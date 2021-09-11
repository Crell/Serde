<?php

declare(strict_types=1);

namespace Crell\Serde;

use Crell\Serde\AST\Value;

class AstToJson
{
    protected AstToArray $arrayifier;

    public function __construct(protected bool $prettyPrint = false)
    {
        $this->arrayifier = new AstToArray();
    }

    public function do(Value $value): string
    {
        $options = JSON_THROW_ON_ERROR;
        if ($this->prettyPrint) {
            $options |= JSON_PRETTY_PRINT;
        }
        return json_encode($this->arrayifier->do($value), $options);
    }
}
