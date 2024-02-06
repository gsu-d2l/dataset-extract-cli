<?php

declare(strict_types=1);

namespace GSU\D2L\DataHub\Extract\Model;

final class ExtractPath implements \Stringable
{
    /**
     * @param string $path
     */
    public function __construct(private string $path)
    {
    }


    /**
     * @return bool
     */
    public function exists(): bool
    {
        return is_file($this->path);
    }


    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }


    /**
     * @return int
     */
    public function getTimestamp(): int
    {
        $timestamp = filectime($this->path);
        return is_int($timestamp) ? $timestamp : -1;
    }


    /**
     * @return int
     */
    public function getSize(): int
    {
        $filesize = filesize($this->path);
        return is_int($filesize) ? $filesize : -1;
    }


    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->path;
    }
}
