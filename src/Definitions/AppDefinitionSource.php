<?php

declare(strict_types=1);

namespace GSU\D2L\DataHub\Extract\Definitions;

use GSU\D2L\API\D2LAPIDefinitionSource;
use GSU\D2L\DataHub\Extract\BuildFullDiff\FullDiffBuilder;
use GSU\D2L\DataHub\Extract\BuildFullDiff\FullDiffBuilderInterface;
use GSU\D2L\DataHub\Extract\BuildIndex\IndexBuilder;
use GSU\D2L\DataHub\Extract\BuildIndex\IndexBuilderInterface;
use GSU\D2L\DataHub\Extract\Definitions\Factory\AppConfigFactory;
use GSU\D2L\DataHub\Extract\Definitions\Factory\ExtractProcessorFactory;
use GSU\D2L\DataHub\Extract\Definitions\Factory\ExtractUploaderFactory;
use GSU\D2L\DataHub\Extract\Definitions\Factory\MySQLUploadConfigFactory;
use GSU\D2L\DataHub\Extract\Definitions\Factory\OracleUploadConfigFactory;
use GSU\D2L\DataHub\Extract\DownloadExtract\ExtractDownloader;
use GSU\D2L\DataHub\Extract\DownloadExtract\ExtractDownloaderInterface;
use GSU\D2L\DataHub\Extract\FetchExtracts\AvailableExtractsRepository;
use GSU\D2L\DataHub\Extract\FetchExtracts\AvailableExtractsRepositoryInterface;
use GSU\D2L\DataHub\Extract\Logger\LogProcessor;
use GSU\D2L\DataHub\Extract\Model\AppConfig;
use GSU\D2L\DataHub\Extract\ProcessExtract\ExtractProcessorInterface;
use GSU\D2L\DataHub\Extract\Repository\ExtractRepository;
use GSU\D2L\DataHub\Extract\Repository\ExtractRepositoryInterface;
use GSU\D2L\DataHub\Extract\ShellCommand\ShellCommand;
use GSU\D2L\DataHub\Extract\ShellCommand\ShellCommandInterface;
use GSU\D2L\DataHub\Extract\UploadExtract\ExtractUploaderInterface;
use GSU\D2L\DataHub\Extract\UploadExtract\MySQL\MySQLUploadConfig;
use GSU\D2L\DataHub\Extract\UploadExtract\Oracle\OracleUploadConfig;
use GSU\D2L\DataHub\Schema\SchemaDefinitionSource;
use mjfklib\Container\DefinitionSource;
use mjfklib\Container\Env;
use Monolog\Processor\ProcessorInterface;

final class AppDefinitionSource extends DefinitionSource
{
    /**
     * @inheritdoc
     */
    protected function createDefinitions(Env $env): array
    {
        return [
            FullDiffBuilderInterface::class => self::get(FullDiffBuilder::class),
            IndexBuilderInterface::class => self::get(IndexBuilder::class),
            ExtractDownloaderInterface::class => self::get(ExtractDownloader::class),
            AvailableExtractsRepositoryInterface::class => self::get(AvailableExtractsRepository::class),
            AppConfig::class => self::factory([
                AppConfigFactory::class,
                'createAppConfig'
            ]),
            ExtractProcessorInterface::class => self::factory([
                ExtractProcessorFactory::class,
                'createExtractProcessor'
            ]),
            ExtractRepositoryInterface::class => self::get(ExtractRepository::class),
            ExtractUploaderInterface::class => self::factory([
                ExtractUploaderFactory::class,
                'createExtractUploader'
            ]),
            OracleUploadConfig::class => self::factory([
                OracleUploadConfigFactory::class,
                'createOracleUploadConfig'
            ]),
            MySQLUploadConfig::class => self::factory([
                MySQLUploadConfigFactory::class,
                'createMySQLUploadConfig'
            ]),
            ShellCommandInterface::class => self::get(ShellCommand::class),
            ProcessorInterface::class => static::get(LogProcessor::class)
        ];
    }


    /**
     * @inheritdoc
     */
    public function getSources(): array
    {
        return [
            D2LAPIDefinitionSource::class,
            SchemaDefinitionSource::class
        ];
    }
}
