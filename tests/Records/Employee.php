<?php

declare(strict_types=1);

namespace Crell\Serde\Records;

use Crell\Serde\Evolvable;

/**
 * A more complex test showing dependent and complex values.
 *
 * There are 4 kinds of special fields evaluated here.
 * - A DateTime field should map to a DateTime in the DB.
 * - A basic array should get mapped to a JSON field and serialized
 *   as such.  No PHP serialization. It should safely round-trip back
 *   an array.
 * - A dependent object with no IDs of its own should get serialized
 *   as a JSON field, and deserialized back to the same object.
 * - A dependent object with its own IDs should get saved as a foreign
 *   key to its own table, rather than in this table directly.
 */
class Employee
{
    use Evolvable;

    public string $fullName;

    public int $id;

    public function __construct(
        public string $first,
        public string $last,
        // To test special DateTime handling.
        public \DateTimeImmutable $hireDate,
        // To test serializing an array to a JSON field.
//        public array $tags,
        // To test foreign Keys.
//        public Job $job,
        public Address $address,
        // To test foreign keys to self.
//        public Employee $manager,
    ) {
        // Crap, that means we can't do this in the constructor.
//        $this->fullName = sprintf("%s %s", $this->first, $this->last);
    }

    /*
    public function withTag(string $tag): static
    {
        return $this->with(tags: [...$this->tags, $tag]);
    }
*/
}
