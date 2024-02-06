<?php

declare(strict_types=1);

namespace GSU\D2L\DataHub\Extract\Exception;

use GSU\D2L\DataHub\Extract\Model\ExtractPath;

final class FileNotFoundException extends \RuntimeException
{
    /**
     * @param string|ExtractPath $path
     */
    public function __construct(string|ExtractPath $path)
    {
        parent::__construct(strval($path));
    }
}
