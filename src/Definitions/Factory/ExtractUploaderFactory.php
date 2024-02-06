<?php

declare(strict_types=1);

namespace GSU\D2L\DataHub\Extract\Definitions\Factory;

use GSU\D2L\DataHub\Extract\Model\AppConfig;
use GSU\D2L\DataHub\Extract\Model\ExtractProcessType;
use GSU\D2L\DataHub\Extract\UploadExtract\ExtractUploaderInterface;
use GSU\D2L\DataHub\Extract\UploadExtract\MySQL\MySQLUploader;
use GSU\D2L\DataHub\Extract\UploadExtract\Oracle\OracleUploader;
use Psr\Container\ContainerInterface;

final class ExtractUploaderFactory
{
    /**
     * @param AppConfig $config
     * @param ContainerInterface $container
     * @return ExtractUploaderInterface
     */
    public static function createExtractUploader(
        AppConfig $config,
        ContainerInterface $container
    ): ExtractUploaderInterface {
        $uploader = $container->get(match ($config->processType) {
            ExtractProcessType::ORACLE => OracleUploader::class,
            ExtractProcessType::MYSQL => MySQLUploader::class
        });

        return $uploader instanceof ExtractUploaderInterface
            ? $uploader
            : throw new \UnexpectedValueException("Processor does not implement ExtractProcessorInterface");
    }
}
