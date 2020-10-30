<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Finder\Iterator;

/**
 * SortableIterator applies a sort on a given Iterator.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class SortableIterator implements \IteratorAggregate
{
    const SORT_BY_NONE = 0;
    const SORT_BY_NAME = 1;
    const SORT_BY_TYPE = 2;
    const SORT_BY_ACCESSED_TIME = 3;
    const SORT_BY_CHANGED_TIME = 4;
    const SORT_BY_MODIFIED_TIME = 5;
    const SORT_BY_NAME_NATURAL = 6;

    private $iterator;
    private $sort;

    /**
     * @param \Traversable $iterator The Iterator to filter
     * @param int|callable $sort     The sort type (SORT_BY_NAME, SORT_BY_TYPE, or a PHP callback)
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(\Traversable $iterator, $sort, bool $reverseOrder = false)
    {
        $this->iterator = $iterator;
        $order = $reverseOrder ? -1 : 1;

        if (self::SORT_BY_NAME === $sort) {
            $this->sort = static function (\SplFileInfo $a, \SplFileInfo $b) use ($order) {
                return $order * strcmp($a->getRealPath() ?: $a->getPathname(), $b->getRealPath() ?: $b->getPathname());
            };
        } elseif (self::SORT_BY_NAME_NATURAL === $sort) {
            $this->sort = static function (\SplFileInfo $a, \SplFileInfo $b) use ($order) {
                return $order * strnatcmp($a->getRealPath() ?: $a->getPathname(), $b->getRealPath() ?: $b->getPathname());
            };
        } elseif (self::SORT_BY_TYPE === $sort) {
            $this->sort = static function (\SplFileInfo $a, \SplFileInfo $b) use ($order) {
                if ($a->isDir() && $b->isFile()) {
                    return -$order;
                } elseif ($a->isFile() && $b->isDir()) {
                    return $order;
                }

                return $order * strcmp($a->getRealPath() ?: $a->getPathname(), $b->getRealPath() ?: $b->getPathname());
            };
        } elseif (self::SORT_BY_ACCESSED_TIME === $sort) {
            $this->sort = static function (\SplFileInfo $a, \SplFileInfo $b) use ($order) {
                return $order * ($a->getATime() - $b->getATime());
            };
        } elseif (self::SORT_BY_CHANGED_TIME === $sort) {
            $this->sort = static function (\SplFileInfo $a, \SplFileInfo $b) use ($order) {
                return $order * ($a->getCTime() - $b->getCTime());
            };
        } elseif (self::SORT_BY_MODIFIED_TIME === $sort) {
            $this->sort = static function (\SplFileInfo $a, \SplFileInfo $b) use ($order) {
                return $order * ($a->getMTime() - $b->getMTime());
            };
        } elseif (self::SORT_BY_NONE === $sort) {
            $this->sort = $order;
        } elseif (\is_callable($sort)) {
            $this->sort = $reverseOrder ? static function (\SplFileInfo $a, \SplFileInfo $b) use ($sort) { return -$sort($a, $b); } : $sort;
        } else {
            throw new \InvalidArgumentException('The SortableIterator takes a PHP callable or a valid built-in sort algorithm as an argument.');
        }
    }

    /**
     * @return \Traversable
     */
    public function getIterator()
    {
        if (1 === $this->sort) {
            return $this->iterator;
        }

        $array = iterator_to_array($this->iterator, true);

        if (-1 === $this->sort) {
            $array = array_reverse($array);
        } else {
            uasort($array, $this->sort);
        }

        return new \ArrayIterator($array);
    }
}
