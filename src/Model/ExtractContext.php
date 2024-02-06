<?php

declare(strict_types=1);

namespace GSU\D2L\DataHub\Extract\Model;

use mjfklib\Utils\ArrayValue;

final class ExtractContext implements \Stringable, \JsonSerializable
{
    /**
     * @param ExtractInfo $extract
     * @param ExtractPath $path
     * @return self
     */
    public static function create(
        ExtractInfo $extract,
        ExtractPath $path
    ): self {
        $values = ArrayValue::convertToArray($path->getPath());
        return new self(
            extract: $extract,
            path: $path,
            processType: ExtractProcessType::getType(ArrayValue::getString($values, 'processType')),
            files: ArrayValue::getStringArray($values, 'files'),
            recordCount: ArrayValue::getInt($values, 'recordCount'),
            started: ArrayValue::getInt($values, 'started'),
            finished: ArrayValue::getInt($values, 'finished')
        );
    }


    /**
     * @param ExtractInfo $extract
     * @param ExtractProcessType $processType
     * @param string[] $files
     * @param int $recordCount
     */
    public function __construct(
        public readonly ExtractInfo $extract,
        public readonly ExtractPath $path,
        public readonly ExtractProcessType $processType,
        public array $files = [],
        public int $recordCount = 0,
        public int $started = 0,
        public int $finished = 0
    ) {
    }


    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->path->getPath();
    }


    /**
     * @return mixed
     */
    public function jsonSerialize(): mixed
    {
        return [
            'processType' => $this->processType->value,
            'files' => $this->files,
            'recordCount' => $this->recordCount,
            'started' => $this->started,
            'finished' => $this->finished
        ];
    }
}
