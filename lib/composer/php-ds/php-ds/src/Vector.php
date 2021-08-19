<?php
namespace Ds;

/**
 * A Vector is a sequence of values in a contiguous buffer that grows and
 * shrinks automatically. It’s the most efficient sequential structure because
 * a value’s index is a direct mapping to its index in the buffer, and the
 * growth factor isn't bound to a specific multiple or exponent.
 *
 * @package Ds
 */
final class Vector implements Sequence
{
    use Traits\GenericCollection;
    use Traits\GenericSequence;
    use Traits\Capacity;

    const MIN_CAPACITY = 8;

    protected function getGrowthFactor(): float
    {
        return 1.5;
    }

    /**
     * @return bool whether capacity should be increased.
     */
    protected function shouldIncreaseCapacity(): bool
    {
        return count($this) > $this->capacity;
    }
}
