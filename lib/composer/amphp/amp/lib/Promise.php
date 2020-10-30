<?php

namespace Amp;

/**
 * Representation of the future value of an asynchronous operation.
 *
 * @template-covariant TValue
 * @psalm-yield TValue
 */
interface Promise
{
    /**
     * Registers a callback to be invoked when the promise is resolved.
     *
     * If this method is called multiple times, additional handlers will be registered instead of replacing any already
     * existing handlers.
     *
     * If the promise is already resolved, the callback MUST be executed immediately.
     *
     * Exceptions MUST NOT be thrown from this method. Any exceptions thrown from invoked callbacks MUST be
     * forwarded to the event-loop error handler.
     *
     * Note: You shouldn't implement this interface yourself. Instead, provide a method that returns a promise for the
     * operation you're implementing. Objects other than pure placeholders implementing it are a very bad idea.
     *
     * @param callable $onResolved The first argument shall be `null` on success, while the second shall be `null` on
     *     failure.
     *
     * @psalm-param callable(\Throwable|null, mixed): (Promise|\React\Promise\PromiseInterface|\Generator<mixed,
     *     Promise|\React\Promise\PromiseInterface|array<array-key, Promise|\React\Promise\PromiseInterface>, mixed,
     *     mixed>|null) | callable(\Throwable|null, mixed): void $onResolved
     *
     * @return void
     */
    public function onResolve(callable $onResolved);
}
