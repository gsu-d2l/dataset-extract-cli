<?php

declare(strict_types=1);

namespace GSU\D2L\DataHub\Extract\Model;

use mjfklib\Utils\ArrayValue;

class ExtractBaseInfo implements \Stringable, \JsonSerializable
{
    /**
     * @param string $extractPath
     * @return self
     */
    final public static function create(string $extractPath): self
    {
        $values = ArrayValue::convertToArray($extractPath);
        return new self(
            extractName: ArrayValue::getString($values, 'extractName'),
            url: ArrayValue::getString($values, 'url'),
            size: ArrayValue::getInt($values, 'size')
        );
    }


    /**
     * @param string $name
     * @return bool
     */
    final public static function isExtractName(string $name): bool
    {
        return preg_match('/^[a-zA-Z]+_[0-9]{8}_[0-9]{6}_[a-zA-Z]+$/', $name) === 1;
    }


    /**
     * @param string $datasetName
     * @param \DateTimeInterface $timestamp
     * @param string|ExtractType $type
     * @return string
     */
    final public static function getExtractName(
        string $datasetName,
        \DateTimeInterface $timestamp,
        string|ExtractType $type
    ): string {
        return sprintf(
            "%s_%s_%s",
            $datasetName,
            $timestamp->format('Ymd_His'),
            ExtractType::getType($type)->value
        );
    }


    /**
     * @param string $extractName
     * @return array{0:string,1:string,2:\DateTimeInterface,3:ExtractType}
     */
    final public static function explodeExtractName(string $extractName): array
    {
        if (!self::isExtractName($extractName)) {
            throw new \InvalidArgumentException("Invalid extract name: {$extractName}");
        }

        list(
            $datasetName,
            $date,
            $time,
            $type
        ) = explode("_", $extractName);

        $timestamp = \DateTimeImmutable::createFromFormat('YmdHis', $date . $time);
        if (!$timestamp instanceof \DateTimeInterface) {
            throw new \InvalidArgumentException("Invalid datetime: {$date},{$time}");
        }

        return [
            $extractName,
            $datasetName,
            $timestamp,
            ExtractType::getType($type)
        ];
    }


    public readonly string $extractName;
    public readonly string $datasetName;
    public readonly \DateTimeInterface $timestamp;
    public readonly ExtractType $type;
    public readonly string $url;
    public readonly int $size;


    /**
     * @param string $extractName
     * @param string $url
     * @param int $size
     */
    public function __construct(
        string $extractName,
        string $url,
        int $size,
    ) {
        list(
            $this->extractName,
            $this->datasetName,
            $this->timestamp,
            $this->type
        ) = self::explodeExtractName($extractName);
        $this->url = $url;
        $this->size = $size;
    }


    /**
     * @return string
     */
    final public function __toString(): string
    {
        return $this->extractName;
    }


    /**
     * @return mixed
     */
    final public function jsonSerialize(): mixed
    {
        return [
            'extractName' => $this->extractName,
            'url' => $this->url,
            'size' => $this->size
        ];
    }
}
