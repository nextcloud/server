<?php

declare(strict_types=1);

namespace MabeEnum;

use Countable;
use InvalidArgumentException;
use Iterator;
use IteratorAggregate;

/**
 * A set of enumerators of the given enumeration (EnumSet<T of Enum>)
 * based on an integer or binary bitset depending of given enumeration size.
 *
 * @template T of Enum
 * @implements IteratorAggregate<int, T>
 *
 * @copyright 2020, Marc Bennewitz
 * @license http://github.com/marc-mabe/php-enum/blob/master/LICENSE.txt New BSD License
 * @link http://github.com/marc-mabe/php-enum for the canonical source repository
 */
class EnumSet implements IteratorAggregate, Countable
{
    /**
     * The classname of the Enumeration
     * @var class-string<T>
     */
    private $enumeration;

    /**
     * Number of enumerators defined in the enumeration
     * @var int
     */
    private $enumerationCount;

    /**
     * Integer or binary (little endian) bitset
     * @var int|string
     */
    private $bitset = 0;

    /**
     * Integer or binary (little endian) empty bitset
     *
     * @var int|string
     */
    private $emptyBitset = 0;

    /**#@+
     * Defines private method names to be called depended of how the bitset type was set too.
     * ... Integer or binary bitset.
     * ... *Int or *Bin method
     *
     * @var string
     */
    /** @var string */
    private $fnDoGetIterator       = 'doGetIteratorInt';

    /** @var string */
    private $fnDoCount             = 'doCountInt';

    /** @var string */
    private $fnDoGetOrdinals       = 'doGetOrdinalsInt';

    /** @var string */
    private $fnDoGetBit            = 'doGetBitInt';

    /** @var string */
    private $fnDoSetBit            = 'doSetBitInt';

    /** @var string */
    private $fnDoUnsetBit          = 'doUnsetBitInt';

    /** @var string */
    private $fnDoGetBinaryBitsetLe = 'doGetBinaryBitsetLeInt';

    /** @var string */
    private $fnDoSetBinaryBitsetLe = 'doSetBinaryBitsetLeInt';
    /**#@-*/

    /**
     * Constructor
     *
     * @param class-string<T> $enumeration The classname of the enumeration
     * @param iterable<T|null|bool|int|float|string|array<mixed>>|null $enumerators iterable list of enumerators initializing the set
     * @throws InvalidArgumentException
     */
    public function __construct(string $enumeration, ?iterable $enumerators = null)
    {
        if (!\is_subclass_of($enumeration, Enum::class)) {
            throw new InvalidArgumentException(\sprintf(
                '%s can handle subclasses of %s only',
                __METHOD__,
                Enum::class
            ));
        }

        $this->enumeration      = $enumeration;
        $this->enumerationCount = \count($enumeration::getConstants());

        // By default the bitset is initialized as integer bitset
        // in case the enumeration has more enumerators then integer bits
        // we will switch this into a binary bitset
        if ($this->enumerationCount > \PHP_INT_SIZE * 8) {
            // init binary bitset with zeros
            $this->bitset = $this->emptyBitset = \str_repeat("\0", (int)\ceil($this->enumerationCount / 8));

            // switch internal binary bitset functions
            $this->fnDoGetIterator       = 'doGetIteratorBin';
            $this->fnDoCount             = 'doCountBin';
            $this->fnDoGetOrdinals       = 'doGetOrdinalsBin';
            $this->fnDoGetBit            = 'doGetBitBin';
            $this->fnDoSetBit            = 'doSetBitBin';
            $this->fnDoUnsetBit          = 'doUnsetBitBin';
            $this->fnDoGetBinaryBitsetLe = 'doGetBinaryBitsetLeBin';
            $this->fnDoSetBinaryBitsetLe = 'doSetBinaryBitsetLeBin';
        }

        if ($enumerators !== null) {
            foreach ($enumerators as $enumerator) {
                $this->{$this->fnDoSetBit}($enumeration::get($enumerator)->getOrdinal());
            }
        }
    }

    /**
     * Add virtual private property "__enumerators" with a list of enumerator values set
     * to the result of var_dump.
     *
     * This helps debugging as internally the enumerators of this EnumSet gets stored
     * as either integer or binary bit-array.
     *
     * @return array<string, mixed>
     */
    public function __debugInfo() {
        $dbg = (array)$this;
        $dbg["\0" . self::class . "\0__enumerators"] = $this->getValues();
        return $dbg;
    }

