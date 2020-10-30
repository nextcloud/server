<?php

namespace Amp;

/**
 * Defines an asynchronous iterator over a set of values that is designed to be used within a coroutine.
 *
 * @template-covariant TValue
 */
interface Iterator
{
    /**
     * Succeeds with true if an emitted value is available by calling getCurrent() or false if the iterator has
     * resolved. If the iterator fails, the returned promise will fail with the same exception.
     *
     * @return Promise
     * @psalm-return Promise<bool>
     *
     * @throws \Error If the prior promise returned from this method has not resolved.
     * @throws \Throwable The exception used to fail the iterator.
     */
    public function advance(): Promise;

    /**
     * Gets the last emitted value or throws an exception if the iterator has completed.
     *
     * @return mixed  Value emitted from the iterator.
     * @psalm-return TValue
     *
     * @throws \Error If the iterator has resolved or advance() was not called before calling this method.
     * @throws \Throwable The exception used to fail the iterator.
     */
    public function getCurrent();
}
