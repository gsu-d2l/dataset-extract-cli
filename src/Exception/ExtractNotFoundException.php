<?php

declare(strict_types=1);

namespace GSU\D2L\DataHub\Extract\Exception;

final class ExtractNotFoundException extends \RuntimeException
{
    /**
     * @param string $extract
     * @param \Throwable|null $previous
     */
    public function __construct(
        string $extract,
        \Throwable|null $previous = null
    ) {
        parent::__construct(
            $extract,
            0,
            $previous
        );
    }
}
