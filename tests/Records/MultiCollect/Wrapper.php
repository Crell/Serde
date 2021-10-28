<?php

declare(strict_types=1);

namespace Crell\Serde\Records\MultiCollect;

use Crell\Serde\Field;
use Crell\Serde\StaticTypeMap;

// @todo Make this actually work, round-trip, and we solve the "type"/"renderType" question for TCA.

class Wrapper
{
    public function __construct(
        #[Field(flatten: true)]
        public GroupOne $one,
        #[Field(flatten: true)]
        public GroupTwo $two,
        #[Field(flatten: true)]
        public array $other = [],
    ) {}
}

#[StaticTypeMap(key: 'group_one', map: [
    'thing_a' => ThingOneA::class,
    'thing_b' => ThingOneB::class,
])]
interface GroupOne
{

}

#[StaticTypeMap(key: 'group_two', map: [
    'thing_c' => ThingTwoC::class,
    'thing_d' => ThingTwoD::class,
])]
interface GroupTwo
{

}

class ThingOneA implements GroupOne
{
    public function __construct(
        public string $first = '',
        public string $second = '',
    ) {}
}

class ThingOneB implements GroupOne
{
    public function __construct(
        public string $third = '',
        public string $fourth = '',
    ) {}
}

class ThingTwoC implements GroupTwo
{
    public function __construct(
        public string $fifth = '',
        public string $sixth = '',
    ) {}
}

class ThingTwoD implements GroupTwo
{
    public function __construct(
        public string $seventh = '',
        public string $eighth = '',
    ) {}
}
