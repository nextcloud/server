<?php
namespace Ds;

use Error;
use OutOfBoundsException;
use OutOfRangeException;

/**
 * A sequence of unique values.
 *
 * @package Ds
 */
final class Set implements Collection, \ArrayAccess 
{
    use Traits\GenericCollection;

    const MIN_CAPACITY = Map::MIN_CAPACITY;

    /**
     * @var Map internal map to store the values.
     */
    private $table;

    /**
     * Creates a new set using the values of an array or Traversable object.
     * The keys of either will not be preserved.
     *
     * @param array|\Traversable|null $values
     */
    public function __construct($values = null)
    {
        $this->table = new Map();

        if (func_num_args()) {
            $this->add(...$values);
        }
    }

    /**
     * Adds zero or more values to the set.
     *
     * @param mixed ...$values
     */
    public function add(...$values)
    {
        foreach ($values as $value) {
            $this->table->put($value, null);
        }
    }

    /**
     * Ensures that enough memory is allocated for a specified capacity. This
     * potentially reduces the number of reallocations as the size increases.
     *
     * @param int $capacity The number of values for which capacity should be
     *                      allocated. Capacity will stay the same if this value
     *                      is less than or equal to the current capacity.
     */
    public function allocate(int $capacity)
    {
        $this->table->allocate($capacity);
    }

    /**
     * Returns the current capacity of the set.
     *
     * @return int
     */
    public function capacity(): int
    {
        return $this->table->capacity();
    }

    /**
     * Clear all elements in the Set
     */
    public function clear()
    {
        $this->table->clear();
    }

