<?php

declare(strict_types=1);

namespace GSU\D2L\DataHub\Extract\ProcessExtract\Oracle;

use GSU\D2L\DataHub\Extract\Exception\MissingColumnException;
use GSU\D2L\DataHub\Extract\Exception\ProcessExtractException;
use GSU\D2L\DataHub\Extract\Logger\ProgressLoggerAwareTrait;
use GSU\D2L\DataHub\Extract\Model\ExtractContext;
use GSU\D2L\DataHub\Extract\Model\ExtractInfo;
use GSU\D2L\DataHub\Extract\Model\ExtractProcessType;
use GSU\D2L\DataHub\Extract\ProcessExtract\ExtractProcessorInterface;
use GSU\D2L\DataHub\Extract\Utils\ColumnMapper;
use GSU\D2L\DataHub\Schema\Model\ColumnSchema;
use GSU\D2L\DataHub\Schema\Model\ColumnSchemaType;
use GSU\D2L\DataHub\Schema\Model\SQLType;
use GSU\D2L\DataHub\Schema\SchemaRepositoryInterface;
use mjfklib\Logger\LoggerAwareTrait;
use mjfklib\Utils\FileMethods;
use Psr\Log\LoggerAwareInterface;

final class OracleProcessor implements LoggerAwareInterface, ExtractProcessorInterface
{
    use LoggerAwareTrait;
    use ProgressLoggerAwareTrait;


    /**
     * @param string $name
     * @return string
     */
    public static function getTableColumnName(string $name): string
    {
        return match (strtolower($name)) {
            "group", "comment", "order" => "D2L" . $name,
            default => $name
        };
    }


    /** @var array<string,string> */
    private const SPECIAL_CHARS = [
        "\xC2\xAB" => "<<",
        "\xC2\xBB" => ">>",
        "\xE2\x80\x98" => "'",
        "\xE2\x80\x99" => "'",
        "\xE2\x80\x9A" => "'",
        "\xE2\x80\x9B" => "'",
        "\xE2\x80\x9C" => '"',
        "\xE2\x80\x9D" => '"',
        "\xE2\x80\x9E" => '"',
        "\xE2\x80\x9F" => '"',
        "\xE2\x80\xB9" => "<",
        "\xE2\x80\xBA" => ">",
        "\xE2\x80\x93" => "-",
        "\xE2\x80\x94" => "-",
        "\xE2\x80\xA6" => "...",
        "\t" => " ",
        "\r" => " ",
        "\n" => " ",
    ];

    /** @var array<string> $search */
    private array $search;

    /** @var array<string> $replacements */
    private array $replacements;


    /**
     * @param SchemaRepositoryInterface $schemaRepository
     */
    public function __construct(private SchemaRepositoryInterface $schemaRepository)
    {
        $this->search = array_keys(self::SPECIAL_CHARS);
        $this->replacements = array_values(self::SPECIAL_CHARS);
    }


    /**
     * @inheritdoc
     */
    public function processFull(
        ExtractInfo $extract,
        bool $force
    ): ExtractContext|null {
        return $this->processExtract(
            new OracleProcessorParams(
                extract: $extract,
                columns: $extract->schema->columns,
                getLoadTable: GetLoadTable::getLoadTableFull(),
                openInputFile: OpenInputFile::openInputFileFull(),
                getSqlContents: GetSqlContents::getSqlContentsFull()
            ),
            $force
        );
    }


    /**
     * @inheritdoc
     */
    public function processFullDiff(
        ExtractInfo $extract,
        bool $force
    ): ExtractContext|null {
        return $this->processExtract(
            new OracleProcessorParams(
                extract: $extract,
                columns: $extract->schema->getPrimaryColumns(),
                getLoadTable: GetLoadTable::getLoadTableFullDiff(),
                openInputFile: OpenInputFile::openInputFileFullDiff(),
                getSqlContents: GetSqlContents::getSqlContentsFullDiff()
            ),
            $force
        );
    }


    /**
     * @inheritdoc
     */
    public function processDiff(
        ExtractInfo $extract,
        bool $force
    ): ExtractContext|null {
        return $this->processExtract(
            new OracleProcessorParams(
                extract: $extract,
                columns: $extract->schema->columns,
                getLoadTable: GetLoadTable::getLoadTableDiff(),
                openInputFile: OpenInputFile::openInputFileDiff(),
                getSqlContents: GetSqlContents::getSqlContentsDiff()
            ),
            $force
        );
    }


