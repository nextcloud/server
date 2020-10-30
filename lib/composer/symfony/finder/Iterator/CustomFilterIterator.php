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
 * CustomFilterIterator filters files by applying anonymous functions.
 *
 * The anonymous function receives a \SplFileInfo and must return false
 * to remove files.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class CustomFilterIterator extends \FilterIterator
{
    private $filters = [];

    /**
     * @param \Iterator  $iterator The Iterator to filter
     * @param callable[] $filters  An array of PHP callbacks
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(\Iterator $iterator, array $filters)
    {
        foreach ($filters as $filter) {
            if (!\is_callable($filter)) {
                throw new \InvalidArgumentException('Invalid PHP callback.');
            }
        }
        $this->filters = $filters;

        parent::__construct($iterator);
    }

    /**
     * Filters the iterator values.
     *
     * @return bool true if the value should be kept, false otherwise
     */
    public function accept()
    {
        $fileinfo = $this->current();

        foreach ($this->filters as $filter) {
            if (false === $filter($fileinfo)) {
                return false;
            }
        }

        return true;
    }
}
