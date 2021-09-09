<?php

declare(strict_types=1);

namespace Crell\Serde\Decoder;

use Crell\Serde\AST\DictionaryValue;
use Crell\Serde\AST\SequenceValue;
use Crell\Serde\AST\Value;
use Crell\Serde\Decoder;
use Crell\Serde\Delegatable;

/**
 * This handles both sequences and dictionaries, because PHP doesn't differentiate.
 */
class ArrayDecoder implements Decoder, Delegatable
{
    use Delegator;

    /**
     * @param array $value
     * @return SequenceValue
     */
    public function decode(mixed $value): Value
    {
        return $this->array_is_list($value)
            ? $this->decodeSequence($value)
            : $this->decodeDictionary($value);
    }

    protected function decodeSequence(array $value): SequenceValue
    {
        $values = array_map(fn (mixed $item) => $this->deferrer->decode($item), $value);
        return new SequenceValue($values);
    }

    protected function decodeDictionary(array $value): DictionaryValue
    {
        // @todo This could probably be a utility in the FP library.
        $values = array_combine(
            array_keys($value),
            array_map(fn (mixed $item) => $this->deferrer->decode($item), $value),
        );

        return new DictionaryValue(
            values: $values,
        );
    }

    // This is native in PHP 8.1, so replace with that eventually.
    private function array_is_list(array $array): bool {
        $expectedKey = 0;
        foreach ($array as $i => $_) {
            if ($i !== $expectedKey) { return false; }
            $expectedKey++;
        }
        return true;
    }
}
