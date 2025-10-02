<?php

declare(strict_types=1);

namespace CBOR;

use ArrayAccess;
use ArrayIterator;
use InvalidArgumentException;
use Iterator;
use IteratorAggregate;
use function array_key_exists;

/**
 * @phpstan-implements ArrayAccess<int, CBORObject>
 * @phpstan-implements IteratorAggregate<int, MapItem>
 * @final
 */
class IndefiniteLengthMapObject extends AbstractCBORObject implements IteratorAggregate, Normalizable, ArrayAccess
{
    private const MAJOR_TYPE = self::MAJOR_TYPE_MAP;

    private const ADDITIONAL_INFORMATION = self::LENGTH_INDEFINITE;

    /**
     * @var MapItem[]
     */
    private array $data = [];

    public function __construct()
    {
        parent::__construct(self::MAJOR_TYPE, self::ADDITIONAL_INFORMATION);
    }

    public function __toString(): string
    {
        $result = parent::__toString();
        foreach ($this->data as $object) {
            $result .= (string) $object->getKey();
            $result .= (string) $object->getValue();
        }

        return $result . "\xFF";
    }

    public static function create(): self
    {
        return new self();
    }

    public function add(CBORObject $key, CBORObject $value): self
    {
        if (! $key instanceof Normalizable) {
            throw new InvalidArgumentException('Invalid key. Shall be normalizable');
        }
        $this->data[$key->normalize()] = MapItem::create($key, $value);

        return $this;
    }

    public function has(int|string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    public function remove(int|string $index): self
    {
        if (! $this->has($index)) {
            return $this;
        }
        unset($this->data[$index]);
        $this->data = array_values($this->data);

        return $this;
    }

    public function get(int|string $index): CBORObject
    {
        if (! $this->has($index)) {
            throw new InvalidArgumentException('Index not found.');
        }

        return $this->data[$index]->getValue();
    }

    public function set(MapItem $object): self
    {
        $key = $object->getKey();
        if (! $key instanceof Normalizable) {
            throw new InvalidArgumentException('Invalid key. Shall be normalizable');
        }

        $this->data[$key->normalize()] = $object;

        return $this;
    }

    /**
     * @return Iterator<int, MapItem>
     */
    public function getIterator(): Iterator
    {
        return new ArrayIterator($this->data);
    }

    /**
     * @return mixed[]
     */
    public function normalize(): array
    {
        return array_reduce($this->data, static function (array $carry, MapItem $item): array {
            $key = $item->getKey();
            if (! $key instanceof Normalizable) {
                throw new InvalidArgumentException('Invalid key. Shall be normalizable');
            }
            $valueObject = $item->getValue();
            $carry[$key->normalize()] = $valueObject instanceof Normalizable ? $valueObject->normalize() : $valueObject;

            return $carry;
        }, []);
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
        if (! $offset instanceof CBORObject) {
            throw new InvalidArgumentException('Invalid key');
        }
        if (! $value instanceof CBORObject) {
            throw new InvalidArgumentException('Invalid value');
        }

        $this->set(MapItem::create($offset, $value));
    }

    public function offsetUnset($offset): void
    {
        $this->remove($offset);
    }
}
