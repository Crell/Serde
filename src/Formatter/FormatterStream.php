<?php

declare(strict_types=1);

namespace Crell\Serde\Formatter;

use Crell\fp\Newable;

class FormatterStream
{
    use Newable;

    private bool $namedContext = false;

    public function __construct(
        public mixed $stream,
    ) {}

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
     * @param string $data
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
     * @param mixed ...$args
     * @return int|false
     *   The return value from fwrite().
     */
    public function printf(string $format, mixed ...$args): int|false
    {
        return $this->write(sprintf($format, ...$args));
    }
}
