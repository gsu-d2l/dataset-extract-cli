<?php

declare(strict_types=1);

namespace GSU\D2L\DataHub\Extract\ListExtracts;

use GSU\D2L\DataHub\Extract\Model\ExtractStatus;
use GSU\D2L\DataHub\Extract\Model\ExtractType;
use mjfklib\Utils\ArrayValue;

final class ListExtractsParams
{
    /**
     * @param mixed $values
     * @return self
     */
    public static function create(mixed $values): self
    {
        $values = ArrayValue::convertToArray($values);
        $datasets = $values['datasets'] ?? null;
        $types = $values['types'] ?? null;
        $status = $values['status'] ?? null;

        return new self(
            datasets: match (true) {
                is_string($datasets), is_scalar($datasets) => ArrayValue::getString($values, 'datasets'),
                is_array($datasets) => ArrayValue::getStringArrayNull($values, 'datasets'),
                default => null
            },
            startDate: self::getDate(ArrayValue::getStringNull($values, 'startDate'), '000000'),
            endDate: self::getDate(ArrayValue::getStringNull($values, 'endDate'), '235959'),
            types: match (true) {
                $types instanceof ExtractType => $types,
                is_array($types) => array_map(
                    fn (string $t) => ExtractType::getType($t),
                    ArrayValue::getStringArray($values, 'types')
                ),
                default => null
            },
            status: ($status instanceof ExtractStatus || $status === null)
                ? $status
                : ExtractStatus::getType(ArrayValue::getString($values, 'status')),
            showDatasetsOnly: ArrayValue::getBoolNull($values, 'showDatasetsOnly') ?? false,
        );
    }


    /**
     * @param string|null $value
     * @param string $time
     * @return \DateTimeImmutable|null
     * @throws \InvalidArgumentException
     */
    private static function getDate(
        string|null $value,
        string $time
    ): \DateTimeImmutable|null {
        $dateValue = (is_string($value))
            ? \DateTimeImmutable::createFromFormat('YmdHis', substr(strval($value), 0, 8) . $time)
            : null;

        return $dateValue instanceof \DateTimeImmutable || $dateValue === null
            ? $dateValue
            : throw new \InvalidArgumentException("Invalid date: {$value}");
    }


    /**
     * @param string|string[]|null $datasets
     * @param \DateTimeInterface|null $startDate
     * @param \DateTimeInterface|null $endDate
     * @param ExtractType|ExtractType[]|null $types
     * @param ExtractStatus|null $status
     * @param bool $showDatasetsOnly
     */
    public function __construct(
        public string|array|null $datasets = null,
        public \DateTimeInterface|null $startDate = null,
        public \DateTimeInterface|null $endDate = null,
        public ExtractType|array|null $types = null,
        public ExtractStatus|null $status = null,
        public bool $showDatasetsOnly = false
    ) {
    }
}
