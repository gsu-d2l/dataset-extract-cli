<?php

declare(strict_types=1);

namespace GSU\D2L\DataHub\Extract\Definitions\Factory;

use GSU\D2L\DataHub\Extract\UploadExtract\MySQL\MySQLUploadConfig;
use mjfklib\Container\Env;
use mjfklib\Utils\ArrayValue;

final class MySQLUploadConfigFactory
{
    /**
     * @param Env $env
     * @return MySQLUploadConfig
     */
    public static function createMySQLUploadConfig(Env $env): MySQLUploadConfig
    {
        $values = ArrayValue::convertToArray($env->getArrayCopy());
        return new MySQLUploadConfig(
            processDir: ArrayValue::getString($values, ['processDir', 'PROCESS_DIR']),
            uploadDir: ArrayValue::getString($values, ['uploadDir', 'UPLOAD_DIR']),
            database: ArrayValue::getString($values, ['database', 'MYSQL_DATABASE']),
            options: ArrayValue::getStringNull($values, ['options', 'MYSQL_OPTIONS']) ?? ''
        );
    }
}
