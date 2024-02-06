<?php

declare(strict_types=1);

namespace GSU\D2L\DataHub\Extract\ProcessExtract;

use GSU\D2L\DataHub\Extract\Logger\ProgressLoggerAwareInterface;
use GSU\D2L\DataHub\Extract\Model\ExtractContext;
use GSU\D2L\DataHub\Extract\Model\ExtractInfo;

interface ExtractProcessorInterface extends ProgressLoggerAwareInterface
{
    /**
     * @param ExtractInfo $extract
     * @param bool $force
     * @return ExtractContext|null
     */
    public function processFull(
        ExtractInfo $extract,
        bool $force
    ): ExtractContext|null;


    /**
     * @param ExtractInfo $extract
     * @param bool $force
     * @return ExtractContext|null
     */
    public function processFullDiff(
        ExtractInfo $extract,
        bool $force
    ): ExtractContext|null;


    /**
     * @param ExtractInfo $extract
     * @param bool $force
     * @return ExtractContext|null
     */
    public function processDiff(
        ExtractInfo $extract,
        bool $force
    ): ExtractContext|null;
}
