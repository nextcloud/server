<?php

declare(strict_types=1);

namespace CBOR;

use ArrayAccess;
use ArrayIterator;
use Countable;
use InvalidArgumentException;
use Iterator;
use IteratorAggregate;
use function array_key_exists;
use function count;

/**
 * @phpstan-implements ArrayAccess<int, CBORObject>
 * @phpstan-implements IteratorAggregate<int, CBORObject>
 * @see \CBOR\Test\ListObjectTest
 */
class ListObject extends AbstractCBORObject implements Countable, IteratorAggregate, Normalizable, ArrayAccess
{
    private const MAJOR_TYPE = self::MAJOR_TYPE_LIST;

    /**
     * @var CBORObject[]
     */
    private array $data;

    private ?string $length = null;

    /**
     * @param CBORObject[] $data
     */
    public function __construct(array $data = [])
    {
        [$additionalInformation, $length] = LengthCalculator::getLengthOfArray($data);
        array_map(static function ($item): void {
        }, $data);

        parent::__construct(self::MAJOR_TYPE, $additionalInformation);
        $this->data = array_values($data);
        $this->length = $length;
    }

    public function __toString(): string
    {
        $result = parent::__toString();
        if ($this->length !== null) {
            $result .= $this->length;
        }
        foreach ($this->data as $object) {
            $result .= (string) $object;
        }

        return $result;
    }

    /**
     * @param CBORObject[] $data
     */
    public static function create(array $data = []): self
    {
        return new self($data);
    }

    public function add(CBORObject $object): self
    {
        $this->data[] = $object;
        [$this->additionalInformation, $this->length] = LengthCalculator::getLengthOfArray($this->data);

        return $this;
    }

    public function has(int $index): bool
    {
        return array_key_exists($index, $this->data);
    }

    public function remove(int $index): self
    {
        if (! $this->has($index)) {
            return $this;
        }
        unset($this->data[$index]);
        $this->data = array_values($this->data);
        [$this->additionalInformation, $this->length] = LengthCalculator::getLengthOfArray($this->data);

        return $this;
    }

    public function get(int $index): CBORObject
    {
        if (! $this->has($index)) {
            throw new InvalidArgumentException('Index not found.');
        }

        return $this->data[$index];
    }

    public function set(int $index, CBORObject $object): self
    {
        if (! $this->has($index)) {
            throw new InvalidArgumentException('Index not found.');
        }

        $this->data[$index] = $object;
        [$this->additionalInformation, $this->length] = LengthCalculator::getLengthOfArray($this->data);

        return $this;
    }

    /**
     * @return array<int, mixed>
     */
    public function normalize(): array
    {
        return array_map(
            static fn (CBORObject $object) => $object instanceof Normalizable ? $object->normalize() : $object,
            $this->data
        );
    }

    public function count(): int
    {
        return count($this->data);
    }

    /**
     * @return Iterator<int, CBORObject>
     */
    public function getIterator(): Iterator
    {
        return new ArrayIterator($this->data);
    }

    public function offsetExists($offset): bool
    {
        return $this->has($offset);
    }

    public function offsetGet($offset): CBORObject
    {
        return $this->get($offset);
    }

    public function offsetSet($offset, $value): void
    {
        if ($offset === null) {
            $this->add($value);

            return;
        }

        $this->set($offset, $value);
    }

    public function offsetUnset($offset): void
    {
        $this->remove($offset);
    }
}
