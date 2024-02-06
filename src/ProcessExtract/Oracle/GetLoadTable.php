<?php

declare(strict_types=1);

namespace GSU\D2L\DataHub\Extract\ProcessExtract\Oracle;

final class GetLoadTable
{
    /**
     * @return self
     */
    public static function getLoadTableFull(): self
    {
        return new self(fn(string $table) => $table);
    }


    /**
     * @return self
     */
    public static function getLoadTableFullDiff(): self
    {
        return self::getLoadTableDiff();
    }


    /**
     * @return self
     */
    public static function getLoadTableDiff(): self
    {
        return new self(fn(string $table) => "{$table}_LOAD");
    }


    /**
     * @param (callable(string $tableName): string) $callable
     */
    public function __construct(private mixed $callable)
    {
    }


    /**
     * @param string $tableName
     * @return string
     */
    public function __invoke(string $tableName): string
    {
        return ($this->callable)($tableName);
    }
}
