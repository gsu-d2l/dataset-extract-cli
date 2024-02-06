<?php

declare(strict_types=1);

namespace GSU\D2L\DataHub\Extract\UploadExtract\Oracle;

use GSU\D2L\DataHub\Extract\Exception\FileNotFoundException;
use GSU\D2L\DataHub\Extract\Exception\InvalidExtractStatusException;
use GSU\D2L\DataHub\Extract\Model\ExtractContext;
use GSU\D2L\DataHub\Extract\Model\ExtractInfo;
use GSU\D2L\DataHub\Extract\Model\ExtractProcessType;
use GSU\D2L\DataHub\Extract\Model\ExtractStatus;
use GSU\D2L\DataHub\Extract\ShellCommand\ShellCommandInterface;
use GSU\D2L\DataHub\Extract\UploadExtract\ExtractUploaderInterface;
use mjfklib\Utils\FileMethods;

final class OracleUploader implements ExtractUploaderInterface
{
    /**
     * @param OracleUploadConfig $config
     * @param ShellCommandInterface $shellCommand
     */
    public function __construct(
        private OracleUploadConfig $config,
        private ShellCommandInterface $shellCommand
    ) {
    }


    /**
     * @inheritdoc
     */
    public function upload(
        ExtractInfo $extract,
        bool $force
    ): ExtractContext|null {
        $started = time();

        if ($extract->processContext === null) {
            throw new InvalidExtractStatusException($extract, ExtractStatus::PROCESSED);
        } elseif ($force) {
            FileMethods::deleteFiles(sprintf(
                "%s/%s*",
                dirname($extract->uploadPath->getPath()),
                $extract->extractName
            ));
        } elseif ($extract->uploadPath->exists()) {
            return null;
        }

        if ($extract->processContext->recordCount === 0) {
            return new ExtractContext(
                extract: $extract,
                path: $extract->uploadPath,
                processType: ExtractProcessType::ORACLE,
                started: $started,
                finished: time()
            );
        }

        $cmdPath = $this->shellCommand->getCmdPath('upload/oracle-upload');
        $userId = $this->config->userId;
        $ctlPath = $this->config->processDir . "/" . ($extract->processContext->files['ctl'] ?? '');
        if (!is_file($ctlPath)) {
            throw new FileNotFoundException($ctlPath);
        }
        $datPath = $this->config->processDir . "/" . ($extract->processContext->files['dat'] ?? '');
        if (!is_file($datPath)) {
            throw new FileNotFoundException($datPath);
        }
        $sqlPath = $this->config->processDir . "/" . ($extract->processContext->files['sql'] ?? '');
        if (!is_file($sqlPath)) {
            throw new FileNotFoundException($sqlPath);
        }
        $outPath = $this->config->uploadDir . "/" . $extract->extractName;
        $logFile = $outPath . ".sqlldr.log";
        $badFile = $outPath . ".sqlldr.bad";
        $outFile = $outPath . ".out";

        $this->shellCommand->exec(sprintf(
            "%s %s %s %s %s %s > %s 2>&1",
            escapeshellcmd($cmdPath),
            escapeshellarg($userId),
            escapeshellarg($ctlPath),
            escapeshellarg($datPath),
            escapeshellarg($sqlPath),
            escapeshellarg($outPath),
            escapeshellarg($outFile)
        ));

        if (!is_file($outFile)) {
            throw new FileNotFoundException($outFile);
        }
        if (!is_file($logFile)) {
            throw new FileNotFoundException($logFile);
        }
        if (is_file($badFile)) {
            throw new \RuntimeException("Bad file exists");
        }

        return new ExtractContext(
            extract: $extract,
            path: $extract->uploadPath,
            processType: ExtractProcessType::ORACLE,
            started: $started,
            files: [
                'out' => basename($outFile),
                'log' => basename($logFile)
            ],
            recordCount: $extract->processContext->recordCount,
            finished: time()
        );
    }
}
