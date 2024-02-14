<?php

declare(strict_types=1);

namespace GSU\D2L\DataHub\Extract\FetchExtracts;

use GSU\D2L\DataHub\Extract\Model\ExtractBaseInfo;

interface AvailableExtractsRepositoryInterface
{
    /**
     * @param string[] $datasets
     * @return ExtractBaseInfo[]
     */
    public function getAvailableExtract(array $datasets): array;
}
