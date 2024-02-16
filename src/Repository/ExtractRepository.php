<?php

declare(strict_types=1);

namespace GSU\D2L\DataHub\Extract\Repository;

use GSU\D2L\DataHub\Extract\Exception\CreateExtractException;
use GSU\D2L\DataHub\Extract\Exception\DeleteExtractException;
use GSU\D2L\DataHub\Extract\Exception\ExtractNotFoundException;
use GSU\D2L\DataHub\Extract\Exception\FileNotFoundException;
use GSU\D2L\DataHub\Extract\Exception\StoreExtractException;
use GSU\D2L\DataHub\Extract\Model\AppConfig;
use GSU\D2L\DataHub\Extract\Model\ExtractBaseInfo;
use GSU\D2L\DataHub\Extract\Model\ExtractInfo;
use GSU\D2L\DataHub\Extract\Model\ExtractPath;
use GSU\D2L\DataHub\Extract\Model\ExtractStatus;
use GSU\D2L\DataHub\Extract\Model\ExtractType;
use GSU\D2L\DataHub\Schema\Model\DatasetSchemaType;
use GSU\D2L\DataHub\Schema\SchemaRepositoryInterface;
use mjfklib\Logger\LoggerAwareTrait;
use mjfklib\Utils\FileMethods;
use mjfklib\Utils\JSON;
use Psr\Log\LoggerAwareInterface;

