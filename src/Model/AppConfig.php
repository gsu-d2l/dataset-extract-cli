<?php

declare(strict_types=1);

namespace GSU\D2L\DataHub\Extract\Model;

use mjfklib\Utils\FileMethods;

final class AppConfig
{
    public string $binDir;
    public string $availableDir;
    public string $downloadDir;
    public string $indexDir;
    public string $fullDiffDir;
    public string $processDir;
    public string $uploadDir;
    public ExtractProcessType $processType;


    /**
     * @param string $appEnv
     * @param string $binDir
     * @param string $availableDir
     * @param string $downloadDir
     * @param string $indexDir
     * @param string $fullDiffDir
     * @param string $processDir
     * @param string $uploadDir
     * @param string|ExtractProcessType $processType
     */
    public function __construct(
        public string $appEnv,
        string $binDir,
        string $availableDir,
        string $downloadDir,
        string $indexDir,
        string $fullDiffDir,
        string $processDir,
        string $uploadDir,
        string|ExtractProcessType $processType
    ) {
        $this->binDir = FileMethods::getDirPath($binDir);
        $this->availableDir = FileMethods::getDirPath($availableDir);
        $this->downloadDir = FileMethods::getDirPath($downloadDir);
        $this->indexDir = FileMethods::getDirPath($indexDir);
        $this->fullDiffDir = FileMethods::getDirPath($fullDiffDir);
        $this->processDir = FileMethods::getDirPath($processDir);
        $this->uploadDir = FileMethods::getDirPath($uploadDir);
        $this->processType = ExtractProcessType::getType($processType);
    }
}
