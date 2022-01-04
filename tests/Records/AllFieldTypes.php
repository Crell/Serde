<?php

declare(strict_types=1);

namespace Crell\Serde\Records;

use Crell\Serde\DictionaryField;
use Crell\Serde\SequenceField;

/**
 * A test class that includes all meaningful types of field, for testing purposes.
 */
class AllFieldTypes
{
    public function __construct(
        public int $anint = 0,
        public string $string = '',
        public float $afloat = 0,
        public bool $bool = true,
        public ?\DateTimeImmutable $dateTimeImmutable = null,
        public ?\DateTime $dateTime = null,
        public ?\DateTimeZone $dateTimeZone = null,
        public array $simpleArray = [],
        public array $assocArray = [],
        public ?Point $simpleObject = null,
        #[SequenceField(arrayType: Point::class)]
        public array $objectList = [],
        #[DictionaryField(arrayType: Point::class)]
        public array $objectMap = [],
        public array $nestedArray = [],
        public Size $size = Size::Small,
        public BackedSize $backedSize = BackedSize::Small,
        #[SequenceField(implodeOn: ',')]
        public array $implodedSeq = [],
        #[DictionaryField(implodeOn: ',', joinOn: '=')]
        public array $implodedDict = [],
//        public $untyped = null,
//        public $resource = null,
    ) {}
}