    /**
     * Get the classname of the enumeration
     * @return class-string<T>
     */
    public function getEnumeration(): string
    {
        return $this->enumeration;
    }

    /* write access (mutable) */

    /**
     * Adds an enumerator object or value
     * @param T|null|bool|int|float|string|array<mixed> $enumerator Enumerator object or value
     * @return void
     * @throws InvalidArgumentException On an invalid given enumerator
     */
    public function add($enumerator): void
    {
        $this->{$this->fnDoSetBit}(($this->enumeration)::get($enumerator)->getOrdinal());
    }

    /**
     * Adds all enumerator objects or values of the given iterable
     * @param iterable<T|null|bool|int|float|string|array<mixed>> $enumerators Iterable list of enumerator objects or values
     * @return void
     * @throws InvalidArgumentException On an invalid given enumerator
     */
    public function addIterable(iterable $enumerators): void
    {
        $bitset = $this->bitset;

        try {
            foreach ($enumerators as $enumerator) {
                $this->{$this->fnDoSetBit}(($this->enumeration)::get($enumerator)->getOrdinal());
            }
        } catch (\Throwable $e) {
            // reset all changes until error happened
            $this->bitset = $bitset;
            throw $e;
        }
    }

    /**
     * Removes the given enumerator object or value
     * @param T|null|bool|int|float|string|array<mixed> $enumerator Enumerator object or value
     * @return void
     * @throws InvalidArgumentException On an invalid given enumerator
     */
    public function remove($enumerator): void
    {
        $this->{$this->fnDoUnsetBit}(($this->enumeration)::get($enumerator)->getOrdinal());
    }

    /**
     * Adds an enumerator object or value
     * @param T|null|bool|int|float|string|array<mixed> $enumerator Enumerator object or value
     * @return void
     * @throws InvalidArgumentException On an invalid given enumerator
     * @see add()
     * @see with()
     * @deprecated Will trigger deprecation warning in last 4.x and removed in 5.x
     */
    public function attach($enumerator): void
    {
        $this->add($enumerator);
    }

    /**
     * Removes the given enumerator object or value
     * @param T|null|bool|int|float|string|array<mixed> $enumerator Enumerator object or value
     * @return void
     * @throws InvalidArgumentException On an invalid given enumerator
     * @see remove()
     * @see without()
     * @deprecated Will trigger deprecation warning in last 4.x and removed in 5.x
     */
    public function detach($enumerator): void
    {
        $this->remove($enumerator);
    }

    /**
     * Removes all enumerator objects or values of the given iterable
     * @param iterable<T|null|bool|int|float|string|array<mixed>> $enumerators Iterable list of enumerator objects or values
     * @return void
     * @throws InvalidArgumentException On an invalid given enumerator
     */
    public function removeIterable(iterable $enumerators): void
    {
        $bitset = $this->bitset;

        try {
            foreach ($enumerators as $enumerator) {
                $this->{$this->fnDoUnsetBit}(($this->enumeration)::get($enumerator)->getOrdinal());
            }
        } catch (\Throwable $e) {
            // reset all changes until error happened
            $this->bitset = $bitset;
            throw $e;
        }
    }

    /**
     * Modify this set from both this and other (this | other)
     *
     * @param EnumSet<T> $other EnumSet of the same enumeration to produce the union
     * @return void
     * @throws InvalidArgumentException If $other doesn't match the enumeration
     */
    public function setUnion(EnumSet $other): void
    {
        if ($this->enumeration !== $other->enumeration) {
            throw new InvalidArgumentException(\sprintf(
                'Other should be of the same enumeration as this %s',
                $this->enumeration
            ));
        }

        $this->bitset = $this->bitset | $other->bitset;
    }

    /**
     * Modify this set with enumerators common to both this and other (this & other)
     *
     * @param EnumSet<T> $other EnumSet of the same enumeration to produce the intersect
     * @return void
     * @throws InvalidArgumentException If $other doesn't match the enumeration
     */
    public function setIntersect(EnumSet $other): void
    {
        if ($this->enumeration !== $other->enumeration) {
            throw new InvalidArgumentException(\sprintf(
                'Other should be of the same enumeration as this %s',
                $this->enumeration
            ));
        }

        $this->bitset = $this->bitset & $other->bitset;
    }

