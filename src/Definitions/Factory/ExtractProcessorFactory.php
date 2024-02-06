<?php

declare(strict_types=1);

namespace GSU\D2L\DataHub\Extract\Definitions\Factory;

use GSU\D2L\DataHub\Extract\Model\AppConfig;
use GSU\D2L\DataHub\Extract\Model\ExtractProcessType;
use GSU\D2L\DataHub\Extract\ProcessExtract\ExtractProcessorInterface;
use GSU\D2L\DataHub\Extract\ProcessExtract\MySQL\MySQLProcessor;
use GSU\D2L\DataHub\Extract\ProcessExtract\Oracle\OracleProcessor;
use Psr\Container\ContainerInterface;

final class ExtractProcessorFactory
{
    /**
     * @param AppConfig $config
     * @param ContainerInterface $container
     * @return ExtractProcessorInterface
     */
    public static function createExtractProcessor(
        AppConfig $config,
        ContainerInterface $container
    ): ExtractProcessorInterface {
        $processor = $container->get(match ($config->processType) {
            ExtractProcessType::ORACLE => OracleProcessor::class,
            ExtractProcessType::MYSQL => MySQLProcessor::class
        });

        return $processor instanceof ExtractProcessorInterface
            ? $processor
            : throw new \UnexpectedValueException("Processor does not implement ExtractProcessorInterface");
    }
}
