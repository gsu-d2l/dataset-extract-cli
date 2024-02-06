<?php

declare(strict_types=1);

namespace GSU\D2L\DataHub\Extract\Exception;

use GSU\D2L\DataHub\Extract\Model\ExtractBaseInfo;

final class CreateExtractException extends \RuntimeException
{
    /**
     * @param string|ExtractBaseInfo $extract
     * @param \Throwable|null $previous
     */
    public function __construct(
        string|ExtractBaseInfo $extract,
        \Throwable|null $previous = null
    ) {
        parent::__construct(
            strval($extract),
            0,
            $previous
        );
    }
}
