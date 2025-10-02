<?php

declare(strict_types=1);

namespace MabeEnum;

use ArrayAccess;
use Countable;
use InvalidArgumentException;
use Iterator;
use IteratorAggregate;
use UnexpectedValueException;

/**
 * A map of enumerators and data values (EnumMap<T of Enum, mixed>).
 *
 * @template T of Enum
 * @implements ArrayAccess<T, mixed>
 * @implements IteratorAggregate<T, mixed>
 *
 * @copyright 2020, Marc Bennewitz
 * @license http://github.com/marc-mabe/php-enum/blob/master/LICENSE.txt New BSD License
 * @link http://github.com/marc-mabe/php-enum for the canonical source repository
 */
class EnumMap implements ArrayAccess, Countable, IteratorAggregate
{
    /**
     * The classname of the enumeration type
     * @var class-string<T>
     */
    private $enumeration;

    /**
     * Internal map of ordinal number and data value
     * @var array<int, mixed>
     */
    private $map = [];

    /**
     * Constructor
     * @param class-string<T> $enumeration The classname of the enumeration type
     * @param null|iterable<T|null|bool|int|float|string|array<mixed>, mixed> $map Initialize map
     * @throws InvalidArgumentException
     */
    public function __construct(string $enumeration, ?iterable $map = null)
    {
        if (!\is_subclass_of($enumeration, Enum::class)) {
            throw new InvalidArgumentException(\sprintf(
                '%s can handle subclasses of %s only',
                 __CLASS__,
                Enum::class
            ));
        }
        $this->enumeration = $enumeration;

        if ($map) {
            $this->addIterable($map);
        }
    }

    /**
     * Add virtual private property "__pairs" with a list of key-value-pairs
     * to the result of var_dump.
     *
     * This helps debugging as internally the map is using the ordinal number.
     *
     * @return array<string, mixed>
     */
    public function __debugInfo(): array {
        $dbg = (array)$this;
        $dbg["\0" . self::class . "\0__pairs"] = array_map(function ($k, $v) {
            return [$k, $v];
        }, $this->getKeys(), $this->getValues());
        return $dbg;
    }

    /* write access (mutable) */

    /**
     * Adds the given enumerator (object or value) mapping to the specified data value.
     * @param T|null|bool|int|float|string|array<mixed> $enumerator
     * @param mixed                                                 $value
     * @throws InvalidArgumentException On an invalid given enumerator
     * @see offsetSet()
     */
    public function add($enumerator, $value): void
    {
        $ord = ($this->enumeration)::get($enumerator)->getOrdinal();
        $this->map[$ord] = $value;
    }

    /**
     * Adds the given iterable, mapping enumerators (objects or values) to data values.
     * @param iterable<T|null|bool|int|float|string|array<mixed>, mixed> $map
     * @throws InvalidArgumentException On an invalid given enumerator
     */
    public function addIterable(iterable $map): void
    {
        $innerMap = $this->map;
        foreach ($map as $enumerator => $value) {
            $ord = ($this->enumeration)::get($enumerator)->getOrdinal();
            $innerMap[$ord] = $value;
        }
        $this->map = $innerMap;
    }

    /**
     * Removes the given enumerator (object or value) mapping.
     * @param T|null|bool|int|float|string|array<mixed> $enumerator
     * @throws InvalidArgumentException On an invalid given enumerator
     * @see offsetUnset()
     */
    public function remove($enumerator): void
    {
        $ord = ($this->enumeration)::get($enumerator)->getOrdinal();
        unset($this->map[$ord]);
    }

    /**
     * Removes the given iterable enumerator (object or value) mappings.
     * @param iterable<T|null|bool|int|float|string|array<mixed>> $enumerators
     * @throws InvalidArgumentException On an invalid given enumerator
     */
    public function removeIterable(iterable $enumerators): void
    {
        $map = $this->map;
        foreach ($enumerators as $enumerator) {
            $ord = ($this->enumeration)::get($enumerator)->getOrdinal();
            unset($map[$ord]);
        }

        $this->map = $map;
    }

    /* write access (immutable) */

    /**
     * Creates a new map with the given enumerator (object or value) mapping to the specified data value added.
     * @param T|null|bool|int|float|string|array<mixed> $enumerator
     * @param mixed                                                 $value
     * @return static
     * @throws InvalidArgumentException On an invalid given enumerator
     */
    public function with($enumerator, $value): self
    {
        $clone = clone $this;
        $clone->add($enumerator, $value);
        return $clone;
    }

    /**
     * Creates a new map with the given iterable mapping enumerators (objects or values) to data values added.
     * @param iterable<T|null|bool|int|float|string|array<mixed>, mixed> $map
     * @return static
     * @throws InvalidArgumentException On an invalid given enumerator
     */
    public function withIterable(iterable $map): self
    {
        $clone = clone $this;
        $clone->addIterable($map);
        return $clone;
    }

    /**
     * Create a new map with the given enumerator mapping removed.
     * @param T|null|bool|int|float|string|array<mixed> $enumerator
     * @return static
     * @throws InvalidArgumentException On an invalid given enumerator
     */
    public function without($enumerator): self
    {
        $clone = clone $this;
        $clone->remove($enumerator);
        return $clone;
    }

