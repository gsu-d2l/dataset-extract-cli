<?php

declare(strict_types=1);

namespace GSU\D2L\DataHub\Extract\UploadExtract\Oracle;

use mjfklib\Utils\FileMethods;

final class OracleUploadConfig
{
    public string $processDir;
    public string $uploadDir;

    /**
     * @param string $processDir
     * @param string $uploadDir
     * @param string $userId
     */
    public function __construct(
        string $processDir,
        string $uploadDir,
        public string $userId,
    ) {
        $this->processDir = FileMethods::getDirPath($processDir);
        $this->uploadDir = FileMethods::getDirPath($uploadDir);
    }
}
