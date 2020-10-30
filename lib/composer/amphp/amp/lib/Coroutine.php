<?php

namespace Amp;

use React\Promise\PromiseInterface as ReactPromise;

/**
 * Creates a promise from a generator function yielding promises.
 *
 * When a promise is yielded, execution of the generator is interrupted until the promise is resolved. A success
 * value is sent into the generator, while a failure reason is thrown into the generator. Using a coroutine,
 * asynchronous code can be written without callbacks and be structured like synchronous code.
 *
 * @template-covariant TReturn
 * @template-implements Promise<TReturn>
 */
final class Coroutine implements Promise
{
    use Internal\Placeholder;

    /**
     * Attempts to transform the non-promise yielded from the generator into a promise, otherwise returns an instance
     * `Amp\Failure` failed with an instance of `Amp\InvalidYieldError`.
     *
     * @param mixed      $yielded Non-promise yielded from generator.
     * @param \Generator $generator No type for performance, we already know the type.
     *
     * @return Promise
     */
    private static function transform($yielded, $generator): Promise
    {
        $exception = null; // initialize here, see https://github.com/vimeo/psalm/issues/2951

        try {
            if (\is_array($yielded)) {
                return Promise\all($yielded);
            }

            if ($yielded instanceof ReactPromise) {
                return Promise\adapt($yielded);
            }

            // No match, continue to returning Failure below.
        } catch (\Throwable $exception) {
            // Conversion to promise failed, fall-through to returning Failure below.
        }

        return new Failure(new InvalidYieldError(
            $generator,
            \sprintf(
                "Unexpected yield; Expected an instance of %s or %s or an array of such instances",
                Promise::class,
                ReactPromise::class
            ),
            $exception
        ));
    }

    /**
     * @param \Generator $generator
     * @psalm-param \Generator<mixed,Promise|ReactPromise|array<array-key,
     *     Promise|ReactPromise>,mixed,Promise<TReturn>|ReactPromise|TReturn> $generator
     */
    public function __construct(\Generator $generator)
    {
        try {
            $yielded = $generator->current();

            if (!$yielded instanceof Promise) {
                if (!$generator->valid()) {
                    $this->resolve($generator->getReturn());
                    return;
                }

                $yielded = self::transform($yielded, $generator);
            }
        } catch (\Throwable $exception) {
            $this->fail($exception);
            return;
        }

        /**
         * @param \Throwable|null $e Exception to be thrown into the generator.
         * @param mixed           $v Value to be sent into the generator.
         *
         * @return void
         *
         * @psalm-suppress MissingClosureParamType
         * @psalm-suppress MissingClosureReturnType
         */
        $onResolve = function (\Throwable $e = null, $v) use ($generator, &$onResolve) {
            /** @var bool $immediate Used to control iterative coroutine continuation. */
            static $immediate = true;

            /** @var \Throwable|null $exception Promise failure reason when executing next coroutine step, null at all other times. */
            static $exception;

            /** @var mixed $value Promise success value when executing next coroutine step, null at all other times. */
            static $value;

            $exception = $e;
            /** @psalm-suppress MixedAssignment */
            $value = $v;

            if (!$immediate) {
                $immediate = true;
                return;
            }

            try {
                try {
                    do {
                        if ($exception) {
                            // Throw exception at current execution point.
                            $yielded = $generator->throw($exception);
                        } else {
                            // Send the new value and execute to next yield statement.
                            $yielded = $generator->send($value);
                        }

                        if (!$yielded instanceof Promise) {
                            if (!$generator->valid()) {
                                $this->resolve($generator->getReturn());
                                $onResolve = null;
                                return;
                            }

                            $yielded = self::transform($yielded, $generator);
                        }

                        $immediate = false;
                        $yielded->onResolve($onResolve);
                    } while ($immediate);

                    $immediate = true;
                } catch (\Throwable $exception) {
                    $this->fail($exception);
                    $onResolve = null;
                } finally {
                    $exception = null;
                    $value = null;
                }
            } catch (\Throwable $e) {
                Loop::defer(static function () use ($e) {
                    throw $e;
                });
            }
        };

        try {
            $yielded->onResolve($onResolve);

            unset($generator, $yielded, $onResolve);
        } catch (\Throwable $e) {
            Loop::defer(static function () use ($e) {
                throw $e;
            });
        }
    }
}
