<?php

declare(strict_types=1);

namespace GSU\D2L\DataHub\Extract\DownloadExtract;

use GSU\D2L\DataHub\Extract\Model\ExtractInfo;

interface ExtractDownloaderInterface
{
    /**
     * @param ExtractInfo $extract
     * @param bool $force
     * @return int
     */
    public function downloadExtract(
        ExtractInfo $extract,
        bool $force
    ): int;
}
