<?php

declare(strict_types=1);

namespace GSU\D2L\DataHub\Extract\Model;

enum ExtractType: string
{
    case FULL = "Full";
    case DIFF = "Diff";

    /**
     * @param string|self $value
     * @return self
     */
    public static function getType(string|self $value): self
    {
        return $value instanceof self
            ? $value
            : self::from(ucfirst(strtolower(substr($value, 0, 4))));
    }
}
