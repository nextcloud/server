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

use Ramsey\Collection\Exception\InvalidArgumentException;
use Ramsey\Collection\Exception\NoSuchElementException;

/**
 * This class provides a basic implementation of `DoubleEndedQueueInterface`, to
 * minimize the effort required to implement this interface.
 *
 * @template T
 * @extends Queue<T>
 * @implements DoubleEndedQueueInterface<T>
 */
class DoubleEndedQueue extends Queue implements DoubleEndedQueueInterface
{
    /**
     * Index of the last element in the queue.
     *
     * @var int
     */
    private $tail = -1;

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

        $this->tail++;

        $this->data[$this->tail] = $value;
    }

    /**
     * @inheritDoc
     */
    public function addFirst($element): bool
    {
        if ($this->checkType($this->getType(), $element) === false) {
            throw new InvalidArgumentException(
                'Value must be of type ' . $this->getType() . '; value is '
                . $this->toolValueToString($element)
            );
        }

        $this->index--;

        $this->data[$this->index] = $element;

        return true;
    }

    /**
     * @inheritDoc
     */
    public function addLast($element): bool
    {
        return $this->add($element);
    }

    /**
     * @inheritDoc
     */
    public function offerFirst($element): bool
    {
        try {
            return $this->addFirst($element);
        } catch (InvalidArgumentException $e) {
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function offerLast($element): bool
    {
        return $this->offer($element);
    }

    /**
     * @inheritDoc
     */
    public function removeFirst()
    {
        return $this->remove();
    }

    /**
     * @inheritDoc
     */
    public function removeLast()
    {
        $tail = $this->pollLast();

        if ($tail === null) {
            throw new NoSuchElementException('Can\'t return element from Queue. Queue is empty.');
        }

        return $tail;
    }

    /**
     * @inheritDoc
     */
    public function pollFirst()
    {
        return $this->poll();
    }

    /**
     * @inheritDoc
     */
    public function pollLast()
    {
        if ($this->count() === 0) {
            return null;
        }

        $tail = $this[$this->tail];

        unset($this[$this->tail]);
        $this->tail--;

        return $tail;
    }

    /**
     * @inheritDoc
     */
    public function firstElement()
    {
        return $this->element();
    }

    /**
     * @inheritDoc
     */
    public function lastElement()
    {
        if ($this->count() === 0) {
            throw new NoSuchElementException('Can\'t return element from Queue. Queue is empty.');
        }

        return $this->data[$this->tail];
    }

    /**
     * @inheritDoc
     */
    public function peekFirst()
    {
        return $this->peek();
    }

    /**
     * @inheritDoc
     */
    public function peekLast()
    {
        if ($this->count() === 0) {
            return null;
        }

        return $this->data[$this->tail];
    }
}