    /**
     * Modify this set with enumerators in this but not in other (this - other)
     *
     * @param EnumSet<T> $other EnumSet of the same enumeration to produce the diff
     * @return void
     * @throws InvalidArgumentException If $other doesn't match the enumeration
     */
    public function setDiff(EnumSet $other): void
    {
        if ($this->enumeration !== $other->enumeration) {
            throw new InvalidArgumentException(\sprintf(
                'Other should be of the same enumeration as this %s',
                $this->enumeration
            ));
        }

        $this->bitset = $this->bitset & ~$other->bitset;
    }

    /**
     * Modify this set with enumerators in either this and other but not in both (this ^ other)
     *
     * @param EnumSet<T> $other EnumSet of the same enumeration to produce the symmetric difference
     * @return void
     * @throws InvalidArgumentException If $other doesn't match the enumeration
     */
    public function setSymDiff(EnumSet $other): void
    {
        if ($this->enumeration !== $other->enumeration) {
            throw new InvalidArgumentException(\sprintf(
                'Other should be of the same enumeration as this %s',
                $this->enumeration
            ));
        }

        $this->bitset = $this->bitset ^ $other->bitset;
    }

    /**
     * Set the given binary bitset in little-endian order
     *
     * @param string $bitset
     * @return void
     * @throws InvalidArgumentException On out-of-range bits given as input bitset
     * @uses doSetBinaryBitsetLeBin()
     * @uses doSetBinaryBitsetLeInt()
     */
    public function setBinaryBitsetLe(string $bitset): void
    {
        $this->{$this->fnDoSetBinaryBitsetLe}($bitset);
    }

    /**
     * Set binary bitset in little-endian order
     *
     * @param string $bitset
     * @return void
     * @throws InvalidArgumentException On out-of-range bits given as input bitset
     * @see setBinaryBitsetLeBin()
     * @see doSetBinaryBitsetLeInt()
     */
    private function doSetBinaryBitsetLeBin($bitset): void
    {
        /** @var string $thisBitset */
        $thisBitset = $this->bitset;

        $size   = \strlen($thisBitset);
        $sizeIn = \strlen($bitset);

        if ($sizeIn < $size) {
            // add "\0" if the given bitset is not long enough
            $bitset .= \str_repeat("\0", $size - $sizeIn);
        } elseif ($sizeIn > $size) {
            if (\ltrim(\substr($bitset, $size), "\0") !== '') {
                throw new InvalidArgumentException('out-of-range bits detected');
            }
            $bitset = \substr($bitset, 0, $size);
        }

        // truncate out-of-range bits of last byte
        $lastByteMaxOrd = $this->enumerationCount % 8;
        if ($lastByteMaxOrd !== 0) {
            $lastByte         = $bitset[-1];
            $lastByteExpected = \chr((1 << $lastByteMaxOrd) - 1) & $lastByte;
            if ($lastByte !== $lastByteExpected) {
                throw new InvalidArgumentException('out-of-range bits detected');
            }

            $this->bitset = \substr($bitset, 0, -1) . $lastByteExpected;
        }

        $this->bitset = $bitset;
    }

    /**
     * Set binary bitset in little-endian order
     *
     * @param string $bitset
     * @return void
     * @throws InvalidArgumentException On out-of-range bits given as input bitset
     * @see setBinaryBitsetLeBin()
     * @see doSetBinaryBitsetLeBin()
     */
    private function doSetBinaryBitsetLeInt($bitset): void
    {
        $len = \strlen($bitset);
        $int = 0;
        for ($i = 0; $i < $len; ++$i) {
            $ord = \ord($bitset[$i]);

            if ($ord && $i > \PHP_INT_SIZE - 1) {
                throw new InvalidArgumentException('out-of-range bits detected');
            }

            $int |= $ord << (8 * $i);
        }

        if ($int & (~0 << $this->enumerationCount)) {
            throw new InvalidArgumentException('out-of-range bits detected');
        }

        $this->bitset = $int;
    }

    /**
     * Set the given binary bitset in big-endian order
     *
     * @param string $bitset
     * @return void
     * @throws InvalidArgumentException On out-of-range bits given as input bitset
     */
    public function setBinaryBitsetBe(string $bitset): void
    {
        $this->{$this->fnDoSetBinaryBitsetLe}(\strrev($bitset));
    }

