<?php

declare(strict_types=1);

namespace Crell\Serde\Extractor;

use Crell\Serde\JsonFormatter;

class DateTimeExtractor implements Extractor, Injector
{
    /**
     * @param JsonFormatter $formatter
     * @param string $format
     * @param string $name
     * @param \DateTimeInterface $value
     * @param string $type
     * @param mixed $runningValue
     * @return mixed
     */
    public function extract(
        JsonFormatter $formatter,
        string $format,
        string $name,
        mixed $value,
        string $type,
        mixed $runningValue
    ): mixed {
        $string = $value->format(\DateTimeInterface::RFC3339_EXTENDED);
        return $formatter->serializeString($runningValue, $name, $string);
    }

    public function supportsExtract(string $type, mixed $value, string $format): bool
    {
        return $value instanceof \DateTimeInterface;
    }

    public function getValue(JsonFormatter $formatter, string $format, mixed $source, string $name, string $type): mixed
    {
        $string = $formatter->deserializeString($source, $name);

        return new $type($string);
    }

    public function supportsInject(string $type, string $format): bool
    {
        return in_array($type, [\DateTimeInterface::class, \DateTime::class, \DateTimeImmutable::class]);
    }


}
