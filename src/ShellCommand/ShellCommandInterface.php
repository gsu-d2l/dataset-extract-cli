<?php

declare(strict_types=1);

namespace GSU\D2L\DataHub\Extract\ShellCommand;

interface ShellCommandInterface
{
    /**
     * @param string $cmd
     * @return string
     */
    public function getCmdPath(string $cmd): string;


    /**
     * @param string $command
     * @return int
     */
    public function exec(string $command): int;
}