final class ExtractRepository implements ExtractRepositoryInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;


    /**
     * @param AppConfig $config
     * @param SchemaRepositoryInterface $schemaRepository
     */
    public function __construct(
        private AppConfig $config,
        private SchemaRepositoryInterface $schemaRepository
    ) {
    }


    /**
     * @param string|ExtractInfo $extract
     * @param ExtractPathType $type
     * @param string $fileExt
     * @return string
     */
    private function getPath(
        string|ExtractInfo $extract,
        ExtractPathType $type,
        string $fileExt
    ): string {
        return sprintf(
            "%s/%s.%s",
            match ($type) {
                ExtractPathType::Extract => $this->config->availableDir,
                ExtractPathType::Download => $this->config->downloadDir,
                ExtractPathType::Index => $this->config->indexDir,
                ExtractPathType::FullDiff => $this->config->fullDiffDir,
                ExtractPathType::Process => $this->config->processDir,
                ExtractPathType::Upload => $this->config->uploadDir,
            },
            strval($extract),
            $fileExt
        );
    }


    /**
     * @inheritdoc
     */
    public function getExtractPath(
        string|ExtractInfo $extract,
        string $fileExt = ExtractRepositoryInterface::EXTRACT_FILE_EXT
    ): string {
        return $this->getPath(
            $extract,
            ExtractPathType::Extract,
            $fileExt
        );
    }


    /**
     * @inheritdoc
     */
    public function getDownloadPath(
        string|ExtractInfo $extract,
        string $fileExt = ExtractRepositoryInterface::DOWNLOAD_FILE_EXT
    ): string {
        return $this->getPath(
            $extract,
            ExtractPathType::Download,
            $fileExt
        );
    }


    /**
     * @inheritdoc
     */
    public function getIndexPath(
        string|ExtractInfo $extract,
        string $fileExt = ExtractRepositoryInterface::INDEX_FILE_EXT
    ): string {
        return $this->getPath(
            $extract,
            ExtractPathType::Index,
            $fileExt
        );
    }


    /**
     * @inheritdoc
     */
    public function getFullDiffPath(
        string|ExtractInfo $extract,
        string $fileExt = ExtractRepositoryInterface::FULLDIFF_FILE_EXT
    ): string {
        return $this->getPath(
            $extract,
            ExtractPathType::FullDiff,
            $fileExt
        );
    }


    /**
     * @inheritdoc
     */
    public function getProcessPath(
        string|ExtractInfo $extract,
        string $fileExt = ExtractRepositoryInterface::PROCESS_FILE_EXT
    ): string {
        return $this->getPath(
            $extract,
            ExtractPathType::Process,
            $fileExt
        );
    }


    /**
     * @inheritdoc
     */
    public function getUploadPath(
        string|ExtractInfo $extract,
        string $fileExt = ExtractRepositoryInterface::UPLOAD_FILE_EXT
    ): string {
        return $this->getPath(
            $extract,
            ExtractPathType::Upload,
            $fileExt
        );
    }


    /**
     * @inheritdoc
     */
    public function createExtract(ExtractBaseInfo $extract): ExtractInfo
    {
        try {
            return new ExtractInfo(
                $extract->extractName,
                $extract->url,
                $extract->size,
                $this->schemaRepository->fetchDataset(
                    DatasetSchemaType::BDS,
                    $extract->datasetName
                ),
                new ExtractPath($this->getExtractPath($extract->extractName)),
                new ExtractPath($this->getDownloadPath($extract->extractName)),
                new ExtractPath($this->getIndexPath($extract->extractName)),
                new ExtractPath($this->getFullDiffPath($extract->extractName)),
                new ExtractPath($this->getProcessPath($extract->extractName)),
                new ExtractPath($this->getUploadPath($extract->extractName))
            );
        } catch (\Throwable $t) {
            throw new CreateExtractException($extract, $t);
        }
    }


    /**
     * @inheritdoc
     */
    public function getExtract(string $extract): ExtractInfo
    {
        try {
            $extractPath = $this->getExtractPath($extract);
            if (!is_file($extractPath)) {
                throw new FileNotFoundException($extractPath);
            }
        } catch (\Throwable $t) {
            throw new ExtractNotFoundException($extract, $t);
        }

        return $this->createExtract(ExtractBaseInfo::create($extractPath));
    }


    /**
     * @inheritdoc
     */
    public function getExtracts(
        string|array $datasets = [],
        \DateTimeInterface|null $startDate = null,
        \DateTimeInterface|null $endDate = null,
        ExtractType|array $types = [],
        ExtractStatus|array $status = []
    ): array {
        if (is_string($datasets)) {
            $datasets = [$datasets];
        }
        if ($types instanceof ExtractType) {
            $types = [$types];
        }
        if ($status instanceof ExtractStatus) {
            $status = [$status];
        }

        /** @var (ExtractInfo|null)[] $extracts */
        $extracts = array_map(
            fn (string $f): ExtractInfo|null => ExtractInfo::isExtractName($f)
                ? $this->getExtract($f)
                : null,
            FileMethods::getFiles(
                $this->config->availableDir,
                ExtractRepositoryInterface::EXTRACT_FILE_EXT
            )
        );

        return array_filter(
            $extracts,
            fn (ExtractInfo|null $e): bool => (
                $e instanceof ExtractInfo &&
                (count($datasets) < 1 || in_array($e->datasetName, $datasets, true)) &&
                (count($types) < 1 || in_array($e->type, $types, true)) &&
                (count($status) < 1 || in_array($e->getStatus(), $status, true)) &&
                ($startDate === null || $e->timestamp >= $startDate) &&
                ($endDate === null || $e->timestamp <= $endDate)
            )
        );
    }


    /**
     * @inheritdoc
     */
    public function storeExtracts(
        ExtractInfo|array $extracts,
        bool $force
    ): int {
        if ($extracts instanceof ExtractInfo) {
            $extracts = [$extracts];
        }

        $storedCount = 0;
        foreach ($extracts as $extract) {
            try {
                $stored = 0;

                if ($force || !$extract->extractPath->exists()) {
                    $stored += FileMethods::putContents(
                        $extract->extractPath->getPath(),
                        JSON::encode($extract)
                    );
                }

                if ($extract->processContext !== null) {
                    if ($force || !$extract->processPath->exists()) {
                        $stored += FileMethods::putContents(
                            $extract->processPath->getPath(),
                            JSON::encode($extract->processContext)
                        );
                    }
                }

                if ($extract->uploadContext !== null) {
                    if ($force || !$extract->uploadPath->exists()) {
                        $stored += FileMethods::putContents(
                            $extract->uploadPath->getPath(),
                            JSON::encode($extract->uploadContext)
                        );
                    }
                }

                $storedCount += ($stored > 0) ? 1 : 0;
            } catch (\Throwable $t) {
                throw new StoreExtractException($extract, $t);
            }
        }

        return $storedCount;
    }


    /**
     * @inheritdoc
     */
    public function deleteExtracts(ExtractInfo|array $extracts): int
    {
        if ($extracts instanceof ExtractInfo) {
            $extracts = [$extracts];
        }

        $deleted = 0;
        foreach ($extracts as $extract) {
            try {
                $this->deleteExtractPath($extract, $extract->extractPath);
                $this->deleteExtractPath($extract, $extract->downloadPath);
                $this->deleteExtractPath($extract, $extract->indexPath);
                $this->deleteExtractPath($extract, $extract->fullDiffPath);
                $this->deleteExtractPath($extract, $extract->processPath);
                $this->deleteExtractPath($extract, $extract->uploadPath);

                $deleted++;
            } catch (\Throwable $t) {
                throw new DeleteExtractException($extract, $t);
            }
        }

        return $deleted;
    }


    /**
     * @param ExtractInfo $extract
     * @param ExtractPath $path
     * @return void
     */
    private function deleteExtractPath(
        ExtractInfo $extract,
        ExtractPath $path
    ): void {
        if ($path->exists()) {
            FileMethods::deleteFiles(sprintf(
                "%s/%s.*",
                dirname($path->getPath()),
                $extract->extractName
            ));
        }

        if ($path->exists()) {
            throw new \RuntimeException("Path still exists: {$path->__toString()}");
        }
    }
}
