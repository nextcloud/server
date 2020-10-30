<?php

namespace Amp\Internal;

use Amp\Coroutine;
use Amp\Loop;
use Amp\Promise;
use React\Promise\PromiseInterface as ReactPromise;

/**
 * Stores a set of functions to be invoked when a promise is resolved.
 *
 * @internal
 * @psalm-internal Amp\Internal
 */
class ResolutionQueue
{
    /** @var array<array-key, callable(\Throwable|null, mixed): (Promise|\React\Promise\PromiseInterface|\Generator<mixed,
     *     Promise|\React\Promise\PromiseInterface|array<array-key, Promise|\React\Promise\PromiseInterface>, mixed,
     *     mixed>|null) | callable(\Throwable|null, mixed): void> */
    private $queue = [];

    /**
     * @param callable|null $callback Initial callback to add to queue.
     *
     * @psalm-param null|callable(\Throwable|null, mixed): (Promise|\React\Promise\PromiseInterface|\Generator<mixed,
     *     Promise|\React\Promise\PromiseInterface|array<array-key, Promise|\React\Promise\PromiseInterface>, mixed,
     *     mixed>|null) | callable(\Throwable|null, mixed): void $callback
     */
    public function __construct(callable $callback = null)
    {
        if ($callback !== null) {
            $this->push($callback);
        }
    }

    /**
     * Unrolls instances of self to avoid blowing up the call stack on resolution.
     *
     * @param callable $callback
     *
     * @psalm-param callable(\Throwable|null, mixed): (Promise|\React\Promise\PromiseInterface|\Generator<mixed,
     *     Promise|\React\Promise\PromiseInterface|array<array-key, Promise|\React\Promise\PromiseInterface>, mixed,
     *     mixed>|null) | callable(\Throwable|null, mixed): void $callback
     *
     * @return void
     */
    public function push(callable $callback)
    {
        if ($callback instanceof self) {
            $this->queue = \array_merge($this->queue, $callback->queue);
            return;
        }

        $this->queue[] = $callback;
    }

    /**
     * Calls each callback in the queue, passing the provided values to the function.
     *
     * @param \Throwable|null $exception
     * @param mixed           $value
     *
     * @return void
     */
    public function __invoke($exception, $value)
    {
        foreach ($this->queue as $callback) {
            try {
                $result = $callback($exception, $value);

                if ($result === null) {
                    continue;
                }

                if ($result instanceof \Generator) {
                    $result = new Coroutine($result);
                }

                if ($result instanceof Promise || $result instanceof ReactPromise) {
                    Promise\rethrow($result);
                }
            } catch (\Throwable $exception) {
                Loop::defer(static function () use ($exception) {
                    throw $exception;
                });
            }
        }
    }
}
