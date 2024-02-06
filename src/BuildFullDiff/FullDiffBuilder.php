<?php

declare(strict_types=1);

namespace GSU\D2L\DataHub\Extract\BuildFullDiff;

use GSU\D2L\DataHub\Extract\Exception\FileNotFoundException;
use GSU\D2L\DataHub\Extract\BuildIndex\IndexBuilderInterface;
use GSU\D2L\DataHub\Extract\Exception\InvalidExtractStatusException;
use GSU\D2L\DataHub\Extract\Exception\InvalidExtractTypeException;
use GSU\D2L\DataHub\Extract\Logger\ProgressLoggerAwareTrait;
use GSU\D2L\DataHub\Extract\Model\ExtractInfo;
use GSU\D2L\DataHub\Extract\Model\ExtractStatus;
use GSU\D2L\DataHub\Extract\Model\ExtractType;
use GSU\D2L\DataHub\Extract\Repository\ExtractRepositoryInterface;
use GSU\D2L\DataHub\Extract\ShellCommand\ShellCommandInterface;
use mjfklib\Utils\FileMethods;

class FullDiffBuilder implements FullDiffBuilderInterface
{
    use ProgressLoggerAwareTrait;


    /**
     * @param ExtractRepositoryInterface $extractRepo
     * @param IndexBuilderInterface $indexBuilder
     * @param ShellCommandInterface $shellCommand
     */
    public function __construct(
        private ExtractRepositoryInterface $extractRepo,
        private IndexBuilderInterface $indexBuilder,
        private ShellCommandInterface $shellCommand
    ) {
    }


    /**
     * @inheritdoc
     */
    public function buildFullDiff(
        ExtractInfo $current,
        bool $force
    ): int {
        if ($current->type !== ExtractType::FULL) {
            throw new InvalidExtractTypeException($current, ExtractType::FULL);
        }
        if (!$current->downloadPath->exists()) {
            throw new InvalidExtractStatusException($current, ExtractStatus::DOWNLOADED);
        }

        if ($force) {
            FileMethods::deleteFiles(sprintf(
                "%s*",
                $current->fullDiffPath->getPath()
            ));
        } elseif ($current->fullDiffPath->exists()) {
            return 0;
        }

        $previous = $this->getPreviousFullExtract($current);
        if ($previous === null) {
            return 0;
        }
        if (!$previous->downloadPath->exists()) {
            throw new InvalidExtractStatusException($previous, ExtractStatus::DOWNLOADED);
        }

        if ($this->progressLogger !== null) {
            $this->indexBuilder->setProgressLogger($this->progressLogger);
        }

        $this->indexBuilder->buildIndex(
            $previous,
            $force
        );

        $this->indexBuilder->buildIndex(
            $current,
            $force
        );

        $this->shellCommand->exec(sprintf(
            '%s %s %s %s',
            $this->shellCommand->getCmdPath('fulldiff/generate-full-diff'),
            $previous->indexPath->getPath(),
            $current->indexPath->getPath(),
            $current->fullDiffPath->getPath()
        ));

        if (!$current->fullDiffPath->exists()) {
            throw new FileNotFoundException($current->fullDiffPath);
        }

        return intval(filesize($current->fullDiffPath->getPath()));
    }


    /**
     * @param ExtractInfo $extract
     * @return ExtractInfo|null
     */
    private function getPreviousFullExtract(ExtractInfo $extract): ExtractInfo|null
    {
        $previousExtracts = $this->extractRepo->getExtracts(
            datasets: $extract->datasetName,
            endDate: \DateTimeImmutable::createFromInterface($extract->timestamp)->modify('-1 second'),
            types: ExtractType::FULL
        );
        return array_pop($previousExtracts);
    }
}
