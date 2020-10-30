<?php

namespace Amp;

/**
 * Creates a promise that calls $promisor only when the result of the promise is requested (i.e. onResolve() is called
 * on the promise). $promisor can return a promise or any value. If $promisor throws an exception, the promise fails
 * with that exception. If $promisor returns a Generator, it will be run as a coroutine.
 */
final class LazyPromise implements Promise
{
    /** @var callable|null */
    private $promisor;

    /** @var Promise|null */
    private $promise;

    /**
     * @param callable $promisor Function which starts an async operation, returning a Promise (or any value).
     *     Generators will be run as a coroutine.
     */
    public function __construct(callable $promisor)
    {
        $this->promisor = $promisor;
    }

    /**
     * {@inheritdoc}
     */
    public function onResolve(callable $onResolved)
    {
        if ($this->promise === null) {
            \assert($this->promisor !== null);

            $provider = $this->promisor;
            $this->promisor = null;
            $this->promise = call($provider);
        }

        \assert($this->promise !== null);

        $this->promise->onResolve($onResolved);
    }
}
