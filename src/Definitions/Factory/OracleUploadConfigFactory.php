<?php

declare(strict_types=1);

namespace GSU\D2L\DataHub\Extract\Definitions\Factory;

use GSU\D2L\DataHub\Extract\UploadExtract\Oracle\OracleUploadConfig;
use mjfklib\Container\Env;
use mjfklib\Utils\ArrayValue;

final class OracleUploadConfigFactory
{
    /**
     * @param Env $env
     * @return OracleUploadConfig
     */
    public static function createOracleUploadConfig(Env $env): OracleUploadConfig
    {
        $values = ArrayValue::convertToArray($env->getArrayCopy());
        return new OracleUploadConfig(
            processDir: ArrayValue::getString($values, ['processDir', 'PROCESS_DIR']),
            uploadDir: ArrayValue::getString($values, ['uploadDir', 'UPLOAD_DIR']),
            userId: ArrayValue::getString($values, ['userId', 'ORACLE_USER_ID'])
        );
    }
}