    /**
     * Set a bit at the given ordinal number
     *
     * @param int $ordinal Ordinal number of bit to set
     * @param bool $bit    The bit to set
     * @return void
     * @throws InvalidArgumentException If the given ordinal number is out-of-range
     * @uses doSetBitBin()
     * @uses doSetBitInt()
     * @uses doUnsetBitBin()
     * @uses doUnsetBitInt()
     */
    public function setBit(int $ordinal, bool $bit): void
    {
        if ($ordinal < 0 || $ordinal > $this->enumerationCount) {
            throw new InvalidArgumentException("Ordinal number must be between 0 and {$this->enumerationCount}");
        }

        if ($bit) {
            $this->{$this->fnDoSetBit}($ordinal);
        } else {
            $this->{$this->fnDoUnsetBit}($ordinal);
        }
    }

    /**
     * Set a bit at the given ordinal number.
     *
     * This is the binary bitset implementation.
     *
     * @param int $ordinal Ordinal number of bit to set
     * @return void
     * @see setBit()
     * @see doSetBitInt()
     */
    private function doSetBitBin($ordinal): void
    {
        /** @var string $thisBitset */
        $thisBitset = $this->bitset;

        $byte = (int) ($ordinal / 8);
        $thisBitset[$byte] = $thisBitset[$byte] | \chr(1 << ($ordinal % 8));

        $this->bitset = $thisBitset;
    }

    /**
     * Set a bit at the given ordinal number.
     *
     * This is the binary bitset implementation.
     *
     * @param int $ordinal Ordinal number of bit to set
     * @return void
     * @see setBit()
     * @see doSetBitBin()
     */
    private function doSetBitInt($ordinal): void
    {
        /** @var int $thisBitset */
        $thisBitset = $this->bitset;

        $this->bitset = $thisBitset | (1 << $ordinal);
    }

    /**
     * Unset a bit at the given ordinal number.
     *
     * This is the binary bitset implementation.
     *
     * @param int $ordinal Ordinal number of bit to unset
     * @return void
     * @see setBit()
     * @see doUnsetBitInt()
     */
    private function doUnsetBitBin($ordinal): void
    {
        /** @var string $thisBitset */
        $thisBitset = $this->bitset;

        $byte = (int) ($ordinal / 8);
        $thisBitset[$byte] = $thisBitset[$byte] & \chr(~(1 << ($ordinal % 8)));

        $this->bitset = $thisBitset;
    }

    /**
     * Unset a bit at the given ordinal number.
     *
     * This is the integer bitset implementation.
     *
     * @param int $ordinal Ordinal number of bit to unset
     * @return void
     * @see setBit()
     * @see doUnsetBitBin()
     */
    private function doUnsetBitInt($ordinal): void
    {
        /** @var int $thisBitset */
        $thisBitset = $this->bitset;

        $this->bitset = $thisBitset & ~(1 << $ordinal);
    }

    /* write access (immutable) */

    /**
     * Creates a new set with the given enumerator object or value added
     * @param T|null|bool|int|float|string|array<mixed> $enumerator Enumerator object or value
     * @return static
     * @throws InvalidArgumentException On an invalid given enumerator
     */
    public function with($enumerator): self
    {
        $clone = clone $this;
        $clone->{$this->fnDoSetBit}(($this->enumeration)::get($enumerator)->getOrdinal());
        return $clone;
    }

    /**
     * Creates a new set with the given enumeration objects or values added
     * @param iterable<T|null|bool|int|float|string|array<mixed>> $enumerators Iterable list of enumerator objects or values
     * @return static
     * @throws InvalidArgumentException On an invalid given enumerator
     */
    public function withIterable(iterable $enumerators): self
    {
        $clone = clone $this;
        foreach ($enumerators as $enumerator) {
            $clone->{$this->fnDoSetBit}(($this->enumeration)::get($enumerator)->getOrdinal());
        }
        return $clone;
    }

    /**
     * Create a new set with the given enumerator object or value removed
     * @param T|null|bool|int|float|string|array<mixed> $enumerator Enumerator object or value
     * @return static
     * @throws InvalidArgumentException On an invalid given enumerator
     */
    public function without($enumerator): self
    {
        $clone = clone $this;
        $clone->{$this->fnDoUnsetBit}(($this->enumeration)::get($enumerator)->getOrdinal());
        return $clone;
    }

