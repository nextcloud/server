<?php

declare(strict_types=1);

/*
 * This file is part of the JsonSchema package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JsonSchema\Iterator;

/**
 * @package JsonSchema\Iterator
 *
 * @author Joost Nijhuis <jnijhuis81@gmail.com>
 */
class ObjectIterator implements \Iterator, \Countable
{
    /** @var object */
    private $object;

    /** @var int */
    private $position = 0;

    /** @var array */
    private $data = [];

    /** @var bool */
    private $initialized = false;

    /**
     * @param object $object
     */
    public function __construct($object)
    {
        $this->object = $object;
    }

    /**
     * {@inheritdoc}
     */
    #[\ReturnTypeWillChange]
    public function current()
    {
        $this->initialize();

        return $this->data[$this->position];
    }

    /**
     * {@inheritdoc}
     */
    public function next(): void
    {
        $this->initialize();
        $this->position++;
    }

    /**
     * {@inheritdoc}
     */
    public function key(): int
    {
        $this->initialize();

        return $this->position;
    }

    /**
     * {@inheritdoc}
     */
    public function valid(): bool
    {
        $this->initialize();

        return isset($this->data[$this->position]);
    }

    /**
     * {@inheritdoc}
     */
    public function rewind(): void
    {
        $this->initialize();
        $this->position = 0;
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        $this->initialize();

        return count($this->data);
    }

    /**
     * Initializer
     */
    private function initialize()
    {
        if (!$this->initialized) {
            $this->data = $this->buildDataFromObject($this->object);
            $this->initialized = true;
        }
    }

    /**
     * @param object $object
     *
     * @return array
     */
    private function buildDataFromObject($object)
    {
        $result = [];

        $stack = new \SplStack();
        $stack->push($object);

        while (!$stack->isEmpty()) {
            $current = $stack->pop();
            if (is_object($current)) {
                array_push($result, $current);
            }

            foreach ($this->getDataFromItem($current) as $propertyName => $propertyValue) {
                if (is_object($propertyValue) || is_array($propertyValue)) {
                    $stack->push($propertyValue);
                }
            }
        }

        return $result;
    }

    /**
     * @param object|array $item
     *
     * @return array
     */
    private function getDataFromItem($item)
    {
        if (!is_object($item) && !is_array($item)) {
            return [];
        }

        return is_object($item) ? get_object_vars($item) : $item;
    }
}
