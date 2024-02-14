<?php

declare(strict_types=1);

namespace GSU\D2L\DataHub\Extract\BuildIndex;

use GSU\D2L\DataHub\Extract\Exception\FileNotFoundException;
use GSU\D2L\DataHub\Extract\Exception\InvalidExtractTypeException;
use GSU\D2L\DataHub\Extract\Exception\MissingColumnException;
use GSU\D2L\DataHub\Extract\Logger\ProgressLoggerAwareTrait;
use GSU\D2L\DataHub\Extract\Model\ExtractInfo;
use GSU\D2L\DataHub\Extract\Model\ExtractType;
use GSU\D2L\DataHub\Extract\ShellCommand\ShellCommandInterface;
use GSU\D2L\DataHub\Extract\Utils\ColumnMapper;
use GSU\D2L\DataHub\Schema\Model\ColumnSchema;
use mjfklib\Utils\FileMethods;

final class IndexBuilder implements IndexBuilderInterface
{
    use ProgressLoggerAwareTrait;


    /**
     * @param ShellCommandInterface $shellCommand
     */
    public function __construct(private ShellCommandInterface $shellCommand)
    {
    }


    /**
     * @inheritdoc
     */
    public function buildIndex(
        ExtractInfo $extract,
        bool $force,
        int $chunkSize = IndexBuilderInterface::DEFAULT_CHUNK_SIZE
    ): int {
        if ($extract->type !== ExtractType::FULL) {
            throw new InvalidExtractTypeException($extract, ExtractType::FULL);
        }
        if (!$extract->downloadPath->exists()) {
            throw new FileNotFoundException($extract->downloadPath);
        }

        if ($force) {
            FileMethods::deleteFiles(sprintf(
                "%s*",
                $extract->indexPath->getPath()
            ));
        } elseif ($extract->indexPath->exists()) {
            return 0;
        }

        try {
            list($zipFile, $zipStream) = FileMethods::openZipFile($extract->downloadPath->getPath());

            $zipSize = $zipFile->statIndex(0)['size'] ?? 0;

            /** @var array<int,string>|false|null */
            $zipColumns = fgetcsv(stream: $zipStream, escape: '"');
            if (!is_array($zipColumns)) {
                throw new \UnexpectedValueException("Expected string array: {$extract->extractName}");
            }

            $columnMap = ColumnMapper::buildColumnMap(
                $zipColumns,
                $extract->schema->getPrimaryColumns()
            );

            if (count($columnMap) < 1) {
                throw new MissingColumnException(
                    $extract,
                    new \UnexpectedValueException("Column map is empty")
                );
            }

            $this->progressLogger?->start('index-builder');
            $recordCount = 0;
            $hasMore = false;
            do {
                list($chunkRecordCount, $hasMore) = $this->generateChunk(
                    $extract,
                    $columnMap,
                    $zipStream,
                    $chunkSize
                );
                $recordCount += $chunkRecordCount;

                if ($recordCount > 0) {
                    $this->progressLogger?->update(
                        $extract->extractName,
                        $recordCount,
                        $zipSize > 0 ? 100 * intval(ftell($zipStream)) / $zipSize : 0,
                    );
                }
            } while ($hasMore);

            if ($recordCount > 0) {
                $this->mergeIndexChunks($extract);

                if (!$extract->indexPath->exists()) {
                    throw new FileNotFoundException($extract->indexPath);
                }

                $this->progressLogger?->finish(
                    $extract->extractName,
                    $recordCount
                );
            }

            return $recordCount;
        } finally {
            if (is_resource($zipStream ?? null)) {
                fclose($zipStream);
            }
            if (($zipFile ?? null) instanceof \ZipArchive) {
                $zipFile->close();
            }
            unset($zipFile, $zipStream);
        }
    }


    /**
     * @param ExtractInfo $extract
     * @param array{0:int,1:int,2:ColumnSchema}[] $columnMap
     * @param resource $zipStream
     * @param int $chunkSize
     * @return array{0:int,1:bool}
     */
    private function generateChunk(
        ExtractInfo $extract,
        array &$columnMap,
        mixed $zipStream,
        int $chunkSize
    ): array {
        // Read rows into buffer
        $chunk = [];
        for ($i = 0; $inRow = fgetcsv(stream: $zipStream, escape: '"'); $i++) {
            /** @var array<int,string> $inRow */
            $outRow = [];
            foreach ($columnMap as list($inIndex, $outIndex,)) {
                $outRow[$outIndex] = $inRow[$inIndex] ?? '';
            }

            $chunk[implode(",", $outRow)] = $outRow;

            if ($i >= $chunkSize) {
                break;
            }
        }

        // Write buffer to disk
        if (count($chunk) > 0) {
            ksort($chunk, SORT_LOCALE_STRING);

            try {
                $out = FileMethods::openFile(
                    sprintf(
                        '%s.%s',
                        $extract->indexPath->getPath(),
                        intval(ftell($zipStream))
                    ),
                    'w'
                );

                foreach ($chunk as $row) {
                    fputcsv($out, $row, ",", '"', '"', "\n");
                }
            } finally {
                if (is_resource($out ?? null)) {
                    fclose($out);
                }
                unset($out);
            }
        }

        return [
            count($chunk),
            !feof($zipStream)
        ];
    }


    /**
     * @param ExtractInfo $extract
     * @return void
     */
    private function mergeIndexChunks(ExtractInfo $extract): void
    {
        $this->shellCommand->exec(sprintf(
            '%s %s %s',
            $this->shellCommand->getCmdPath('fulldiff/merge-index-chunks'),
            dirname($extract->indexPath->getPath()),
            $extract->indexPath->getPath()
        ));
    }
}
