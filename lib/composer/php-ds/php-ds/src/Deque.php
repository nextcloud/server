<?php
namespace Ds;

/**
 * A Deque (pronounced "deck") is a sequence of values in a contiguous buffer
 * that grows and shrinks automatically. The name is a common abbreviation of
 * "double-ended queue".
 *
 * While a Deque is very similar to a Vector, it offers constant time operations
 * at both ends of the buffer, ie. shift, unshift, push and pop are all O(1).
 *
 * @package Ds
 */
final class Deque implements Sequence
{
    use Traits\GenericCollection;
    use Traits\GenericSequence;
    use Traits\SquaredCapacity;

    const MIN_CAPACITY = 8;

    protected function shouldIncreaseCapacity(): bool
    {
        return count($this) >= $this->capacity;
    }
}
