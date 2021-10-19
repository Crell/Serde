<?php

declare(strict_types=1);

namespace Crell\Serde\Records\Drupal;

class User
{
    use TimeTrackable;
    use Fieldable;

    public int $uid;

    public function __construct(
        public string $name,
    ) {
    }

}
