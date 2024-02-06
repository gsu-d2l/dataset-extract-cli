<?php

declare(strict_types=1);

namespace GSU\D2L\DataHub\Extract\Utils;

use GSU\D2L\DataHub\Schema\Model\ColumnSchema;

final class ColumnMapper
{
    /**
     * @param array<int,string|ColumnSchema> $inputColumns
     * @param array<int,ColumnSchema> $outputColumns
     * @return array{0:int,1:int,2:ColumnSchema}[]
     */
    public static function buildColumnMap(
        array $inputColumns,
        array $outputColumns
    ): array {
        /** @var array<string,int> $inColumns */
        $inColumns = array_flip(array_map(
            fn (string|ColumnSchema $c): string => strtoupper($c instanceof ColumnSchema ? $c->name : $c),
            $inputColumns
        ));

        $outColumns = [];
        foreach ($outputColumns as $outIndex => $outColumn) {
            $inIndex = $inColumns[strtoupper($outColumn->name)] ?? null;
            $outColumns[] = (is_int($inIndex) || !$outColumn->isPrimary)
                ? [$inIndex ?? -1, $outIndex, $outColumn]
                : throw new \OutOfRangeException("Missing column: {$outColumn->name}");
        }

        /** @var array{0:int,1:int,2:ColumnSchema}[] $outColumns */
        return $outColumns;
    }
}
