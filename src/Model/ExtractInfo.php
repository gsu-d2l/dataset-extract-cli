<?php

declare(strict_types=1);

namespace GSU\D2L\DataHub\Extract\Model;

use GSU\D2L\DataHub\Schema\Model\DatasetSchema;

final class ExtractInfo extends ExtractBaseInfo implements \Stringable, \JsonSerializable
{
    public ExtractContext|null $processContext;
    public ExtractContext|null $uploadContext;


    /**
     * @param string $extractName
     * @param string $url
     * @param int $size
     * @param DatasetSchema $schema
     * @param ExtractPath $extractPath
     * @param ExtractPath $downloadPath
     * @param ExtractPath $indexPath
     * @param ExtractPath $fullDiffPath
     * @param ExtractPath $processPath
     * @param ExtractPath $uploadPath
     */
    public function __construct(
        string $extractName,
        string $url,
        int $size,
        public readonly DatasetSchema $schema,
        public readonly ExtractPath $extractPath,
        public readonly ExtractPath $downloadPath,
        public readonly ExtractPath $indexPath,
        public readonly ExtractPath $fullDiffPath,
        public readonly ExtractPath $processPath,
        public readonly ExtractPath $uploadPath
    ) {
        parent::__construct(
            $extractName,
            $url,
            $size
        );

        $this->processContext = ($this->processPath->exists())
            ? ExtractContext::create(
                $this,
                $this->processPath
            )
            : null;

        $this->uploadContext = ($this->uploadPath->exists())
            ? ExtractContext::create(
                $this,
                $this->uploadPath
            )
            : null;
    }


    /**
     * @return bool
     */
    public function isFullDiff(): bool
    {
        return $this->type === ExtractType::FULL && $this->fullDiffPath->exists();
    }


    /**
     * @return ExtractStatus
     */
    public function getStatus(): ExtractStatus
    {
        return match (true) {
            $this->uploadPath->exists() => ExtractStatus::UPLOADED,
            $this->processPath->exists() => ExtractStatus::PROCESSED,
            $this->downloadPath->exists() => ExtractStatus::DOWNLOADED,
            default => ExtractStatus::AVAILABLE
        };
    }
}
