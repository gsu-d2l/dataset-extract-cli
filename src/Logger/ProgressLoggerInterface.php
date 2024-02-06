<?php

declare(strict_types=1);

namespace GSU\D2L\DataHub\Extract\Logger;

interface ProgressLoggerInterface
{
    /**
     * @param string $prefix
     * @param int<0,max> $logEventInterval
     * @return void
     */
    public function start(
        string $prefix = '',
        int $logEventInterval = 60
    ): void;


    /**
     * @param string $extractName
     * @param int $count
     * @param float $percent
     * @return void
     */
    public function update(
        string $extractName,
        int $count,
        float $percent
    ): void;


    /**
     * @param string $extractName
     * @param int $total
     * @param float $percent
     * @return void
     */
    public function finish(
        string $extractName,
        int $total,
        float $percent = 100
    ): void;
}
