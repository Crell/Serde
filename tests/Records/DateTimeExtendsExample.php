<?php

declare(strict_types=1);

namespace Crell\Serde\Records;

final class DateTimeExtendsExample {
  public function __construct(
    public DateTimeExtends $extendsProperty,
  ) {}
}
