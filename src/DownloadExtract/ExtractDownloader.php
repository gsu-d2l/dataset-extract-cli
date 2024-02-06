<?php

declare(strict_types=1);

namespace GSU\D2L\DataHub\Extract\DownloadExtract;

use GSU\D2L\API\DataHub\DataHubAPI;
use GSU\D2L\DataHub\Extract\Exception\FileNotFoundException;
use GSU\D2L\DataHub\Extract\Model\ExtractInfo;
use mjfklib\Utils\FileMethods;

final class ExtractDownloader implements ExtractDownloaderInterface
{
    /**
     * @param DataHubAPI $dataHubAPI
     */
    public function __construct(private DataHubAPI $dataHubAPI)
    {
    }


    /**
     * @param ExtractInfo $extract
     * @param bool $force
     * @return int
     */
    public function downloadExtract(
        ExtractInfo $extract,
        bool $force
    ): int {
        if ($force) {
            FileMethods::deleteFiles(sprintf(
                "%s*",
                $extract->downloadPath->getPath()
            ));
        } elseif ($extract->downloadPath->exists()) {
            return 0;
        }

        $bytes = $this->dataHubAPI->downloadBDSExtract(
            $extract->url,
            $extract->downloadPath->getPath()
        );

        return ($extract->downloadPath->exists())
            ? $bytes
            : throw new FileNotFoundException($extract->downloadPath);
    }
}
