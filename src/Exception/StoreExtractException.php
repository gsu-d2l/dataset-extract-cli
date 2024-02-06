<?php

declare(strict_types=1);

namespace GSU\D2L\DataHub\Extract\Exception;

use GSU\D2L\DataHub\Extract\Model\ExtractInfo;

final class StoreExtractException extends \RuntimeException
{
    /**
     * @param ExtractInfo $extract
     * @param \Throwable|null $previous
     */
    public function __construct(
        ExtractInfo $extract,
        \Throwable|null $previous = null
    ) {
        parent::__construct(
            $extract->extractName,
            0,
            $previous
        );
    }
}