    /**
     * @param OracleProcessorParams $params
     * @param bool $force
     * @return ExtractContext|null
     */
    public function processExtract(
        OracleProcessorParams $params,
        bool $force
    ): ExtractContext|null {
        try {
            $started = time();

            if ($force) {
                FileMethods::deleteFiles(sprintf(
                    "%s/%s*",
                    dirname($params->extract->processPath->getPath()),
                    $params->extract->extractName
                ));
            } elseif ($params->extract->processPath->exists()) {
                return null;
            }

            $params->tableName = $this->schemaRepository->fetchSqlTableName(
                SQLType::ORACLE,
                $params->extract->schema
            );

            $ctlPath = $this->buildCtlFile($params);

            list($datPath, $recordCount) = $this->buildDataFile($params);

            $sqlPath = $this->buildSqlFile($params);

            return new ExtractContext(
                extract: $params->extract,
                path: $params->extract->processPath,
                processType: ExtractProcessType::ORACLE,
                files: [
                    'ctl' => basename($ctlPath),
                    'dat' => basename($datPath),
                    'sql' => basename($sqlPath)
                ],
                recordCount: $recordCount,
                started: $started,
                finished: time()
            );
        } catch (\Throwable $t) {
            throw new ProcessExtractException(
                $params->extract->extractName,
                $t
            );
        }
    }


    /**
     * @param OracleProcessorParams $params
     * @return string
     */
    public function buildCtlFile(OracleProcessorParams $params): string
    {
        $ctlPath = sprintf(
            '%s/%s.ctl',
            dirname($params->extract->processPath->getPath()),
            $params->extract->extractName
        );

        $loadTable = $params->getLoadTable->__invoke($params->tableName);

        FileMethods::putContents(
            $ctlPath,
            [
                "UNRECOVERABLE",
                "LOAD DATA",
                "CHARACTERSET UTF8",
                "TRUNCATE INTO TABLE {$loadTable}",
                "FIELDS TERMINATED BY \",\"",
                "OPTIONALLY ENCLOSED BY '\"'",
                "TRAILING NULLCOLS",
                "(",
                implode(",\n", array_map(
                    fn ($c) => $this->getCtlColumn($c),
                    $params->columns
                )),
                ")",
            ]
        );

        return $ctlPath;
    }


    /**
     * @param OracleProcessorParams $params
     * @return array{0:string,1:int}
     */
    public function buildDataFile(OracleProcessorParams $params): array
    {
        try {
            list(
                $inputFile,
                $inputStream,
                $inputColumns
            ) = $params->openInputFile->__invoke($params->extract);
            $inputSize = $inputFile->statIndex(0)['size'] ?? 0;
            if (count($inputColumns) < 1) {
                throw new MissingColumnException(
                    $params->extract,
                    new \UnexpectedValueException("Input column array is empty")
                );
            }

            $outputPath = sprintf(
                '%s/%s.dat.gz',
                dirname($params->extract->processPath->getPath()),
                $params->extract->extractName
            );
            $outputStream = FileMethods::openGzipFile($outputPath);
            if (count($params->columns) < 1) {
                throw new MissingColumnException(
                    $params->extract,
                    new \UnexpectedValueException("Output column array is empty")
                );
            }

            $columnMap = $this->buildColumnMap(
                $inputColumns,
                $params
            );
            if (count($columnMap) < 1) {
                throw new MissingColumnException(
                    $params->extract,
                    new \UnexpectedValueException("Column map is empty")
                );
            }

            $recordCount = 0;
            $this->progressLogger?->start('oracle-processor');
            for ($line = 1; $inputRow = fgetcsv(stream: $inputStream, escape: '"'); $line++) {
                /** @var array<int,string> $inputRow */
                try {
                    $bytes = fputcsv(
                        $outputStream,
                        $this->buildOutputRow(
                            $columnMap,
                            $inputRow
                        ),
                        ",",
                        "\"",
                        "",
                        "\n"
                    );
                    if ($bytes === false) {
                        throw new ProcessExtractException("fputcsv() returned false");
                    }

                    $this->progressLogger?->update(
                        $params->extract->extractName,
                        ++$recordCount,
                        $inputSize > 0 ? 100 * (intval(ftell($inputStream)) / $inputSize) : 0
                    );
                } catch (\Throwable $t) {
                    throw new ProcessExtractException(
                        "Line '{$line}'",
                        $t
                    );
                }
            }

            $this->progressLogger?->finish(
                $params->extract->extractName,
                $recordCount
            );

            return [
                $outputPath,
                $recordCount
            ];
        } finally {
            if (($inputFile ?? null) instanceof \ZipArchive) {
                $inputFile->close();
            }
            if (is_resource($inputStream ?? null)) {
                fclose($inputStream);
            }
            if (is_resource($outputStream ?? null)) {
                gzclose($outputStream);
            }
        }
    }


    /**
     * @param OracleProcessorParams $params
     * @return string
     */
    public function buildSqlFile(OracleProcessorParams $params): string
    {
        $sqlPath = sprintf(
            '%s/%s.sql',
            dirname($params->extract->processPath->getPath()),
            $params->extract->extractName
        );

        FileMethods::putContents(
            $sqlPath,
            [
                ...$params->getSqlContents->__invoke(
                    $params->tableName,
                    $params->columns
                ),
                "",
                "QUIT;"
            ]
        );

        return $sqlPath;
    }


