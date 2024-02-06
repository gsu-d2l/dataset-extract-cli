<?php

declare(strict_types=1);

namespace GSU\D2L\DataHub\Extract\ProcessExtract\MySQL;

use GSU\D2L\DataHub\Extract\Model\ExtractInfo;
use GSU\D2L\DataHub\Schema\Model\ColumnSchema;
use mjfklib\Utils\FileMethods;

final class RuntimeParams
{
    public readonly ExtractInfo $extract;
    public readonly bool $force;
    public readonly string $tableName;
    public readonly string $inputPath;
    public readonly bool $inputStripBOM;
    /** @var array<int,ColumnSchema>|null $inputColumns */
    public readonly array|null $inputColumns;
    public readonly string $outputPath;
    /** @var array<int,ColumnSchema> $outputColumns */
    public readonly array $outputColumns;
    public readonly bool $useLoadTable;
    public readonly bool $insertIgnore;
    public readonly bool $insertOnDuplicateKeyUpdate;
    public int $recordCount;
    public int $started;

    private \ZipArchive|null $inputFile;
    /** @var resource|null $inputStream */
    private mixed $inputStream;
    /** @var resource|null $outputStream */
    private mixed $outputStream;


    /**
     * @param ExtractInfo $extract
     * @param bool $force
     * @param string $tableName
     * @param string $inputPath
     * @param bool $inputStripBOM
     * @param array<int,ColumnSchema>|null $inputColumns
     * @param string $outputPath
     * @param array<int,ColumnSchema> $outputColumns
     * @param bool $useLoadTable
     * @param bool $insertIgnore
     * @param bool $insertOnDuplicateKeyUpdate
     */
    public function __construct(
        ExtractInfo $extract,
        bool $force,
        string $tableName,
        string|null $inputPath = null,
        bool $inputStripBOM = true,
        array|null $inputColumns = null,
        string|null $outputPath = null,
        array|null $outputColumns = null,
        bool $useLoadTable = false,
        bool $insertIgnore = true,
        bool $insertOnDuplicateKeyUpdate = false
    ) {
        $this->extract = $extract;
        $this->force = $force;
        $this->tableName = $tableName;
        $this->inputPath = $inputPath ?? $extract->downloadPath->getPath();
        $this->inputStripBOM = $inputStripBOM;
        $this->inputColumns = $inputColumns;
        $this->outputPath = $outputPath ?? sprintf(
            '%s/%s.sql.gz',
            dirname($extract->processPath->getPath()),
            $extract->extractName
        );
        $this->outputColumns = $outputColumns ?? $extract->schema->columns;
        $this->useLoadTable = $useLoadTable;
        $this->insertIgnore = $insertIgnore;
        $this->insertOnDuplicateKeyUpdate = $insertOnDuplicateKeyUpdate;
        $this->recordCount = 0;
        $this->started = time();
        $this->inputFile = null;
        $this->inputStream = null;
        $this->outputStream = null;
    }


    public function __destruct()
    {
        if (($this->inputFile ?? null) instanceof \ZipArchive) {
            @$this->inputFile->close();
        }
        if (is_resource($this->inputStream ?? null)) {
            @fclose($this->inputStream);
        }
        if (is_resource($this->outputStream ?? null)) {
            @gzclose($this->outputStream);
        }
    }


    /**
     * @return \ZipArchive
     */
    public function getInputFile(): \ZipArchive
    {
        if ($this->inputFile === null) {
            list (
                $this->inputFile,
                $this->inputStream
            ) = FileMethods::openZipFile(
                $this->inputPath,
                $this->inputStripBOM
            );
        }
        return $this->inputFile;
    }


    /**
     * @return resource
     */
    public function getInputStream(): mixed
    {
        if ($this->inputStream === null) {
            list (
                $this->inputFile,
                $this->inputStream
            ) = FileMethods::openZipFile(
                $this->inputPath,
                $this->inputStripBOM
            );
        }
        return $this->inputStream;
    }


    /**
     * @return resource
     */
    public function getOutputStream(): mixed
    {
        if ($this->outputStream === null) {
            $this->outputStream = FileMethods::openGzipFile($this->outputPath);
        }
        return $this->outputStream;
    }
}
