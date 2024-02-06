<?php

declare(strict_types=1);

namespace GSU\D2L\DataHub\Extract\Exception;

use GSU\D2L\DataHub\Extract\Model\ExtractInfo;
use GSU\D2L\DataHub\Extract\Model\ExtractStatus;

final class InvalidExtractStatusException extends \RuntimeException
{
    /**
     * @param ExtractInfo $extract
     * @param ExtractStatus $status
     */
    public function __construct(
        ExtractInfo $extract,
        ExtractStatus $status
    ) {
        parent::__construct("{$extract} - status is {$extract->getStatus()->value}, expected {$status->value}");
    }
}
