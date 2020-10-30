<?php

namespace Amp\Internal;

use Amp\Deferred;
use Amp\Failure;
use Amp\Promise;
use Amp\Success;
use React\Promise\PromiseInterface as ReactPromise;

/**
 * Trait used by Iterator implementations. Do not use this trait in your code, instead compose your class from one of
 * the available classes implementing \Amp\Iterator.
 * Note that it is the responsibility of the user of this trait to ensure that listeners have a chance to listen first
 * before emitting values.
 *
 * @internal
 * @template-covariant TValue
 */
trait Producer
{
    /** @var Promise|null */
    private $complete;

    /** @var mixed[] */
    private $values = [];

    /** @var Deferred[] */
    private $backPressure = [];

    /** @var int */
    private $consumePosition = -1;

    /** @var int */
    private $emitPosition = -1;

    /** @var Deferred|null */
    private $waiting;

    /** @var null|array */
    private $resolutionTrace;

    /**
     * {@inheritdoc}
     *
     * @return Promise<bool>
     */
    public function advance(): Promise
    {
        if ($this->waiting !== null) {
            throw new \Error("The prior promise returned must resolve before invoking this method again");
        }

        unset($this->values[$this->consumePosition]);

        $position = ++$this->consumePosition;

        if (\array_key_exists($position, $this->values)) {
            \assert(isset($this->backPressure[$position]));
            $deferred = $this->backPressure[$position];
            unset($this->backPressure[$position]);
            $deferred->resolve();

            return new Success(true);
        }

        if ($this->complete) {
            return $this->complete;
        }

        $this->waiting = new Deferred;

        return $this->waiting->promise();
    }

    /**
     * {@inheritdoc}
     *
     * @return TValue
     */
    public function getCurrent()
    {
        if (empty($this->values) && $this->complete) {
            throw new \Error("The iterator has completed");
        }

        if (!\array_key_exists($this->consumePosition, $this->values)) {
            throw new \Error("Promise returned from advance() must resolve before calling this method");
        }

        return $this->values[$this->consumePosition];
    }

    /**
     * Emits a value from the iterator. The returned promise is resolved once the emitted value has been consumed.
     *
     * @param mixed $value
     *
     * @return Promise
     * @psalm-return Promise<null>
     *
     * @throws \Error If the iterator has completed.
     */
    private function emit($value): Promise
    {
        if ($this->complete) {
            throw new \Error("Iterators cannot emit values after calling complete");
        }

        if ($value instanceof ReactPromise) {
            $value = Promise\adapt($value);
        }

        if ($value instanceof Promise) {
            $deferred = new Deferred;
            $value->onResolve(function ($e, $v) use ($deferred) {
                if ($this->complete) {
                    $deferred->fail(
                        new \Error("The iterator was completed before the promise result could be emitted")
                    );
                    return;
                }

                if ($e) {
                    $this->fail($e);
                    $deferred->fail($e);
                    return;
                }

                $deferred->resolve($this->emit($v));
            });

            return $deferred->promise();
        }

        $position = ++$this->emitPosition;

        $this->values[$position] = $value;

        if ($this->waiting !== null) {
            $waiting = $this->waiting;
            $this->waiting = null;
            $waiting->resolve(true);
            return new Success; // Consumer was already waiting for a new value, so back-pressure is unnecessary.
        }

        $this->backPressure[$position] = $pressure = new Deferred;

        return $pressure->promise();
    }

    /**
     * Completes the iterator.
     *
     * @return void
     *
     * @throws \Error If the iterator has already been completed.
     */
    private function complete()
    {
        if ($this->complete) {
            $message = "Iterator has already been completed";

            if (isset($this->resolutionTrace)) {
                $trace = formatStacktrace($this->resolutionTrace);
                $message .= ". Previous completion trace:\n\n{$trace}\n\n";
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

        $this->complete = new Success(false);

        if ($this->waiting !== null) {
            $waiting = $this->waiting;
            $this->waiting = null;
            $waiting->resolve($this->complete);
        }
    }

    /**
     * @param \Throwable $exception
     *
     * @return void
     */
    private function fail(\Throwable $exception)
    {
        $this->complete = new Failure($exception);

        if ($this->waiting !== null) {
            $waiting = $this->waiting;
            $this->waiting = null;
            $waiting->resolve($this->complete);
        }
    }
}
