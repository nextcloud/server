<?php

namespace Amp\Internal;

use Amp\Coroutine;
use Amp\Failure;
use Amp\Loop;
use Amp\Promise;
use React\Promise\PromiseInterface as ReactPromise;

/**
 * Trait used by Promise implementations. Do not use this trait in your code, instead compose your class from one of
 * the available classes implementing \Amp\Promise.
 *
 * @internal
 */
trait Placeholder
{
    /** @var bool */
    private $resolved = false;

    /** @var mixed */
    private $result;

    /** @var ResolutionQueue|null|callable(\Throwable|null, mixed): (Promise|\React\Promise\PromiseInterface|\Generator<mixed,
     *     Promise|\React\Promise\PromiseInterface|array<array-key, Promise|\React\Promise\PromiseInterface>, mixed,
     *     mixed>|null)|callable(\Throwable|null, mixed): void */
    private $onResolved;

    /** @var null|array */
    private $resolutionTrace;

    /**
     * @inheritdoc
     */
    public function onResolve(callable $onResolved)
    {
        if ($this->resolved) {
            if ($this->result instanceof Promise) {
                $this->result->onResolve($onResolved);
                return;
            }

            try {
                /** @var mixed $result */
                $result = $onResolved(null, $this->result);

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
            return;
        }

        if (null === $this->onResolved) {
            $this->onResolved = $onResolved;
            return;
        }

        if (!$this->onResolved instanceof ResolutionQueue) {
            /** @psalm-suppress InternalClass */
            $this->onResolved = new ResolutionQueue($this->onResolved);
        }

        /** @psalm-suppress InternalMethod */
        $this->onResolved->push($onResolved);
    }

    public function __destruct()
    {
        try {
            $this->result = null;
        } catch (\Throwable $e) {
            Loop::defer(static function () use ($e) {
                throw $e;
            });
        }
    }

    /**
     * @param mixed $value
     *
     * @return void
     *
     * @throws \Error Thrown if the promise has already been resolved.
     */
    private function resolve($value = null)
    {
        if ($this->resolved) {
            $message = "Promise has already been resolved";

            if (isset($this->resolutionTrace)) {
                $trace = formatStacktrace($this->resolutionTrace);
                $message .= ". Previous resolution trace:\n\n{$trace}\n\n";
            } else {
                // @codeCoverageIgnoreStart
                $message .= ", define environment variable AMP_DEBUG or const AMP_DEBUG = true and enable assertions "
                    . "for a stacktrace of the previous resolution.";
                // @codeCoverageIgnoreEnd
            }

            throw new \Error($message);
        }

        \assert((function () {
            $env = \getenv("AMP_DEBUG") ?: "0";
            if (($env !== "0" && $env !== "false") || (\defined("AMP_DEBUG") && \AMP_DEBUG)) {
                $trace = \debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS);
                \array_shift($trace); // remove current closure
                $this->resolutionTrace = $trace;
            }

            return true;
        })());

        if ($value instanceof ReactPromise) {
            $value = Promise\adapt($value);
        }

        $this->resolved = true;
        $this->result = $value;

        if ($this->onResolved === null) {
            return;
        }

        $onResolved = $this->onResolved;
        $this->onResolved = null;

        if ($this->result instanceof Promise) {
            $this->result->onResolve($onResolved);
            return;
        }

        try {
            /** @var mixed $result */
            $result = $onResolved(null, $this->result);
            $onResolved = null; // allow garbage collection of $onResolved, to catch any exceptions from destructors

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

    /**
     * @param \Throwable $reason Failure reason.
     *
     * @return void
     */
    private function fail(\Throwable $reason)
    {
        $this->resolve(new Failure($reason));
    }
}
