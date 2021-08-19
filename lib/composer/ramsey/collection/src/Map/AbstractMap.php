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

namespace Ramsey\Collection\Map;

use Ramsey\Collection\AbstractArray;
use Ramsey\Collection\Exception\InvalidArgumentException;

use function array_key_exists;
use function array_keys;
use function in_array;

/**
 * This class provides a basic implementation of `MapInterface`, to minimize the
 * effort required to implement this interface.
 *
 * @template T
 * @extends AbstractArray<T>
 * @implements MapInterface<T>
 */
abstract class AbstractMap extends AbstractArray implements MapInterface
{
    /**
     * @inheritDoc
     */
    public function offsetSet($offset, $value): void
    {
        if ($offset === null) {
            throw new InvalidArgumentException(
                'Map elements are key/value pairs; a key must be provided for '
                . 'value ' . var_export($value, true)
            );
        }

        $this->data[$offset] = $value;
    }

    /**
     * @inheritDoc
     */
    public function containsKey($key): bool
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * @inheritDoc
     */
    public function containsValue($value): bool
    {
        return in_array($value, $this->data, true);
    }

    /**
     * @inheritDoc
     */
    public function keys(): array
    {
        return array_keys($this->data);
    }

    /**
     * @inheritDoc
     */
    public function get($key, $defaultValue = null)
    {
        if (!$this->containsKey($key)) {
            return $defaultValue;
        }

        return $this[$key];
    }

    /**
     * @inheritDoc
     */
    public function put($key, $value)
    {
        $previousValue = $this->get($key);
        $this[$key] = $value;

        return $previousValue;
    }

    /**
     * @inheritDoc
     */
    public function putIfAbsent($key, $value)
    {
        $currentValue = $this->get($key);

        if ($currentValue === null) {
            $this[$key] = $value;
        }

        return $currentValue;
    }

    /**
     * @inheritDoc
     */
    public function remove($key)
    {
        $previousValue = $this->get($key);
        unset($this[$key]);

        return $previousValue;
    }

    /**
     * @inheritDoc
     */
    public function removeIf($key, $value): bool
    {
        if ($this->get($key) === $value) {
            unset($this[$key]);

            return true;
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function replace($key, $value)
    {
        $currentValue = $this->get($key);

        if ($this->containsKey($key)) {
            $this[$key] = $value;
        }

        return $currentValue;
    }

    /**
     * @inheritDoc
     */
    public function replaceIf($key, $oldValue, $newValue): bool
    {
        if ($this->get($key) === $oldValue) {
            $this[$key] = $newValue;

            return true;
        }

        return false;
    }
}
