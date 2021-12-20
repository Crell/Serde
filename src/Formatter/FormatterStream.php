<?php

declare(strict_types=1);

namespace Crell\Serde\Formatter;

class FormatterStream
{
    private bool $namedContext = false;

    public function __construct(
        public mixed $stream,
    ) {}

    public static function new(...$args): static
    {
        return new static(...$args);
    }

    public function namedContext(): static
    {
        $new = clone($this);
        $new->namedContext = true;
        return $new;
    }

    public function unnamedContext(): static
    {
        $new = clone($this);
        $new->namedContext = false;
        return $new;
    }

    public function isNamedContext(): bool
    {
        return $this->namedContext;
    }

    /**
     * Wrapper for writing to the stream.
     *
     * @param mixed $data
     *   The data to write.  It will be passed verbatim to fwrite().
     * @return int|false
     *   The value returned from fwrite();
     */
    public function write(string $data): int|false
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
