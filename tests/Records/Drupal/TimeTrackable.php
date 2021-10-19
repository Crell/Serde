<?php

declare(strict_types=1);

namespace Crell\Serde\Records\Drupal;

trait TimeTrackable
{
    public \DateTimeImmutable $createdTime;
    public \DateTimeImmutable $updatedTime;
}