    /**
     * Creates a new map with the given iterable enumerator (object or value) mappings removed.
     * @param iterable<T|null|bool|int|float|string|array<mixed>> $enumerators
     * @return static
     * @throws InvalidArgumentException On an invalid given enumerator
     */
    public function withoutIterable(iterable $enumerators): self
    {
        $clone = clone $this;
        $clone->removeIterable($enumerators);
        return $clone;
    }

    /* read access */

    /**
     * Get the classname of the enumeration type.
     * @return class-string<T>
     */
    public function getEnumeration(): string
    {
        return $this->enumeration;
    }

    /**
     * Get the mapped data value of the given enumerator (object or value).
     * @param T|null|bool|int|float|string|array<mixed> $enumerator
     * @return mixed
     * @throws InvalidArgumentException On an invalid given enumerator
     * @throws UnexpectedValueException If the given enumerator does not exist in this map
     * @see offsetGet()
     */
    public function get($enumerator)
    {
        $enumerator = ($this->enumeration)::get($enumerator);
        $ord = $enumerator->getOrdinal();
        if (!\array_key_exists($ord, $this->map)) {
            throw new UnexpectedValueException(sprintf(
                'Enumerator %s could not be found',
                \var_export($enumerator->getValue(), true)
            ));
        }

        return $this->map[$ord];
    }

    /**
     * Get a list of enumerator keys.
     * @return T[]
     *
     * @phpstan-return array<int, T>
     * @psalm-return list<T>
     */
    public function getKeys(): array
    {
        /** @var callable $byOrdinalFn */
        $byOrdinalFn = [$this->enumeration, 'byOrdinal'];

        return \array_map($byOrdinalFn, \array_keys($this->map));
    }

    /**
     * Get a list of mapped data values.
     * @return mixed[]
     *
     * @phpstan-return array<int, mixed>
     * @psalm-return list<mixed>
     */
    public function getValues(): array
    {
        return \array_values($this->map);
    }

    /**
     * Search for the given data value.
     * @param mixed $value
     * @param bool $strict Use strict type comparison
     * @return T|null The enumerator object of the first matching data value or NULL
     */
    public function search($value, bool $strict = false)
    {
        /** @var false|int $ord */
        $ord = \array_search($value, $this->map, $strict);
        if ($ord !== false) {
            return ($this->enumeration)::byOrdinal($ord);
        }

        return null;
    }

    /**
     * Test if the given enumerator key (object or value) exists.
     * @param T|null|bool|int|float|string|array<mixed> $enumerator
     * @return bool
     * @see offsetExists()
     */
    public function has($enumerator): bool
    {
        try {
            $ord = ($this->enumeration)::get($enumerator)->getOrdinal();
            return \array_key_exists($ord, $this->map);
        } catch (InvalidArgumentException $e) {
            // An invalid enumerator can't be contained in this map
            return false;
        }
    }

    /**
     * Test if the given enumerator key (object or value) exists.
     * @param T|null|bool|int|float|string|array<mixed> $enumerator
     * @return bool
     * @see offsetExists()
     * @see has()
     * @deprecated Will trigger deprecation warning in last 4.x and removed in 5.x
     */
    public function contains($enumerator): bool
    {
        return $this->has($enumerator);
    }

    /* ArrayAccess */

    /**
     * Test if the given enumerator key (object or value) exists and is not NULL
     * @param T|null|bool|int|float|string|array<mixed> $enumerator
     * @return bool
     * @see contains()
     */
    public function offsetExists($enumerator): bool
    {
        try {
            return isset($this->map[($this->enumeration)::get($enumerator)->getOrdinal()]);
        } catch (InvalidArgumentException $e) {
            // An invalid enumerator can't be an offset of this map
            return false;
        }
    }

    /**
     * Get the mapped data value of the given enumerator (object or value).
     * @param T|null|bool|int|float|string|array<mixed> $enumerator
     * @return mixed The mapped date value of the given enumerator or NULL
     * @throws InvalidArgumentException On an invalid given enumerator
     * @see get()
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($enumerator)
    {
        try {
            return $this->get($enumerator);
        } catch (UnexpectedValueException $e) {
            return null;
        }
    }

    /**
     * Adds the given enumerator (object or value) mapping to the specified data value.
     * @param T|null|bool|int|float|string|array<mixed> $enumerator
     * @param mixed                                     $value
     * @return void
     * @throws InvalidArgumentException On an invalid given enumerator
     * @see add()
     */
    public function offsetSet($enumerator, $value = null): void
    {
        $this->add($enumerator, $value);
    }

    /**
     * Removes the given enumerator (object or value) mapping.
     * @param T|null|bool|int|float|string|array<mixed> $enumerator
     * @return void
     * @throws InvalidArgumentException On an invalid given enumerator
     * @see remove()
     */
    public function offsetUnset($enumerator): void
    {
        $this->remove($enumerator);
    }

    /* IteratorAggregate */

    /**
     * Get a new Iterator.
     *
     * @return Iterator<T, mixed> Iterator<K extends Enum, V>
     */
    public function getIterator(): Iterator
    {
        $map = $this->map;
        foreach ($map as $ordinal => $value) {
            yield ($this->enumeration)::byOrdinal($ordinal) => $value;
        }
    }

    /* Countable */

    /**
     * Count the number of elements
     *
     * @return int
     */
    public function count(): int
    {
        return \count($this->map);
    }

    /**
     * Tests if the map is empty
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->map);
    }
}
