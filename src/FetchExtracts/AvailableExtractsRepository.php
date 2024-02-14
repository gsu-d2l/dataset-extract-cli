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
use mjfklib\Logger\LoggerAwareTrait;
use Psr\Log\LoggerAwareInterface;

final class AvailableExtractsRepository implements AvailableExtractsRepositoryInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;


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
     * @inheritdoc
     */
    public function getAvailableExtract(array $datasets): array
    {
        return array_filter(
            array_merge(
                ...array_map(
                    [$this, 'createExtractList'],
                    array_values($this->getBDS($datasets))
                )
            ),
            fn (ExtractBaseInfo $e): bool => $this->schemaRepo->existsDataset(
                DatasetSchemaType::BDS,
                $e->datasetName
            )
        );
    }


    /**
     * @param string[] $datasets
     * @return BDSInfo[]
     */
    private function getBDS(array $datasets): array
    {
        $bds = [];
        $bdsInfoNext = null;

        do {
            $bdsInfoList = $this->dataHubAPI->getBDSInfo($bdsInfoNext);
            $bdsInfoNext = $bdsInfoList->Next;

            foreach ($bdsInfoList->Objects as $bdsInfo) {
                $datasetName = DatasetSchema::getName($bdsInfo->Name);
                if (!in_array(DatasetSchema::getName($datasetName), $datasets, true)) {
                    continue;
                }

                try {
                    $bdsExtractNext = null;

                    do {
                        $bdsExtractList = $this->dataHubAPI->getBDSExtracts($bdsInfo->SchemaId, null, $bdsExtractNext);
                        $bdsExtractNext = $bdsExtractList->Next;

                        foreach ($bdsExtractList->Objects as $bdsExtract) {
                            $createdDate = $bdsExtract->CreatedDate->format(\DateTimeInterface::ATOM);
                            switch ($bdsExtract->BdsType) {
                                case 'Full':
                                    if ($bdsInfo->Full !== null) {
                                        $bdsInfo->Full->Extracts[$createdDate] = $bdsExtract;
                                    }
                                    break;
                                case 'Differential':
                                    if ($bdsInfo->Differential !== null) {
                                        $bdsInfo->Differential->Extracts[$createdDate] = $bdsExtract;
                                    }
                                    break;
                            }
                        }
                    } while (is_string($bdsExtractNext));

                    if ($bdsInfo->Full !== null) {
                        ksort($bdsInfo->Full->Extracts);
                    }
                    if ($bdsInfo->Differential !== null) {
                        ksort($bdsInfo->Differential->Extracts);
                    }

                    $this->logger?->info('(fetch) ' . $this->formatLogResults([
                        'dataset' => $datasetName,
                        'extracts' => count($bdsInfo->Full?->Extracts ?? [])
                            + count($bdsInfo->Differential?->Extracts ?? [])
                    ]));

                    $bds[$bdsInfo->Name] = $bdsInfo;
                } catch (\Throwable $t) {
                    $this->logger?->error("Unable to download extracts for dataset: {$bdsInfo->Name}", [$t]);
                    //throw new \RuntimeException("Unable to download extracts for dataset: {$bdsInfo->Name}", 0, $t);
                }
            }
        } while (is_string($bdsInfoNext));

        ksort($bds);

        return $bds;
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
