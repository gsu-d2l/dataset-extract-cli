<?php

declare(strict_types=1);

namespace GSU\D2L\DataHub\Extract\Exception;

use GSU\D2L\DataHub\Extract\Model\ExtractInfo;
use GSU\D2L\DataHub\Extract\Model\ExtractType;

final class InvalidExtractTypeException extends \RuntimeException
{
    /**
     * @param ExtractInfo $extract
     * @param ExtractType $extractType
     */
    public function __construct(
        ExtractInfo $extract,
        ExtractType $extractType
    ) {
        parent::__construct("{$extract} - Type is {$extract->type->value}, expected {$extractType->value}");
    }
}
