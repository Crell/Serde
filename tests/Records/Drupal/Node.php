<?php

declare(strict_types=1);

namespace Crell\Serde\Records\Drupal;

use Crell\Serde\ArrayField;

trait TimeTrackable
{
    public \DateTimeImmutable $createdTime;
    public \DateTimeImmutable $updatedTime;
}

trait Fieldable
{
    // FieldItemListInterface[]
    #[ArrayField(keyType: 'int', valueType: FieldItemList::class)]
    public array $fields;
}

class User
{
//    use TimeTrackable;
    use Fieldable;

    public int $uid;

    public function __construct(
        public string $name,
    ) {}

}

class Node
{
//    use TimeTrackable;
    use Fieldable;

    public int $nid;

    public function __construct(
        public string $title,
        public int $uid,
        public bool $promoted = false,
        public bool $sticky = false,
    ) {}

}

class FieldItemList
{
    public function __construct(
        public string $langcode = 'en',
        /** @var array<int, FieldItemList> */
        #[ArrayField(keyType: 'int', valueType: Field::class)]
        public array $list = [],
    ) {}
}

class Field
{

}

class StringItem extends Field
{
    public function __construct(public string $value) {}
}

class EmailItem extends Field
{
    public function __construct(public string $email) {}
}

// We can totally do better than this thanks to JSON.
class MapItem extends Field
{
    public string $value;
}

class LinkItem extends Field
{
    public function __construct(
        public string $uri,
        public string $title,
        public array $options = [],
    ) {}
}

class TextItem extends Field
{
    public function __construct(
        public string $value,
        public string $format,
    ) {}

    protected string $processed;

    public function processed(): string
    {
        return $this->processed ??= $this->formatValue();
    }

    protected function formatValue(): string
    {
        // Something fancier goes here, obviously.
        return $this->value;
    }
}
