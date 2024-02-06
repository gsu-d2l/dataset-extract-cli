<?php

declare(strict_types=1);

namespace GSU\D2L\DataHub\Extract\Repository;

use GSU\D2L\DataHub\Extract\Model\ExtractBaseInfo;
use GSU\D2L\DataHub\Extract\Model\ExtractInfo;
use GSU\D2L\DataHub\Extract\Model\ExtractStatus;
use GSU\D2L\DataHub\Extract\Model\ExtractType;

interface ExtractRepositoryInterface
{
    public const EXTRACT_FILE_EXT = 'json';
    public const DOWNLOAD_FILE_EXT = 'zip';
    public const INDEX_FILE_EXT = 'idx';
    public const FULLDIFF_FILE_EXT = 'diff';
    public const PROCESS_FILE_EXT = 'json';
    public const UPLOAD_FILE_EXT = 'json';


    /**
     * @param string|ExtractInfo $extract
     * @param string $fileExt
     * @return string
     */
    public function getExtractPath(
        string|ExtractInfo $extract,
        string $fileExt = self::EXTRACT_FILE_EXT
    ): string;


    /**
     * @param string|ExtractInfo $extract
     * @param string $fileExt
     * @return string
     */
    public function getDownloadPath(
        string|ExtractInfo $extract,
        string $fileExt = self::DOWNLOAD_FILE_EXT
    ): string;


    /**
     * @param string|ExtractInfo $extract
     * @param string $fileExt
     * @return string
     */
    public function getIndexPath(
        string|ExtractInfo $extract,
        string $fileExt = self::INDEX_FILE_EXT
    ): string;


    /**
     * @param string|ExtractInfo $extract
     * @param string $fileExt
     * @return string
     */
    public function getFullDiffPath(
        string|ExtractInfo $extract,
        string $fileExt = self::FULLDIFF_FILE_EXT
    ): string;


    /**
     * @param string|ExtractInfo $extract
     * @param string $fileExt
     * @return string
     */
    public function getProcessPath(
        string|ExtractInfo $extract,
        string $fileExt = self::PROCESS_FILE_EXT
    ): string;


    /**
     * @param string|ExtractInfo $extract
     * @param string $fileExt
     * @return string
     */
    public function getUploadPath(
        string|ExtractInfo $extract,
        string $fileExt = self::UPLOAD_FILE_EXT
    ): string;


    /**
     * @param ExtractBaseInfo $extract
     * @return ExtractInfo
     */
    public function createExtract(ExtractBaseInfo $extract): ExtractInfo;


    /**
     * @param string|string[] $datasets
     * @param \DateTimeInterface|null $startDate
     * @param \DateTimeInterface|null $endDate
     * @param ExtractType|ExtractType[] $types
     * @param ExtractStatus|ExtractStatus[] $status
     * @return ExtractInfo[]
     */
    public function getExtracts(
        string|array $datasets = [],
        \DateTimeInterface|null $startDate = null,
        \DateTimeInterface|null $endDate = null,
        ExtractType|array $types = [],
        ExtractStatus|array $status = []
    ): array;


    /**
     * @param string $extract
     * @return ExtractInfo
     */
    public function getExtract(string $extract): ExtractInfo;


    /**
     * @param ExtractInfo|ExtractInfo[] $extracts
     * @param bool $force
     * @return int
     */
    public function storeExtracts(
        ExtractInfo|array $extracts,
        bool $force
    ): int;


    /**
     * @param ExtractInfo|ExtractInfo[] $extracts
     * @return int
     */
    public function deleteExtracts(ExtractInfo|array $extracts): int;


    /**
     * @return void
     */
    public function cleanUp(): void;
}
