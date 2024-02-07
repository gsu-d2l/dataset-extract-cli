<?php

declare(strict_types=1);

namespace GSU\D2L\DataHub\Extract\Definitions\Factory;

use GSU\D2L\DataHub\Extract\Model\AppConfig;
use mjfklib\Container\Env;
use mjfklib\Utils\ArrayValue;

final class AppConfigFactory
{
    /**
     * @param Env $env
     * @return AppConfig
     */
    public static function createAppConfig(Env $env): AppConfig
    {
        $values = ArrayValue::convertToArray($env->getArrayCopy());
        return new AppConfig(
            appEnv: $env->appEnv,
            binDir: ArrayValue::getString($values, ['binDir', 'BIN_DIR']),
            availableDir: ArrayValue::getString($values, ['availableDir', 'AVAILABLE_DIR']),
            downloadDir: ArrayValue::getString($values, ['downloadDir', 'DOWNLOAD_DIR']),
            indexDir: ArrayValue::getString($values, ['indexDir', 'INDEX_DIR']),
            fullDiffDir: ArrayValue::getString($values, ['fullDiffDir', 'FULLDIFF_DIR']),
            processDir: ArrayValue::getString($values, ['processDir', 'PROCESS_DIR']),
            uploadDir: ArrayValue::getString($values, ['uploadDir', 'UPLOAD_DIR']),
            processType: ArrayValue::getString($values, ['processor', 'PROCESSOR'])
        );
    }
}
