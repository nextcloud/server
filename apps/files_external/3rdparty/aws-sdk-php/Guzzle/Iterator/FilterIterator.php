<?php

namespace Guzzle\Iterator;

use Guzzle\Common\Exception\InvalidArgumentException;

/**
 * Filters values using a callback
 *
 * Used when PHP 5.4's {@see \CallbackFilterIterator} is not available
 */
class FilterIterator extends \FilterIterator
{
    /** @var mixed Callback used for filtering */
    protected $callback;

    /**
     * @param \Iterator      $iterator Traversable iterator
     * @param array|\Closure $callback Callback used for filtering. Return true to keep or false to filter.
     *
     * @throws InvalidArgumentException if the callback if not callable
     */
    public function __construct(\Iterator $iterator, $callback)
    {
        parent::__construct($iterator);
        if (!is_callable($callback)) {
            throw new InvalidArgumentException('The callback must be callable');
        }
        $this->callback = $callback;
    }

    public function accept()
    {
        return call_user_func($this->callback, $this->current());
    }
}
