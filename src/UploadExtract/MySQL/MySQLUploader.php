<?php

declare(strict_types=1);

namespace GSU\D2L\DataHub\Extract\UploadExtract\MySQL;

use GSU\D2L\DataHub\Extract\Exception\FileNotFoundException;
use GSU\D2L\DataHub\Extract\Exception\InvalidExtractStatusException;
use GSU\D2L\DataHub\Extract\Model\ExtractContext;
use GSU\D2L\DataHub\Extract\Model\ExtractInfo;
use GSU\D2L\DataHub\Extract\Model\ExtractProcessType;
use GSU\D2L\DataHub\Extract\Model\ExtractStatus;
use GSU\D2L\DataHub\Extract\ShellCommand\ShellCommandInterface;
use GSU\D2L\DataHub\Extract\UploadExtract\ExtractUploaderInterface;
use mjfklib\Utils\FileMethods;

final class MySQLUploader implements ExtractUploaderInterface
{
    /**
     * @param MySQLUploadConfig $config
     * @param ShellCommandInterface $shellCommand
     */
    public function __construct(
        private MySQLUploadConfig $config,
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
            throw new InvalidExtractStatusException(
                $extract,
                ExtractStatus::PROCESSED
            );
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
                processType: ExtractProcessType::MYSQL,
                started: $started,
                finished: time()
            );
        }

        $cmdPath = $this->shellCommand->getCmdPath('upload/mysql-upload');
        $sqlPath = $this->config->processDir . "/" . ($extract->processContext->files['sql'] ?? '');
        if (!is_file($sqlPath)) {
            throw new FileNotFoundException($sqlPath);
        }
        $database = $this->config->database;
        $options = $this->config->options;
        $outPath = "{$this->config->uploadDir}/{$extract->extractName}.out";

        $this->shellCommand->exec(sprintf(
            "%s %s %s %s > %s 2>&1",
            escapeshellcmd($cmdPath),
            escapeshellarg($sqlPath),
            escapeshellarg($database),
            escapeshellarg($options),
            escapeshellarg($outPath)
        ));

        if (!is_file($outPath)) {
            throw new FileNotFoundException($outPath);
        }
        if (filesize($outPath) !== 0) {
            throw new \RuntimeException("Out file contains errors");
        }

        return new ExtractContext(
            extract: $extract,
            path: $extract->uploadPath,
            processType: ExtractProcessType::ORACLE,
            started: $started,
            files: [
                'out' => basename($outPath)
            ],
            recordCount: $extract->processContext->recordCount,
            finished: time()
        );
    }
}
