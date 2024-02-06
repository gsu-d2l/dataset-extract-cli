<?php

declare(strict_types=1);

namespace GSU\D2L\DataHub\Extract\BuildFullDiff;

use GSU\D2L\DataHub\Extract\Logger\ProgressLoggerAwareInterface;
use GSU\D2L\DataHub\Extract\Model\ExtractInfo;

interface FullDiffBuilderInterface extends ProgressLoggerAwareInterface
{
    /**
     * @param ExtractInfo $current
     * @param bool $force
     * @return int
     */
    public function buildFullDiff(
        ExtractInfo $current,
        bool $force
    ): int;
}
