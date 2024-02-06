<?php

declare(strict_types=1);

namespace GSU\D2L\DataHub\Extract\BuildIndex;

use GSU\D2L\DataHub\Extract\Logger\ProgressLoggerAwareInterface;
use GSU\D2L\DataHub\Extract\Model\ExtractInfo;

interface IndexBuilderInterface extends ProgressLoggerAwareInterface
{
    /** @var int */
    public const DEFAULT_CHUNK_SIZE = 50000;


    /**
     * @param ExtractInfo $extract
     * @param bool $force
     * @param int $chunkSize
     * @return int
     */
    public function buildIndex(
        ExtractInfo $extract,
        bool $force,
        int $chunkSize = self::DEFAULT_CHUNK_SIZE
    ): int;
}