    /**
     * Creates a new set with the given enumeration objects or values removed
     * @param iterable<T|null|bool|int|float|string|array<mixed>> $enumerators Iterable list of enumerator objects or values
     * @return static
     * @throws InvalidArgumentException On an invalid given enumerator
     */
    public function withoutIterable(iterable $enumerators): self
    {
        $clone = clone $this;
        foreach ($enumerators as $enumerator) {
            $clone->{$this->fnDoUnsetBit}(($this->enumeration)::get($enumerator)->getOrdinal());
        }
        return $clone;
    }

    /**
     * Create a new set with enumerators from both this and other (this | other)
     *
     * @param EnumSet<T> $other EnumSet of the same enumeration to produce the union
     * @return static
     * @throws InvalidArgumentException If $other doesn't match the enumeration
     */
    public function withUnion(EnumSet $other): self
    {
        $clone = clone $this;
        $clone->setUnion($other);
        return $clone;
    }

    /**
     * Create a new set with enumerators from both this and other (this | other)
     *
     * @param EnumSet<T> $other EnumSet of the same enumeration to produce the union
     * @return static
     * @throws InvalidArgumentException If $other doesn't match the enumeration
     * @see withUnion()
     * @see setUnion()
     * @deprecated Will trigger deprecation warning in last 4.x and removed in 5.x
     */
    public function union(EnumSet $other): self
    {
        return $this->withUnion($other);
    }

    /**
     * Create a new set with enumerators common to both this and other (this & other)
     *
     * @param EnumSet<T> $other EnumSet of the same enumeration to produce the intersect
     * @return static
     * @throws InvalidArgumentException If $other doesn't match the enumeration
     */
    public function withIntersect(EnumSet $other): self
    {
        $clone = clone $this;
        $clone->setIntersect($other);
        return $clone;
    }

    /**
     * Create a new set with enumerators common to both this and other (this & other)
     *
     * @param EnumSet<T> $other EnumSet of the same enumeration to produce the intersect
     * @return static
     * @throws InvalidArgumentException If $other doesn't match the enumeration
     * @see withIntersect()
     * @see setIntersect()
     * @deprecated Will trigger deprecation warning in last 4.x and removed in 5.x
     */
    public function intersect(EnumSet $other): self
    {
        return $this->withIntersect($other);
    }

    /**
     * Create a new set with enumerators in this but not in other (this - other)
     *
     * @param EnumSet<T> $other EnumSet of the same enumeration to produce the diff
     * @return static
     * @throws InvalidArgumentException If $other doesn't match the enumeration
     */
    public function withDiff(EnumSet $other): self
    {
        $clone = clone $this;
        $clone->setDiff($other);
        return $clone;
    }

    /**
     * Create a new set with enumerators in this but not in other (this - other)
     *
     * @param EnumSet<T> $other EnumSet of the same enumeration to produce the diff
     * @return static
     * @throws InvalidArgumentException If $other doesn't match the enumeration
     * @see withDiff()
     * @see setDiff()
     * @deprecated Will trigger deprecation warning in last 4.x and removed in 5.x
     */
    public function diff(EnumSet $other): self
    {
        return $this->withDiff($other);
    }

    /**
     * Create a new set with enumerators in either this and other but not in both (this ^ other)
     *
     * @param EnumSet<T> $other EnumSet of the same enumeration to produce the symmetric difference
     * @return static
     * @throws InvalidArgumentException If $other doesn't match the enumeration
     */
    public function withSymDiff(EnumSet $other): self
    {
        $clone = clone $this;
        $clone->setSymDiff($other);
        return $clone;
    }

    /**
     * Create a new set with enumerators in either this and other but not in both (this ^ other)
     *
     * @param EnumSet<T> $other EnumSet of the same enumeration to produce the symmetric difference
     * @return static
     * @throws InvalidArgumentException If $other doesn't match the enumeration
     * @see withSymDiff()
     * @see setSymDiff()
     * @deprecated Will trigger deprecation warning in last 4.x and removed in 5.x
     */
    public function symDiff(EnumSet $other): self
    {
        return $this->withSymDiff($other);
    }

    /**
     * Create a new set with the given binary bitset in little-endian order
     *
     * @param string $bitset
     * @return static
     * @throws InvalidArgumentException On out-of-range bits given as input bitset
     * @uses doSetBinaryBitsetLeBin()
     * @uses doSetBinaryBitsetLeInt()
     */
    public function withBinaryBitsetLe(string $bitset): self
    {
        $clone = clone $this;
        $clone->{$this->fnDoSetBinaryBitsetLe}($bitset);
        return $clone;
    }

