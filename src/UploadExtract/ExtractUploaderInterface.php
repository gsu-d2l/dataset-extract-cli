<?php

declare(strict_types=1);

namespace GSU\D2L\DataHub\Extract\UploadExtract;

use GSU\D2L\DataHub\Extract\Model\ExtractContext;
use GSU\D2L\DataHub\Extract\Model\ExtractInfo;

interface ExtractUploaderInterface
{
    /**
     * @param ExtractInfo $extractInfo
     * @param bool $force
     * @return ExtractContext|null
     */
    public function upload(
        ExtractInfo $extractInfo,
        bool $force
    ): ExtractContext|null;
}