    /**
     * Determines whether the set contains all of zero or more values.
     *
     * @param mixed ...$values
     *
     * @return bool true if at least one value was provided and the set
     *              contains all given values, false otherwise.
     */
    public function contains(...$values): bool
    {
        foreach ($values as $value) {
            if ( ! $this->table->hasKey($value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function copy(): self
    {
        return new self($this);
    }

    /**
     * Returns the number of elements in the Stack
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->table);
    }

    /**
     * Creates a new set using values from this set that aren't in another set.
     *
     * Formally: A \ B = {x ∈ A | x ∉ B}
     *
     * @param Set $set
     *
     * @return Set
     */
    public function diff(Set $set): Set
    {
        return $this->table->diff($set->table)->keys();
    }

    /**
     * Creates a new set using values in either this set or in another set,
     * but not in both.
     *
     * Formally: A ⊖ B = {x : x ∈ (A \ B) ∪ (B \ A)}
     *
     * @param Set $set
     *
     * @return Set
     */
    public function xor(Set $set): Set
    {
        return $this->table->xor($set->table)->keys();
    }

    /**
     * Returns a new set containing only the values for which a callback
     * returns true. A boolean test will be used if a callback is not provided.
     *
     * @param callable|null $callback Accepts a value, returns a boolean:
     *                                 true : include the value,
     *                                 false: skip the value.
     *
     * @return Set
     */
    public function filter(callable $callback = null): Set
    {
        return new self(array_filter($this->toArray(), $callback ?: 'boolval'));
    }

    /**
     * Returns the first value in the set.
     *
     * @return mixed the first value in the set.
     */
    public function first()
    {
        return $this->table->first()->key;
    }

    /**
     * Returns the value at a specified position in the set.
     *
     * @param int $position
     *
     * @return mixed|null
     *
     * @throws OutOfRangeException
     */
    public function get(int $position)
    {
        return $this->table->skip($position)->key;
    }

    /**
     * Creates a new set using values common to both this set and another set.
     *
     * In other words, returns a copy of this set with all values removed that
     * aren't in the other set.
     *
     * Formally: A ∩ B = {x : x ∈ A ∧ x ∈ B}
     *
     * @param Set $set
     *
     * @return Set
     */
    public function intersect(Set $set): Set
    {
        return $this->table->intersect($set->table)->keys();
    }

    /**
     * @inheritDoc
     */
    public function isEmpty(): bool
    {
        return $this->table->isEmpty();
    }

    /**
     * Joins all values of the set into a string, adding an optional 'glue'
     * between them. Returns an empty string if the set is empty.
     *
     * @param string $glue
     *
     * @return string
     */
    public function join(string $glue = null): string
    {
        return implode($glue, $this->toArray());
    }

    /**
     * Returns the last value in the set.
     *
     * @return mixed the last value in the set.
     */
    public function last()
    {
        return $this->table->last()->key;
    }

    /**
     * Iteratively reduces the set to a single value using a callback.
     *
     * @param callable $callback Accepts the carry and current value, and
     *                           returns an updated carry value.
     *
     * @param mixed|null $initial Optional initial carry value.
     *
     * @return mixed The carry value of the final iteration, or the initial
     *               value if the set was empty.
     */
    public function reduce(callable $callback, $initial = null)
    {
        $carry = $initial;

        foreach ($this as $value) {
            $carry = $callback($carry, $value);
        }

        return $carry;
    }

    /**
     * Removes zero or more values from the set.
     *
     * @param mixed ...$values
     */
    public function remove(...$values)
    {
        foreach ($values as $value) {
            $this->table->remove($value, null);
        }
    }

    /**
     * Reverses the set in-place.
     */
    public function reverse()
    {
        $this->table->reverse();
    }

    /**
     * Returns a reversed copy of the set.
     *
     * @return Set
     */
    public function reversed(): Set
    {
        $reversed = $this->copy();
        $reversed->table->reverse();

        return $reversed;
    }

    /**
     * Returns a subset of a given length starting at a specified offset.
     *
     * @param int $offset If the offset is non-negative, the set will start
     *                    at that offset in the set. If offset is negative,
     *                    the set will start that far from the end.
     *
     * @param int $length If a length is given and is positive, the resulting
     *                    set will have up to that many values in it.
     *                    If the requested length results in an overflow, only
     *                    values up to the end of the set will be included.
     *
     *                    If a length is given and is negative, the set
     *                    will stop that many values from the end.
     *
     *                    If a length is not provided, the resulting set
     *                    will contains all values between the offset and the
     *                    end of the set.
     *
     * @return Set
     */
    public function slice(int $offset, int $length = null): Set
    {
        $sliced = new self();
        $sliced->table = $this->table->slice($offset, $length);

        return $sliced;
    }

    /**
     * Sorts the set in-place, based on an optional callable comparator.
     *
     * @param callable|null $comparator Accepts two values to be compared.
     *                                  Should return the result of a <=> b.
     */
    public function sort(callable $comparator = null)
    {
        $this->table->ksort($comparator);
    }

    /**
     * Returns a sorted copy of the set, based on an optional callable
     * comparator. Natural ordering will be used if a comparator is not given.
     *
     * @param callable|null $comparator Accepts two values to be compared.
     *                                  Should return the result of a <=> b.
     *
     * @return Set
     */
    public function sorted(callable $comparator = null): Set
    {
        $sorted = $this->copy();
        $sorted->table->ksort($comparator);

        return $sorted;
    }

    /**
     * Returns the result of adding all given values to the set.
     *
     * @param array|\Traversable $values
     *
     * @return \Ds\Set
     */
    public function merge($values): Set
    {
        $merged = $this->copy();

        foreach ($values as $value) {
            $merged->add($value);
        }

        return $merged;
    }

    /**
     * @inheritDoc
     */
    public function toArray(): array
    {
        return iterator_to_array($this);
    }

    /**
     * Returns the sum of all values in the set.
     *
     * @return int|float The sum of all the values in the set.
     */
    public function sum()
    {
        return array_sum($this->toArray());
    }

    /**
     * Creates a new set that contains the values of this set as well as the
     * values of another set.
     *
     * Formally: A ∪ B = {x: x ∈ A ∨ x ∈ B}
     *
     * @param Set $set
     *
     * @return Set
     */
    public function union(Set $set): Set
    {
        $union = new self();

        foreach ($this as $value) {
            $union->add($value);
        }

        foreach ($set as $value) {
            $union->add($value);
        }

        return $union;
    }

    /**
     * Get iterator
     */
    public function getIterator()
    {
        foreach ($this->table as $key => $value) {
            yield $key;
        }
    }

    /**
     * @inheritdoc
     *
     * @throws OutOfBoundsException
     */
    public function offsetSet($offset, $value)
    {
        if ($offset === null) {
            $this->add($value);
            return;
        }
        throw new Error();
    }

    /**
     * @inheritdoc
     */
    public function offsetGet($offset)
    {
        return $this->table->skip($offset)->key;
    }

    /**
     * @inheritdoc
     *
     * @throws Error
     */
    public function offsetExists($offset)
    {
        throw new Error();
    }

    /**
     * @inheritdoc
     *
     * @throws Error
     */
    public function offsetUnset($offset)
    {
        throw new Error();
    }

    /**
     * Ensures that the internal table will be cloned too.
     */
    public function __clone()
    {
        $this->table = clone $this->table;
    }
}