    /**
     * @param array<int,string|ColumnSchema> $inputColumns
     * @param OracleProcessorParams $params
     * @return array{0:int,1:int,2:ColumnSchema,3:(callable(string $value):string)}[]
     */
    private function buildColumnMap(
        array $inputColumns,
        OracleProcessorParams $params
    ): array {
        $columnMap = ColumnMapper::buildColumnMap(
            $inputColumns,
            $params->columns
        );

        foreach ($columnMap as &$column) {
            list($inIndex, $outIndex, $outColumn) = $column;

            if ($inIndex >= 0) {
                $column[3] = $this->buildFormatter($outColumn);
            } elseif (!$outColumn->isPrimary) {
                $this->logger?->warning(sprintf(
                    "Missing field: %s.%s",
                    $params->extract->schema->getSimpleName(),
                    $outColumn->name
                ));

                $column[3] = $this->buildFormatter($outColumn);
            } else {
                throw new \RuntimeException(sprintf(
                    "Missing field: %s.%s",
                    $params->extract->schema->getSimpleName(),
                    $outColumn->name
                ));
            }
        }

        /** @var array{0:int,1:int,2:ColumnSchema,3:(callable(string $value):string)}[] $columnMap */
        return $columnMap;
    }


    /**
     * @param ColumnSchema $outColumn
     * @return (callable(string $value):string) $formatter
     */
    private function buildFormatter(ColumnSchema $outColumn): callable
    {
        return match ($outColumn->type) {
            ColumnSchemaType::BIT => fn ($v) => ($v !== '' || $outColumn->isPrimary === true)
                ? match (strtoupper($v)) {
                    "1", "T", "TRUE" => "1",
                    default => "0"
                }
                : $v,
            ColumnSchemaType::FLOAT => fn ($v) => ($v !== '' || $outColumn->isPrimary === true)
                ? strval(floatval($v))
                : $v,
            ColumnSchemaType::BIGINT,
            ColumnSchemaType::INT,
            ColumnSchemaType::SMALLINT => fn ($v) => ($v !== '' || $outColumn->isPrimary === true)
                ? strval(intval($v))
                : $v,
            ColumnSchemaType::NVARCHAR,
            ColumnSchemaType::VARCHAR => fn ($v) => ($v !== '')
                ? mb_substr(
                    str_replace($this->search, $this->replacements, $v),
                    0,
                    min(intval(2 * max(1, intval($outColumn->size))), 4000)
                )
                : $v,
            default => fn ($v) => $v,
        };
    }


    /**
     * @param array{0:int,1:int,2:ColumnSchema,3:(callable(string $value):string)}[] $columnMap
     * @param array<int,string> $inputRow
     * @return array<int,string>
     */
    private function buildOutputRow(
        array &$columnMap,
        array &$inputRow
    ): array {
        $outputRow = [];
        foreach ($columnMap as list($inIndex, $outIndex, $column, $formatter)) {
            try {
                $outputRow[$outIndex] = $formatter($inputRow[$inIndex] ?? '');
            } catch (\Throwable $t) {
                throw new ProcessExtractException(
                    "Column '{$column->name}'",
                    $t
                );
            }
        }
        return $outputRow;
    }


    /**
     * @param ColumnSchema $column
     * @return string
     */
    private function getCtlColumn(ColumnSchema $column): string
    {
        $name = OracleProcessor::getTableColumnName($column->name);

        /** @var int $size */
        $size = max(match ($column->type) {
            ColumnSchemaType::BIT => 1,
            ColumnSchemaType::SMALLINT => 5,
            ColumnSchemaType::INT => 10,
            ColumnSchemaType::BIGINT => 20,
            ColumnSchemaType::DATETIME2 => 21,
            ColumnSchemaType::DECIMAL => intval(array_sum(explode(",", $column->size))),
            ColumnSchemaType::FLOAT => 126,
            ColumnSchemaType::NVARCHAR,
            ColumnSchemaType::VARCHAR => intval(2.5 * intval($column->size)),
            ColumnSchemaType::UNIQUEIDENTIFIER => 36
        }, 1);

        $type = match ($column->type) {
            ColumnSchemaType::BIGINT,
            ColumnSchemaType::BIT,
            ColumnSchemaType::INT,
            ColumnSchemaType::SMALLINT => "INTEGER EXTERNAL({$size})",
            ColumnSchemaType::DATETIME2 => "TIMESTAMP WITH TIME ZONE 'yyyy-mm-dd\"T\"hh24:mi:ss.fftzhtzm'",
            ColumnSchemaType::DECIMAL => "DECIMAL EXTERNAL({$size})",
            ColumnSchemaType::FLOAT => "FLOAT EXTERNAL({$size})",
            ColumnSchemaType::NVARCHAR,
            ColumnSchemaType::VARCHAR,
            ColumnSchemaType::UNIQUEIDENTIFIER => "CHAR({$size})"
        };

        return implode(
            " ",
            ($column->isPrimary)
                ? [" ", $name, $type]
                : [" ", $name, $type, "NULLIF ({$name}=BLANKS)"]
        );
    }
}
