<?php

declare(strict_types=1);

namespace GSU\D2L\DataHub\Extract\UploadExtract\MySQL;

use mjfklib\Utils\FileMethods;

final class MySQLUploadConfig
{
    public string $processDir;
    public string $uploadDir;

    /**
     * @param string $processDir
     * @param string $uploadDir
     * @param string $database
     * @param string $options
     */
    public function __construct(
        string $processDir,
        string $uploadDir,
        public string $database,
        public string $options = ''
    ) {
        $this->processDir = FileMethods::getDirPath($processDir);
        $this->uploadDir = FileMethods::getDirPath($uploadDir);
    }
}
