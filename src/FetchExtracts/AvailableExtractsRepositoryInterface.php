<?php

declare(strict_types=1);

namespace GSU\D2L\DataHub\Extract\FetchExtracts;

use GSU\D2L\DataHub\Extract\Model\ExtractBaseInfo;

interface AvailableExtractsRepositoryInterface
{
    /**
     * @return ExtractBaseInfo[]
     */
    public function getAvailableExtract(): array;
}
