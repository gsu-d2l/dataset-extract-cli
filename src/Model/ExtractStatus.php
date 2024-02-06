<?php

declare(strict_types=1);

namespace GSU\D2L\DataHub\Extract\Model;

enum ExtractStatus: string
{
    case AVAILABLE = "Available";
    case DOWNLOADED = "Downloaded";
    case PROCESSED = "Processed";
    case UPLOADED = "Uploaded";

    /**
     * @param string|self $value
     * @return self
     */
    public static function getType(string|self $value): self
    {
        return $value instanceof self ? $value : self::from(ucfirst(strtolower($value)));
    }
}
