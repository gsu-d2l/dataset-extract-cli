<?php

declare(strict_types=1);

namespace GSU\D2L\DataHub\Extract\Logger;

interface ProgressLoggerAwareInterface
{
    /**
     * @param ProgressLoggerInterface $progressLogger
     * @return void
     */
    public function setProgressLogger(ProgressLoggerInterface $progressLogger): void;
}
