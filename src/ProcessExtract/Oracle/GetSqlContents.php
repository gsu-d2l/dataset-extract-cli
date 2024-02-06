<?php

declare(strict_types=1);

namespace GSU\D2L\DataHub\Extract\ProcessExtract\Oracle;

use GSU\D2L\DataHub\Schema\Model\ColumnSchema;

final class GetSqlContents
{
    /**
     * @return self
     */
    public static function getSqlContentsFull(): self
    {
        return new self(fn(string $table, array $columns) => self::__getSqlContentsFull(
            $table,
            $columns
        ));
    }


    /**
     * @return self
     */
    public static function getSqlContentsFullDiff(): self
    {
        return new self(fn(string $table, array $columns) => self::__getSqlContentsFullDiff(
            $table,
            $columns
        ));
    }


    /**
     * @return self
     */
    public static function getSqlContentsDiff(): self
    {
        return new self(fn($table, $columns) => self::__getSqlContentsDiff(
            $table,
            $columns
        ));
    }


    /**
     * @param string $table
     * @param array<int,ColumnSchema> $columns
     * @return string[]
     */
    private static function __getSqlContentsFull(
        string $table,
        array $columns
    ): array {
        return ["TRUNCATE TABLE {$table}_LOAD"];
    }


    /**
     * @param string $table
     * @param array<int,ColumnSchema> $columns
     * @return string[]
     */
    private static function __getSqlContentsFullDiff(
        string $table,
        array $columns
    ): array {
        $primary = array_map(
            fn (ColumnSchema $c): string => sprintf(
                '  s.%1$s = t.%1$s',
                OracleProcessor::getTableColumnName($c->name)
            ),
            array_filter(
                $columns,
                fn (ColumnSchema $c): bool => $c->isPrimary
            )
        );

        return [
            "DELETE FROM",
            "  {$table} t",
            "WHERE",
            "  EXISTS(",
            "    SELECT",
            "      1",
            "    FROM",
            "      {$table}_LOAD s",
            "    WHERE",
            implode(" AND\n", $primary),
            "  )",
            ";",
        ];
    }


    /**
     * @param string $table
     * @param array<int,ColumnSchema> $columns
     * @return string[]
     */
    private static function __getSqlContentsDiff(
        string $table,
        array $columns
    ): array {
        $primary = array_map(
            fn (ColumnSchema $c): string => sprintf(
                '  t.%1$s = s.%1$s',
                OracleProcessor::getTableColumnName($c->name)
            ),
            array_filter(
                $columns,
                fn (ColumnSchema $c): bool => $c->isPrimary
            )
        );

        $nonPrimary = array_map(
            fn (ColumnSchema $c): string => sprintf(
                '  t.%1$s = s.%1$s',
                OracleProcessor::getTableColumnName($c->name)
            ),
            array_filter(
                $columns,
                fn (ColumnSchema $c): bool => !$c->isPrimary
            )
        );

        return [
            "MERGE INTO {$table} t",
            "USING {$table}_LOAD s",
            "ON (",
            implode(" AND\n", $primary),
            ")",
            ...(
                (count($nonPrimary) > 0)
                    ? [
                        "WHEN MATCHED THEN UPDATE SET",
                        implode(",\n", $nonPrimary)
                    ]
                    : []
            ),
            "WHEN NOT MATCHED THEN",
            "INSERT (",
            implode(",\n", array_map(
                fn (ColumnSchema $c): string => sprintf(
                    '  t.%s',
                    OracleProcessor::getTableColumnName($c->name)
                ),
                $columns
            )),
            ")",
            "VALUES (",
            implode(",\n", array_map(
                fn (ColumnSchema $c): string => sprintf(
                    '  s.%s',
                    OracleProcessor::getTableColumnName($c->name)
                ),
                $columns
            )),
            ");"
        ];
    }


    /**
     * @param (callable(string $tableName, array<int,ColumnSchema> $outputColumns):string[]) $callable
     */
    public function __construct(private mixed $callable)
    {
    }


    /**
     * @param string $tableName
     * @param array<int,ColumnSchema> $outputColumns
     * @return string[]
     */
    public function __invoke(string $tableName, array $outputColumns): array
    {
        return ($this->callable)(
            $tableName,
            $outputColumns
        );
    }
}
