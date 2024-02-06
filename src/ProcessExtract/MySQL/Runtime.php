<?php

declare(strict_types=1);

namespace GSU\D2L\DataHub\Extract\ProcessExtract\MySQL;

use GSU\D2L\DataHub\Extract\Exception\MissingColumnException;
use GSU\D2L\DataHub\Extract\Exception\ProcessExtractException;
use GSU\D2L\DataHub\Extract\Exception\SkippedExtractException;
use GSU\D2L\DataHub\Extract\Logger\ProgressLoggerInterface;
use GSU\D2L\DataHub\Extract\Model\ExtractContext;
use GSU\D2L\DataHub\Extract\Model\ExtractProcessType;
use GSU\D2L\DataHub\Extract\Utils\ColumnMapper;
use GSU\D2L\DataHub\Schema\Model\ColumnSchema;
use GSU\D2L\DataHub\Schema\Model\ColumnSchemaType;
use mjfklib\Utils\FileMethods;
use Psr\Log\LoggerInterface;

final class Runtime
{
    /**
     * @param RuntimeParams $params
     * @param LoggerInterface|null $logger
     * @param ProgressLoggerInterface|null $progressLogger
     */
    public function __construct(
        private RuntimeParams $params,
        private LoggerInterface|null $logger,
        private ProgressLoggerInterface|null $progressLogger
    ) {
    }


    /**
     * @return self
     */
    public function init(): self
    {
        if ($this->params->force) {
            FileMethods::deleteFiles(sprintf(
                "%s/%s*",
                dirname($this->params->extract->processPath->getPath()),
                $this->params->extract->extractName
            ));
        } elseif ($this->params->extract->processPath->exists()) {
            throw new SkippedExtractException($this->params->extract);
        }

        return $this;
    }


    /**
     * @return self
     */
    public function truncateTable(): self
    {
        return $this->writeOutput(sprintf(
            "TRUNCATE TABLE `%s%s`;",
            $this->params->tableName,
            $this->params->useLoadTable ? '_LOAD' : ''
        ));
    }


    /**
     * @return self
     */
    public function insertIntoTable(): self
    {
        $this->params->recordCount = 0;
        $this->progressLogger?->start('mysql-processor');

        $inputSize = $this->params->getInputFile()->statIndex(0)['size'] ?? 0;
        if ($inputSize < 1) {
            return $this;
        }

        $buffer = [];
        $inputStream = $this->params->getInputStream();
        $insertHeader = $this->getInsertHeader();
        $insertFooter = $this->getInsertFooter();
        $columnMap = $this->buildColumnMap();

        for ($line = 1; $inputRow = fgetcsv(stream: $inputStream, escape: '"'); $line++) {
            /** @var array<int,string> $inputRow */
            try {
                $buffer[] = $this->buildOutputRow(
                    $inputRow,
                    $columnMap
                );

                if (count($buffer) >= 10000) {
                    $this->writeOutput([
                        $insertHeader,
                        implode(",\n", $buffer),
                        $insertFooter,
                    ]);
                    $this->params->recordCount += count($buffer);
                    $buffer = [];
                }

                $this->progressLogger?->update(
                    $this->params->extract->extractName,
                    $line - 1,
                    100 * (intval(ftell($inputStream)) / $inputSize)
                );
            } catch (\Throwable $t) {
                throw new ProcessExtractException("Line '{$this->params->recordCount}'", $t);
            }
        }

        if (count($buffer) > 0) {
            $this->writeOutput([
                $insertHeader,
                implode(",\n", $buffer),
                $insertFooter
            ]);
            $this->params->recordCount += count($buffer);
            $buffer = [];
        }

        $this->progressLogger?->finish(
            $this->params->extract->extractName,
            $this->params->recordCount
        );

        return $this;
    }


    /**
     * @return self
     */
    public function deleteFromTable(): self
    {
        return $this->writeOutput([
            "DELETE FROM",
            "  `{$this->params->tableName}` t",
            "WHERE",
            "  EXISTS(",
            "    SELECT",
            "      1",
            "    FROM",
            "      `{$this->params->tableName}_LOAD` s",
            "    WHERE",
            implode(" AND\n", array_map(
                fn ($c) => sprintf("      s.%1\$s = t.%1\$s", $c->name),
                $this->params->outputColumns
            )),
            "  )",
            ";"
        ]);
    }


    /**
     * @return ExtractContext
     */
    public function createExtractContext(): ExtractContext
    {
        return new ExtractContext(
            $this->params->extract,
            $this->params->extract->processPath,
            ExtractProcessType::MYSQL,
            [
                'sql' => basename($this->params->outputPath)
            ],
            $this->params->recordCount,
            $this->params->started,
            time()
        );
    }


    /**
     * @param string|string[] $line
     * @return self
     */
    private function writeOutput(string|array $line): self
    {
        fwrite(
            $this->params->getOutputStream(),
            (is_array($line) ? implode("\n", $line) : $line) . "\n"
        );

        return $this;
    }


    /**
     * @return string
     */
    private function getInsertHeader(): string
    {
        return implode("\n", [
            sprintf(
                "%s INTO `%s`",
                $this->params->insertIgnore ? "INSERT IGNORE" : "INSERT",
                $this->params->tableName
            ),
            "  (",
            implode(",\n", array_map(
                fn ($c) => "    `{$c->name}`",
                $this->params->outputColumns
            )),
            "  )",
            "VALUES"
        ]);
    }


