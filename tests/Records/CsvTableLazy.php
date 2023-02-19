<?php

declare(strict_types=1);

namespace Crell\Serde\Records;

use Crell\Serde\Attributes\SequenceField;

class CsvTableLazy
{
    /**
     * @param CsvRow[] $people
     */
    public function __construct(
        #[SequenceField(arrayType: CsvRow::class)]
        public iterable $people,
    ) {}

}
