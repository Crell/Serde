<?php

declare(strict_types=1);

namespace Crell\Serde\Formatter;

class FormatterStream
{
    public bool $root = true;

    public function __construct(
        public mixed $stream,
    ) {}

    public static function new(...$args): static
    {
        return new static(...$args);
    }

    /**
     * Wrapper for writing to the stream.
     *
     * @param mixed $data
     *   The data to write.  It will be passed verbatim to fwrite().
     * @return int|false
     *   The value returned from fwrite();
     */
    public function write(mixed $data): int|false
    {
        return fwrite($this->stream, $data);
    }

    /**
     * Wrapper for write() that accepts printf() syntax.
     *
     * @param string $format
     * @param ...$args
     * @return static
     *   The called object.
     */
    public function printf(string $format, ...$args): static
    {
        $this->write(sprintf($format, ...$args));
        return $this;
    }
}