    /**
     * @return string
     */
    private function getInsertFooter(): string
    {
        return $this->params->insertOnDuplicateKeyUpdate
            ? implode("\n", [
                "AS new ON DUPLICATE KEY UPDATE",
                implode(",\n", array_map(
                    fn ($c) =>  "  `{$c->name}`=new.`{$c->name}`",
                    $this->params->outputColumns
                )) . "\n",
                ";"
            ])
            : ";";
    }


    /**
     * @return array{0:int,1:int,2:ColumnSchema,3:(callable(string $value):string)}[]
     */
    private function buildColumnMap(): array
    {
        $inputColumns = $this->params->inputColumns ?? $this->getColumnsFromInputStream();
        if (count($inputColumns) < 1) {
            throw new MissingColumnException(
                $this->params->extract,
                new \UnexpectedValueException("Input column array is empty")
            );
        }

        if (count($this->params->outputColumns) < 1) {
            throw new MissingColumnException(
                $this->params->extract,
                new \UnexpectedValueException("Output column array is empty")
            );
        }

        $columnMap = ColumnMapper::buildColumnMap(
            $inputColumns,
            $this->params->outputColumns
        );

        foreach ($columnMap as &$column) {
            list($inIndex, $outIndex, $outColumn) = $column;

            if ($inIndex >= 0) {
                $column[3] = $this->createFormatter(
                    $outColumn->type,
                    $outColumn->isPrimary
                );
            } elseif (!$outColumn->isPrimary) {
                $this->logger?->warning(sprintf(
                    "Missing field: %s.%s",
                    $this->params->extract->schema->getSimpleName(),
                    $outColumn->name
                ));

                $column[3] = $this->createFormatter(
                    $outColumn->type,
                    $outColumn->isPrimary
                );
            } else {
                throw new \RuntimeException(sprintf(
                    "Missing field: %s.%s",
                    $this->params->extract->schema->getSimpleName(),
                    $outColumn->name
                ));
            }
        }

        /** @var array{0:int,1:int,2:ColumnSchema,3:(callable(string $value):string)}[] $columnMap */
        return $columnMap;
    }


    /**
     * @return array<int,ColumnSchema>
     */
    private function getColumnsFromInputStream(): array
    {
        $columns = [];
        foreach ($this->params->extract->schema->columns as $column) {
            $columns[strtoupper($column->name)] = $column;
        }

        /** @var array<int,string>|false|null */
        $inColumns = fgetcsv(
            stream: $this->params->getInputStream(),
            escape: '"'
        );

        return array_filter(array_map(
            fn (string $name) => $columns[$name] ?? null,
            array_map('strtoupper', is_array($inColumns) ? $inColumns : [])
        ));
    }


    /**
     * @param ColumnSchemaType $type
     * @param bool $isPrimary
     * @return (callable(string $value):string)
     */
    private function createFormatter(
        ColumnSchemaType $type,
        bool $isPrimary
    ): callable {
        return match ($type) {
            ColumnSchemaType::BIT => fn ($v) => ($v !== '' || $isPrimary === true)
                ? match (strtoupper($v)) {
                    "1", "T", "TRUE" => "1",
                    default => "0"
                }
                : 'NULL',
            ColumnSchemaType::FLOAT => fn ($v) => ($v !== '' || $isPrimary === true)
                ? strval(floatval($v))
                : 'NULL',
            ColumnSchemaType::BIGINT,
            ColumnSchemaType::INT,
            ColumnSchemaType::SMALLINT => fn ($v) => ($v !== '' || $isPrimary === true)
                ? strval(intval($v))
                : 'NULL',
            ColumnSchemaType::DATETIME2 => function ($v): string {
                $dateValue = ($v !== '') ? @strtotime($v) : false;
                return is_int($dateValue) ? "'" . date('Y-m-d H:i:s', $dateValue) . "'" : 'NULL';
            },
            ColumnSchemaType::DECIMAL => fn ($v) => match (true) {
                $v !== '' => "'" . $v . "'",
                $isPrimary === true => "'0.00'",
                default => 'NULL'
            },
            ColumnSchemaType::NVARCHAR,
            ColumnSchemaType::VARCHAR => fn ($v) => ($v !== '' || $isPrimary === true)
                ? "'" . preg_replace('~[\x00\x0A\x0D\x1A\x22\x27\x5C]~u', '\\\$0', $v) . "'"
                : 'NULL',
            ColumnSchemaType::UNIQUEIDENTIFIER => fn ($v) => ($v !== '' || $isPrimary === true)
                ? "'" . $v . "'"
                : 'NULL',
        };
    }


    /**
     * @param array<int,string> $inputRow
     * @param array{0:int,1:int,2:ColumnSchema,3:(callable(string $value):string)}[] $columnMap
     * @return string
     */
    private function buildOutputRow(
        array &$inputRow,
        array &$columnMap
    ): string {
        $outputRow = [];
        foreach ($columnMap as list($inIndex, $outIndex, $column, $formatter)) {
            try {
                $outputRow[$outIndex] = $formatter($inputRow[$inIndex] ?? '');
            } catch (\Throwable $t) {
                throw new ProcessExtractException("Column '{$column->name}'", $t);
            }
        }
        return "  (" . implode(",", $outputRow) . ")";
    }
}
