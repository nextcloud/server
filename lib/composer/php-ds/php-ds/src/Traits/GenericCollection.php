<?php
namespace Ds\Traits;

/**
 * Common to structures that implement the base collection interface.
 */
trait GenericCollection
{
    /**
     * Returns whether the collection is empty.
     *
     * This should be equivalent to a count of zero, but is not required.
     * Implementations should define what empty means in their own context.
     *
     * @return bool whether the collection is empty.
     */
    public function isEmpty(): bool
    {
        return count($this) === 0;
    }

    /**
     * Returns a representation that can be natively converted to JSON, which is
     * called when invoking json_encode.
     *
     * @return mixed
     *
     * @see \JsonSerializable
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * Creates a shallow copy of the collection.
     *
     * @return static a shallow copy of the collection.
     */
    public function copy(): self
    {
        return new static($this);
    }

    /**
     * Returns an array representation of the collection.
     *
     * The format of the returned array is implementation-dependent. Some
     * implementations may throw an exception if an array representation
     * could not be created (for example when object are used as keys).
     *
     * @return array
     */
    abstract public function toArray(): array;

    /**
     * Invoked when calling var_dump.
     *
     * @return array
     */
    public function __debugInfo()
    {
        return $this->toArray();
    }

    /**
     * Returns a string representation of the collection, which is invoked when
     * the collection is converted to a string.
     */
    public function __toString()
    {
        return 'object(' . get_class($this) . ')';
    }
}
