<?php

declare(strict_types=1);

namespace GSU\D2L\DataHub\Extract\ProcessExtract\MySQL;

use GSU\D2L\DataHub\Extract\Exception\ProcessExtractException;
use GSU\D2L\DataHub\Extract\Exception\SkippedExtractException;
use GSU\D2L\DataHub\Extract\Logger\ProgressLoggerAwareInterface;
use GSU\D2L\DataHub\Extract\Logger\ProgressLoggerAwareTrait;
use GSU\D2L\DataHub\Extract\Model\ExtractContext;
use GSU\D2L\DataHub\Extract\Model\ExtractInfo;
use GSU\D2L\DataHub\Extract\ProcessExtract\ExtractProcessorInterface;
use GSU\D2L\DataHub\Schema\Model\SQLType;
use GSU\D2L\DataHub\Schema\SchemaRepositoryInterface;
use mjfklib\Logger\LoggerAwareTrait;
use Psr\Log\LoggerAwareInterface;

final class MySQLProcessor implements LoggerAwareInterface, ProgressLoggerAwareInterface, ExtractProcessorInterface
{
    use LoggerAwareTrait;
    use ProgressLoggerAwareTrait;


    /**
     * @param SchemaRepositoryInterface $schemaRepository
     */
    public function __construct(private SchemaRepositoryInterface $schemaRepository)
    {
    }


    /**
     * @inheritdoc
     */
    public function processFull(
        ExtractInfo $extract,
        bool $force
    ): ExtractContext|null {
        $params = new RuntimeParams(
            extract: $extract,
            force: $force,
            tableName: $this->schemaRepository->fetchSqlTableName(
                SQLType::MYSQL,
                $extract->schema
            )
        );

        try {
            return (new Runtime($params, $this->logger, $this->progressLogger))
                ->init()
                ->truncateTable()
                ->insertIntoTable()
                ->createExtractContext();
        } catch (SkippedExtractException) {
            return null;
        } catch (\Throwable $t) {
            throw new ProcessExtractException("Extract '{$extract}'", $t);
        } finally {
            unset($params);
        }
    }


    /**
     * @inheritdoc
     */
    public function processFullDiff(
        ExtractInfo $extract,
        bool $force
    ): ExtractContext|null {
        $params = new RuntimeParams(
            extract: $extract,
            force: $force,
            tableName: $this->schemaRepository->fetchSqlTableName(
                SQLType::MYSQL,
                $extract->schema
            ),
            inputPath: $extract->fullDiffPath->getPath(),
            inputStripBOM: false,
            inputColumns: $extract->schema->getPrimaryColumns(),
            outputColumns: $extract->schema->getPrimaryColumns(),
            useLoadTable: true,
        );

        try {
            return (new Runtime($params, $this->logger, $this->progressLogger))
                ->init()
                ->truncateTable()
                ->insertIntoTable()
                ->deleteFromTable()
                ->createExtractContext();
        } catch (SkippedExtractException) {
            return null;
        } catch (\Throwable $t) {
            throw new ProcessExtractException("Extract '{$extract}'", $t);
        } finally {
            unset($params);
        }
    }


    /**
     * @inheritdoc
     */
    public function processDiff(
        ExtractInfo $extract,
        bool $force
    ): ExtractContext|null {
        $params = new RuntimeParams(
            extract: $extract,
            force: $force,
            tableName: $this->schemaRepository->fetchSqlTableName(
                SQLType::MYSQL,
                $extract->schema
            ),
            insertIgnore: false,
            insertOnDuplicateKeyUpdate: true
        );

        try {
            return (new Runtime($params, $this->logger, $this->progressLogger))
                ->init()
                ->insertIntoTable()
                ->createExtractContext();
        } catch (SkippedExtractException) {
            return null;
        } catch (\Throwable $t) {
            throw new ProcessExtractException("Extract '{$extract}'", $t);
        } finally {
            unset($params);
        }
    }
}