    /**
     * Create a new set with the given binary bitset in big-endian order
     *
     * @param string $bitset
     * @return static
     * @throws InvalidArgumentException On out-of-range bits given as input bitset
     */
    public function withBinaryBitsetBe(string $bitset): self
    {
        $clone = $this;
        $clone->{$this->fnDoSetBinaryBitsetLe}(\strrev($bitset));
        return $clone;
    }

    /**
     * Create a new set with the bit at the given ordinal number set
     *
     * @param int $ordinal Ordinal number of bit to set
     * @param bool $bit    The bit to set
     * @return static
     * @throws InvalidArgumentException If the given ordinal number is out-of-range
     * @uses doSetBitBin()
     * @uses doSetBitInt()
     * @uses doUnsetBitBin()
     * @uses doUnsetBitInt()
     */
    public function withBit(int $ordinal, bool $bit): self
    {
        $clone = clone $this;
        $clone->setBit($ordinal, $bit);
        return $clone;
    }

    /* read access */

    /**
     * Test if the given enumerator exists
     * @param T|null|bool|int|float|string|array<mixed> $enumerator
     * @return bool
     */
    public function has($enumerator): bool
    {
        return $this->{$this->fnDoGetBit}(($this->enumeration)::get($enumerator)->getOrdinal());
    }

    /**
     * Test if the given enumerator exists
     * @param T|null|bool|int|float|string|array<mixed> $enumerator
     * @return bool
     * @see has()
     * @deprecated Will trigger deprecation warning in last 4.x and removed in 5.x
     */
    public function contains($enumerator): bool
    {
        return $this->has($enumerator);
    }

    /* IteratorAggregate */

    /**
     * Get a new iterator
     * @return Iterator<int, T>
     * @uses doGetIteratorInt()
     * @uses doGetIteratorBin()
     */
    public function getIterator(): Iterator
    {
        return $this->{$this->fnDoGetIterator}();
    }

    /**
     * Get a new Iterator.
     *
     * This is the binary bitset implementation.
     *
     * @return Iterator<int, T>
     * @see getIterator()
     * @see goGetIteratorInt()
     */
    private function doGetIteratorBin()
    {
        /** @var string $bitset */
        $bitset   = $this->bitset;
        $byteLen  = \strlen($bitset);
        for ($bytePos = 0; $bytePos < $byteLen; ++$bytePos) {
            if ($bitset[$bytePos] === "\0") {
                // fast skip null byte
                continue;
            }

            $ord = \ord($bitset[$bytePos]);
            for ($bitPos = 0; $bitPos < 8; ++$bitPos) {
                if ($ord & (1 << $bitPos)) {
                    $ordinal = $bytePos * 8 + $bitPos;
                    yield $ordinal => ($this->enumeration)::byOrdinal($ordinal);
                }
            }
        }
    }

    /**
     * Get a new Iterator.
     *
     * This is the integer bitset implementation.
     *
     * @return Iterator<int, T>
     * @see getIterator()
     * @see doGetIteratorBin()
     */
    private function doGetIteratorInt()
    {
        /** @var int $bitset */
        $bitset = $this->bitset;
        $count  = $this->enumerationCount;
        for ($ordinal = 0; $ordinal < $count; ++$ordinal) {
            if ($bitset & (1 << $ordinal)) {
                yield $ordinal => ($this->enumeration)::byOrdinal($ordinal);
            }
        }
    }

    /* Countable */

    /**
     * Count the number of elements
     *
     * @return int
     * @uses doCountBin()
     * @uses doCountInt()
     */
    public function count(): int
    {
        return $this->{$this->fnDoCount}();
    }

    /**
     * Count the number of elements.
     *
     * This is the binary bitset implementation.
     *
     * @return int
     * @see count()
     * @see doCountInt()
     */
    private function doCountBin()
    {
        /** @var string $bitset */
        $bitset  = $this->bitset;
        $count   = 0;
        $byteLen = \strlen($bitset);
        for ($bytePos = 0; $bytePos < $byteLen; ++$bytePos) {
            if ($bitset[$bytePos] === "\0") {
                // fast skip null byte
                continue;
            }

            $ord = \ord($bitset[$bytePos]);
            if ($ord & 0b00000001) ++$count;
            if ($ord & 0b00000010) ++$count;
            if ($ord & 0b00000100) ++$count;
            if ($ord & 0b00001000) ++$count;
            if ($ord & 0b00010000) ++$count;
            if ($ord & 0b00100000) ++$count;
            if ($ord & 0b01000000) ++$count;
            if ($ord & 0b10000000) ++$count;
        }
        return $count;
    }

