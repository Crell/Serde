<?php

declare(strict_types=1);

namespace Crell\Serde\Records\Drupal;


class Node
{
    use TimeTrackable;
    use Fieldable;

    public int $nid;

    public function __construct(
        public string $title,
        public int $uid,
        public bool $promoted = false,
        public bool $sticky = false,
    ) {}

}
