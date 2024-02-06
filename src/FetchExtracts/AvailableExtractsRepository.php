<?php

declare(strict_types=1);

namespace GSU\D2L\DataHub\Extract\FetchExtracts;

use GSU\D2L\API\DataHub\DataHubAPI;
use GSU\D2L\API\DataHub\Model\BDSExtractInfo;
use GSU\D2L\API\DataHub\Model\BDSInfo;
use GSU\D2L\DataHub\Extract\Model\ExtractBaseInfo;
use GSU\D2L\DataHub\Schema\Model\DatasetSchema;
use GSU\D2L\DataHub\Schema\Model\DatasetSchemaType;
use GSU\D2L\DataHub\Schema\SchemaRepositoryInterface;

final class AvailableExtractsRepository implements AvailableExtractsRepositoryInterface
{
    /**
     * @param DataHubAPI $dataHubAPI
     * @param SchemaRepositoryInterface $schemaRepo
     */
    public function __construct(
        private DataHubAPI $dataHubAPI,
        private SchemaRepositoryInterface $schemaRepo
    ) {
    }


    /**
     * @return ExtractBaseInfo[]
     */
    public function getAvailableExtract(): array
    {
        return array_filter(
            array_merge(
                ...array_map(
                    [$this, 'createExtractList'],
                    array_values($this->dataHubAPI->getBDS())
                )
            ),
            fn (ExtractBaseInfo $e): bool => $this->schemaRepo->existsDataset(
                DatasetSchemaType::BDS,
                $e->datasetName
            )
        );
    }


    /**
     * @param BDSInfo $bdsInfo
     * @return ExtractBaseInfo[]
     */
    private function createExtractList(BDSInfo $bdsInfo): array
    {
        return array_map(
            fn (BDSExtractInfo $bdsExtractInfo) => new ExtractBaseInfo(
                extractName: ExtractBaseInfo::getExtractName(
                    DatasetSchema::getName($bdsInfo->Name),
                    $bdsExtractInfo->QueuedForProcessingDate,
                    $bdsExtractInfo->BdsType
                ),
                url: $bdsExtractInfo->DownloadLink,
                size: $bdsExtractInfo->DownloadSize
            ),
            array_merge(
                array_values($bdsInfo->Full?->Extracts ?? []),
                array_values($bdsInfo->Differential?->Extracts ?? []),
            )
        );
    }
}
