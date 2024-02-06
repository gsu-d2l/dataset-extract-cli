<?php

declare(strict_types=1);

namespace GSU\D2L\DataHub\Extract\ProcessExtract\Oracle;

use GSU\D2L\DataHub\Extract\Model\ExtractInfo;
use GSU\D2L\DataHub\Schema\Model\ColumnSchema;
use mjfklib\Utils\FileMethods;

final class OpenInputFile
{
    /**
     * @return self
     */
    public static function openInputFileFull(): self
    {
        return new self(
            function (ExtractInfo $e): array {
                list ($f, $s) = FileMethods::openZipFile($e->downloadPath->getPath());
                /** @var array<int,string|\GSU\D2L\DataHub\Schema\Model\ColumnSchema>|false|null $c*/
                $c = fgetcsv(stream: $s, escape: '"');
                return is_array($c)
                    ? [$f, $s, $c]
                    : throw new \UnexpectedValueException("Expected string array: {$e}");
            }
        );
    }


    /**
     * @return self
     */
    public static function openInputFileFullDiff(): self
    {
        return new self(
            function (ExtractInfo $e): array {
                list ($f, $s) = FileMethods::openZipFile($e->fullDiffPath->getPath(), false);
                return [$f, $s, $e->schema->getPrimaryColumns()];
            }
        );
    }


    /**
     * @return self
     */
    public static function openInputFileDiff(): self
    {
        return self::openInputFileFull();
    }


    /**
     * @param (callable(ExtractInfo $e): array{0:\ZipArchive,1:resource,2:array<int,string|ColumnSchema>}) $callable
     */
    public function __construct(private mixed $callable)
    {
    }


    /**
     * @param ExtractInfo $extract
     * @return array{0:\ZipArchive,1:resource,2:array<int,string|ColumnSchema>}
     */
    public function __invoke(ExtractInfo $extract): array
    {
        return ($this->callable)($extract);
    }
}
