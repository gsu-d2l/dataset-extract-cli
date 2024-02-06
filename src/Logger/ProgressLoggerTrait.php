<?php

declare(strict_types=1);

namespace GSU\D2L\DataHub\Extract\Logger;

use mjfklib\Logger\LoggerAwareTrait;

trait ProgressLoggerTrait
{
    use LoggerAwareTrait;


    private string $logEventPrefix = '';
    /** @var int<0,max> $logEventInterval */
    private int $logEventInterval = 0;
    private int $logEventCount = 0;
    private float $progressLoggerStarted = 0.0;
    private int $nextLogEvent = PHP_INT_MAX;


    /**
     * @param string $prefix
     * @param int<0,max> $logEventInterval
     * @return void
     */
    public function start(
        string $prefix = '',
        int $logEventInterval = 60
    ): void {
        $this->logEventPrefix = $prefix;
        $this->logEventInterval = $logEventInterval;
        $this->progressLoggerStarted = microtime(true);
        $this->logEventCount = 0;
        $this->nextLogEvent = time() + $this->logEventInterval;
    }


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
    ): void {
        if ($this->nextLogEvent <= time()) {
            $this->logEventCount++;
            $this->nextLogEvent = time() + 60;
            $this->logger?->debug("({$this->logEventPrefix}) " . $this->formatLogResults([
                'extract'  => $extractName,
                'count' => $count,
                'percent' => number_format($percent, 3) . '%',
                'elapsed' => $this->getElapsedTime($this->progressLoggerStarted)
            ]));
        }
    }


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
    ): void {
        if ($this->logEventCount > 0) {
            $this->logger?->debug("({$this->logEventPrefix}) " . $this->formatLogResults([
                'extract'  => $extractName,
                'total' => $total,
                'percent' => number_format($percent, 3) . '%',
                'elapsed' => $this->getElapsedTime($this->progressLoggerStarted)
            ]));
        }
    }
}
