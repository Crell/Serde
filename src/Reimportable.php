<?php

declare(strict_types=1);

namespace Crell\Serde;

trait Reimportable
{
    /**
     * Generic implementation of __set_state().
     *
     * This should allow *most* data objects to be re-hydrated
     * from the format used by var_export().
     */
    public static function __set_state(array $data): static
    {
        static $reflector;
        $reflector ??= new \ReflectionClass(static::class);
        $new = $reflector->newInstanceWithoutConstructor();
        foreach ($data as $k => $v) {
            $new->$k = $v;
        }
        return $new;
    }
}
