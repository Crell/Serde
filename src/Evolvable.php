<?php

declare(strict_types=1);

namespace Crell\Serde;

/**
 * Based on a very similar class from brendt.
 * @see https://github.com/spatie/php-cloneable
 */
trait Evolvable
{
    public function with(...$values): static
    {
         $clone = (new \ReflectionClass(static::class))->newInstanceWithoutConstructor();

        foreach ($this as $field => $value) {
            $value = array_key_exists($field, $values) ? $values[$field] : $value;
            $clone->$field = $value;
        }

        return $clone;
    }
}