    /**
     * Count the number of elements.
     *
     * This is the integer bitset implementation.
     *
     * @return int
     * @see count()
     * @see doCountBin()
     */
    private function doCountInt()
    {
        /** @var int $bitset */
        $bitset = $this->bitset;
        $count  = 0;

        // PHP does not support right shift unsigned
        if ($bitset < 0) {
            $count  = 1;
            $bitset = $bitset & \PHP_INT_MAX;
        }

        // iterate byte by byte and count set bits
        $phpIntBitSize = \PHP_INT_SIZE * 8;
        for ($bitPos = 0; $bitPos < $phpIntBitSize; $bitPos += 8) {
            $bitChk = 0xff << $bitPos;
            $byte = $bitset & $bitChk;
            if ($byte) {
                $byte = $byte >> $bitPos;
                if ($byte & 0b00000001) ++$count;
                if ($byte & 0b00000010) ++$count;
                if ($byte & 0b00000100) ++$count;
                if ($byte & 0b00001000) ++$count;
                if ($byte & 0b00010000) ++$count;
                if ($byte & 0b00100000) ++$count;
                if ($byte & 0b01000000) ++$count;
                if ($byte & 0b10000000) ++$count;
            }

            if ($bitset <= $bitChk) {
                break;
            }
        }

        return $count;
    }

    /**
     * Check if this EnumSet is the same as other
     * @param EnumSet<T> $other
     * @return bool
     */
    public function isEqual(EnumSet $other): bool
    {
        return $this->enumeration === $other->enumeration
            && $this->bitset === $other->bitset;
    }

    /**
     * Check if this EnumSet is a subset of other
     * @param EnumSet<T> $other
     * @return bool
     */
    public function isSubset(EnumSet $other): bool
    {
        return $this->enumeration === $other->enumeration
            && ($this->bitset & $other->bitset) === $this->bitset;
    }

    /**
     * Check if this EnumSet is a superset of other
     * @param EnumSet<T> $other
     * @return bool
     */
    public function isSuperset(EnumSet $other): bool
    {
        return $this->enumeration === $other->enumeration
            && ($this->bitset | $other->bitset) === $this->bitset;
    }

    /**
     * Tests if the set is empty
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->bitset === $this->emptyBitset;
    }

    /**
     * Get ordinal numbers of the defined enumerators as array
     * @return array<int, int>
     * @uses  doGetOrdinalsBin()
     * @uses  doGetOrdinalsInt()
     */
    public function getOrdinals(): array
    {
        return $this->{$this->fnDoGetOrdinals}();
    }

    /**
     * Get ordinal numbers of the defined enumerators as array.
     *
     * This is the binary bitset implementation.
     *
     * @return array<int, int>
     * @see getOrdinals()
     * @see goGetOrdinalsInt()
     */
    private function doGetOrdinalsBin()
    {
        /** @var string $bitset */
        $bitset   = $this->bitset;
        $ordinals = [];
        $byteLen  = \strlen($bitset);
        for ($bytePos = 0; $bytePos < $byteLen; ++$bytePos) {
            if ($bitset[$bytePos] === "\0") {
                // fast skip null byte
                continue;
            }

            $ord = \ord($bitset[$bytePos]);
            for ($bitPos = 0; $bitPos < 8; ++$bitPos) {
                if ($ord & (1 << $bitPos)) {
                    $ordinals[] = $bytePos * 8 + $bitPos;
                }
            }
        }
        return $ordinals;
    }

    /**
     * Get ordinal numbers of the defined enumerators as array.
     *
     * This is the integer bitset implementation.
     *
     * @return array<int, int>
     * @see getOrdinals()
     * @see doGetOrdinalsBin()
     */
    private function doGetOrdinalsInt()
    {
        /** @var int $bitset */
        $bitset   = $this->bitset;
        $ordinals = [];
        $count    = $this->enumerationCount;
        for ($ordinal = 0; $ordinal < $count; ++$ordinal) {
            if ($bitset & (1 << $ordinal)) {
                $ordinals[] = $ordinal;
            }
        }
        return $ordinals;
    }

