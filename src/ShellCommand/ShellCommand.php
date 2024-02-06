<?php

declare(strict_types=1);

namespace GSU\D2L\DataHub\Extract\ShellCommand;

use GSU\D2L\DataHub\Extract\Exception\FileNotFoundException;
use GSU\D2L\DataHub\Extract\Model\AppConfig;

final class ShellCommand implements ShellCommandInterface
{
    /**
     * @param AppConfig $config
     */
    public function __construct(private AppConfig $config)
    {
    }


    /**
     * @inheritdoc
     */
    public function getCmdPath(string $cmd): string
    {
        $cmdPath = realpath("{$this->config->binDir}/{$cmd}");
        return is_string($cmdPath)
            ? $cmdPath
            : throw new FileNotFoundException("{$this->config->binDir}/{$cmd}");
    }


    /**
     * @inheritdoc
     */
    public function exec(string $command): int
    {
        $result_code = 0;

        exec(
            command: $command,
            result_code: $result_code
        );

        return ($result_code === 0)
            ? $result_code
            : throw new \RuntimeException("Status code {$result_code}: {$command}");
    }
}
