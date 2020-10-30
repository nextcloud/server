<?php

namespace Amp\Internal;

use Amp\Iterator;
use Amp\Promise;

/**
 * Wraps an Iterator instance that has public methods to emit, complete, and fail into an object that only allows
 * access to the public API methods.
 *
 * @template-covariant TValue
 * @template-implements Iterator<TValue>
 */
final class PrivateIterator implements Iterator
{
    /** @var Iterator<TValue> */
    private $iterator;

    /**
     * @param Iterator $iterator
     *
     * @psalm-param Iterator<TValue> $iterator
     */
    public function __construct(Iterator $iterator)
    {
        $this->iterator = $iterator;
    }

    /**
     * @return Promise<bool>
     */
    public function advance(): Promise
    {
        return $this->iterator->advance();
    }

    /**
     * @psalm-return TValue
     */
    public function getCurrent()
    {
        return $this->iterator->getCurrent();
    }
}
