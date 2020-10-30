<?php

namespace Amp;

use React\Promise\PromiseInterface as ReactPromise;

/**
 * Creates a failed promise using the given exception.
 *
 * @template-covariant TValue
 * @template-implements Promise<TValue>
 */
final class Failure implements Promise
{
    /** @var \Throwable $exception */
    private $exception;

    /**
     * @param \Throwable $exception Rejection reason.
     */
    public function __construct(\Throwable $exception)
    {
        $this->exception = $exception;
    }

    /**
     * {@inheritdoc}
     */
    public function onResolve(callable $onResolved)
    {
        try {
            /** @var mixed $result */
            $result = $onResolved($this->exception, null);

            if ($result === null) {
                return;
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