    /**
     * Get values of the defined enumerators as array
     * @return (null|bool|int|float|string|array)[]
     *
     * @phpstan-return array<int, null|bool|int|float|string|array<mixed>>
     * @psalm-return list<null|bool|int|float|string|array>
     */
    public function getValues(): array
    {
        $enumeration = $this->enumeration;
        $values      = [];
        foreach ($this->getOrdinals() as $ord) {
            $values[] = $enumeration::byOrdinal($ord)->getValue();
        }
        return $values;
    }

    /**
     * Get names of the defined enumerators as array
     * @return string[]
     *
     * @phpstan-return array<int, string>
     * @psalm-return list<string>
     */
    public function getNames(): array
    {
        $enumeration = $this->enumeration;
        $names       = [];
        foreach ($this->getOrdinals() as $ord) {
            $names[] = $enumeration::byOrdinal($ord)->getName();
        }
        return $names;
    }

    /**
     * Get the defined enumerators as array
     * @return Enum[]
     *
     * @phpstan-return array<int, T>
     * @psalm-return list<T>
     */
    public function getEnumerators(): array
    {
        $enumeration = $this->enumeration;
        $enumerators = [];
        foreach ($this->getOrdinals() as $ord) {
            $enumerators[] = $enumeration::byOrdinal($ord);
        }
        return $enumerators;
    }

    /**
     * Get binary bitset in little-endian order
     *
     * @return string
     * @uses doGetBinaryBitsetLeBin()
     * @uses doGetBinaryBitsetLeInt()
     */
    public function getBinaryBitsetLe(): string
    {
        return $this->{$this->fnDoGetBinaryBitsetLe}();
    }

    /**
     * Get binary bitset in little-endian order.
     *
     * This is the binary bitset implementation.
     *
     * @return string
     * @see getBinaryBitsetLe()
     * @see doGetBinaryBitsetLeInt()
     */
    private function doGetBinaryBitsetLeBin()
    {
        /** @var string $bitset */
        $bitset = $this->bitset;

        return $bitset;
    }

    /**
     * Get binary bitset in little-endian order.
     *
     * This is the integer bitset implementation.
     *
     * @return string
     * @see getBinaryBitsetLe()
     * @see doGetBinaryBitsetLeBin()
     */
    private function doGetBinaryBitsetLeInt()
    {
        $bin = \pack(\PHP_INT_SIZE === 8 ? 'P' : 'V', $this->bitset);
        return \substr($bin, 0, (int)\ceil($this->enumerationCount / 8));
    }

    /**
     * Get binary bitset in big-endian order
     *
     * @return string
     */
    public function getBinaryBitsetBe(): string
    {
        return \strrev($this->getBinaryBitsetLe());
    }

    /**
     * Get a bit at the given ordinal number
     *
     * @param int $ordinal Ordinal number of bit to get
     * @return bool
     * @throws InvalidArgumentException If the given ordinal number is out-of-range
     * @uses doGetBitBin()
     * @uses doGetBitInt()
     */
    public function getBit(int $ordinal): bool
    {
        if ($ordinal < 0 || $ordinal > $this->enumerationCount) {
            throw new InvalidArgumentException("Ordinal number must be between 0 and {$this->enumerationCount}");
        }

        return $this->{$this->fnDoGetBit}($ordinal);
    }

    /**
     * Get a bit at the given ordinal number.
     *
     * This is the binary bitset implementation.
     *
     * @param int $ordinal Ordinal number of bit to get
     * @return bool
     * @see getBit()
     * @see doGetBitInt()
     */
    private function doGetBitBin($ordinal)
    {
        /** @var string $bitset */
        $bitset = $this->bitset;

        return (\ord($bitset[(int) ($ordinal / 8)]) & 1 << ($ordinal % 8)) !== 0;
    }

    /**
     * Get a bit at the given ordinal number.
     *
     * This is the integer bitset implementation.
     *
     * @param int $ordinal Ordinal number of bit to get
     * @return bool
     * @see getBit()
     * @see doGetBitBin()
     */
    private function doGetBitInt($ordinal)
    {
        /** @var int $bitset */
        $bitset = $this->bitset;

        return (bool)($bitset & (1 << $ordinal));
    }
}
