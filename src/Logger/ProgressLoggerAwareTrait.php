<?php

declare(strict_types=1);

namespace GSU\D2L\DataHub\Extract\Logger;

trait ProgressLoggerAwareTrait
{
    protected ProgressLoggerInterface|null $progressLogger;


    /**
     * @inheritdoc
     */
    public function setProgressLogger(ProgressLoggerInterface $progressLogger): void
    {
        $this->progressLogger = $progressLogger;
    }
}
