<?php

declare(strict_types=1);

namespace Crell\Serde;

use Crell\Serde\AST\Value;

class AnnotatedJsonToAst
{
    public function do(string $json): Value
    {
        $array = json_decode($json, true, 512, JSON_THROW_ON_ERROR);


    }
}
