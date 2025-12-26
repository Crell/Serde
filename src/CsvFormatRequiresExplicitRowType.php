<?php

declare(strict_types=1);

namespace Crell\Serde;

use Crell\Serde\Attributes\ClassSettings;
use Crell\Serde\Attributes\Field;
use InvalidArgumentException;

class CsvFormatRequiresExplicitRowType extends InvalidArgumentException implements SerdeException
{
    public readonly ClassSettings $classDef;
    public readonly Field $rowField;

    public static function create(ClassSettings $classDef, Field $rowField): self
    {
        $new = new self();
        $new->classDef = $classDef;
        $new->rowField = $rowField;

        $new->message = sprintf('While trying to use %s with a CSV format, the %s field must be marked as a SequenceField and must have an explicit arrayType that specifies a class.', $classDef->phpType, $rowField->phpName);

        return $new;
    }
}
