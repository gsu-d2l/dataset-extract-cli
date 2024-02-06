<?php

declare(strict_types=1);

namespace GSU\D2L\DataHub\Extract\ProcessExtract\Oracle;

use GSU\D2L\DataHub\Extract\Model\ExtractInfo;
use GSU\D2L\DataHub\Schema\Model\ColumnSchema;

final class OracleProcessorParams
{
    public string $tableName = '';


    /**
     * @param ExtractInfo $extract
     * @param array<int,ColumnSchema> $columns
     * @param GetLoadTable $getLoadTable
     * @param OpenInputFile $openInputFile
     * @param GetSqlContents $getSqlContents
     */
    public function __construct(
        public ExtractInfo $extract,
        public array $columns,
        public GetLoadTable $getLoadTable,
        public OpenInputFile $openInputFile,
        public GetSqlContents $getSqlContents
    ) {
    }
}
