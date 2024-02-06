<?php

declare(strict_types=1);

namespace GSU\D2L\DataHub\Extract\Exception;

final class ProcessExtractException extends \RuntimeException
{
    /**
     * @inheritdoc
     */
    public function __construct(
        string $message = "",
        \Throwable|null $previous = null
    ) {
        parent::__construct(
            $message,
            0,
            $previous
        );
    }
}
