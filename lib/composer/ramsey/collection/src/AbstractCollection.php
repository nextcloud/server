<?php

/**
 * This file is part of the ramsey/collection library
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @copyright Copyright (c) Ben Ramsey <ben@benramsey.com>
 * @license http://opensource.org/licenses/MIT MIT
 */

declare(strict_types=1);

namespace Ramsey\Collection;

use Closure;
use Ramsey\Collection\Exception\CollectionMismatchException;
use Ramsey\Collection\Exception\InvalidArgumentException;
use Ramsey\Collection\Exception\InvalidSortOrderException;
use Ramsey\Collection\Exception\OutOfBoundsException;
use Ramsey\Collection\Tool\TypeTrait;
use Ramsey\Collection\Tool\ValueExtractorTrait;
use Ramsey\Collection\Tool\ValueToStringTrait;

use function array_filter;
use function array_map;
use function array_merge;
use function array_search;
use function array_udiff;
use function array_uintersect;
use function current;
use function end;
use function in_array;
use function reset;
use function sprintf;
use function unserialize;
use function usort;

/**
 * This class provides a basic implementation of `CollectionInterface`, to
 * minimize the effort required to implement this interface
 *
 * @template T
 * @extends AbstractArray<T>
 * @implements CollectionInterface<T>
 */
abstract class AbstractCollection extends AbstractArray implements CollectionInterface
{
    use TypeTrait;
    use ValueToStringTrait;
    use ValueExtractorTrait;

    /**
     * @inheritDoc
     */
    public function add($element): bool
    {
        $this[] = $element;

        return true;
    }

    /**
     * @inheritDoc
     */
    public function contains($element, bool $strict = true): bool
    {
        return in_array($element, $this->data, $strict);
    }

    /**
     * @inheritDoc
     */
    public function offsetSet($offset, $value): void
    {
        if ($this->checkType($this->getType(), $value) === false) {
            throw new InvalidArgumentException(
                'Value must be of type ' . $this->getType() . '; value is '
                . $this->toolValueToString($value)
            );
        }

        if ($offset === null) {
            $this->data[] = $value;
        } else {
            $this->data[$offset] = $value;
        }
    }

    /**
     * @inheritDoc
     */
    public function remove($element): bool
    {
        if (($position = array_search($element, $this->data, true)) !== false) {
            unset($this->data[$position]);

            return true;
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function column(string $propertyOrMethod): array
    {
        $temp = [];

        foreach ($this->data as $item) {
            /** @var mixed $value */
            $value = $this->extractValue($item, $propertyOrMethod);

            /** @psalm-suppress MixedAssignment */
            $temp[] = $value;
        }

        return $temp;
    }

    /**
     * @inheritDoc
     */
    public function first()
    {
        if ($this->isEmpty()) {
            throw new OutOfBoundsException('Can\'t determine first item. Collection is empty');
        }

        reset($this->data);

        /** @var T $first */
        $first = current($this->data);

        return $first;
    }

    /**
     * @inheritDoc
     */
    public function last()
    {
        if ($this->isEmpty()) {
            throw new OutOfBoundsException('Can\'t determine last item. Collection is empty');
        }

        /** @var T $item */
        $item = end($this->data);
        reset($this->data);

        return $item;
    }

    public function sort(string $propertyOrMethod, string $order = self::SORT_ASC): CollectionInterface
    {
        if (!in_array($order, [self::SORT_ASC, self::SORT_DESC], true)) {
            throw new InvalidSortOrderException('Invalid sort order given: ' . $order);
        }

        $collection = clone $this;

        usort(
            $collection->data,
            /**
             * @param T $a
             * @param T $b
             */
            function ($a, $b) use ($propertyOrMethod, $order): int {
                /** @var mixed $aValue */
                $aValue = $this->extractValue($a, $propertyOrMethod);

                /** @var mixed $bValue */
                $bValue = $this->extractValue($b, $propertyOrMethod);

                return ($aValue <=> $bValue) * ($order === self::SORT_DESC ? -1 : 1);
            }
        );

        return $collection;
    }

    public function filter(callable $callback): CollectionInterface
    {
        $collection = clone $this;
        $collection->data = array_merge([], array_filter($collection->data, $callback));

        return $collection;
    }

    /**
     * {@inheritdoc}
     */
    public function where(string $propertyOrMethod, $value): CollectionInterface
    {
        return $this->filter(function ($item) use ($propertyOrMethod, $value) {
            /** @var mixed $accessorValue */
            $accessorValue = $this->extractValue($item, $propertyOrMethod);

            return $accessorValue === $value;
        });
    }

    public function map(callable $callback): CollectionInterface
    {
        return new Collection('mixed', array_map($callback, $this->data));
    }

    public function diff(CollectionInterface $other): CollectionInterface
    {
        $this->compareCollectionTypes($other);

        $diffAtoB = array_udiff($this->data, $other->toArray(), $this->getComparator());
        $diffBtoA = array_udiff($other->toArray(), $this->data, $this->getComparator());

        /** @var array<array-key, T> $diff */
        $diff = array_merge($diffAtoB, $diffBtoA);

        $collection = clone $this;
        $collection->data = $diff;

        return $collection;
    }

    public function intersect(CollectionInterface $other): CollectionInterface
    {
        $this->compareCollectionTypes($other);

        /** @var array<array-key, T> $intersect */
        $intersect = array_uintersect($this->data, $other->toArray(), $this->getComparator());

        $collection = clone $this;
        $collection->data = $intersect;

        return $collection;
    }

    public function merge(CollectionInterface ...$collections): CollectionInterface
    {
        $temp = [$this->data];

        foreach ($collections as $index => $collection) {
            if (!$collection instanceof static) {
                throw new CollectionMismatchException(
                    sprintf('Collection with index %d must be of type %s', $index, static::class)
                );
            }

            // When using generics (Collection.php, Set.php, etc),
            // we also need to make sure that the internal types match each other
            if ($collection->getType() !== $this->getType()) {
                throw new CollectionMismatchException(
                    sprintf('Collection items in collection with index %d must be of type %s', $index, $this->getType())
                );
            }

            $temp[] = $collection->toArray();
        }

        $merge = array_merge(...$temp);

        $collection = clone $this;
        $collection->data = $merge;

        return $collection;
    }

    /**
     * @inheritDoc
     */
    public function unserialize($serialized): void
    {
        /** @var array<array-key, T> $data */
        $data = unserialize($serialized, ['allowed_classes' => [$this->getType()]]);

        $this->data = $data;
    }

    /**
     * @param CollectionInterface<T> $other
     */
    private function compareCollectionTypes(CollectionInterface $other): void
    {
        if (!$other instanceof static) {
            throw new CollectionMismatchException('Collection must be of type ' . static::class);
        }

        // When using generics (Collection.php, Set.php, etc),
        // we also need to make sure that the internal types match each other
        if ($other->getType() !== $this->getType()) {
            throw new CollectionMismatchException('Collection items must be of type ' . $this->getType());
        }
    }

    private function getComparator(): Closure
    {
        return /**
             * @param T $a
             * @param T $b
             */
            function ($a, $b): int {
                // If the two values are object, we convert them to unique scalars.
                // If the collection contains mixed values (unlikely) where some are objects
                // and some are not, we leave them as they are.
                // The comparator should still work and the result of $a < $b should
                // be consistent but unpredictable since not documented.
                if (is_object($a) && is_object($b)) {
                    $a = spl_object_id($a);
                    $b = spl_object_id($b);
                }

                return $a === $b ? 0 : ($a < $b ? 1 : -1);
            };
    }
}
