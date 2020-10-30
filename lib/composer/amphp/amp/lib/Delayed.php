<?php

namespace Amp;

/**
 * Creates a promise that resolves itself with a given value after a number of milliseconds.
 *
 * @template-covariant TReturn
 * @template-implements Promise<TReturn>
 */
final class Delayed implements Promise
{
    use Internal\Placeholder;

    /** @var string|null Event loop watcher identifier. */
    private $watcher;

    /**
     * @param int     $time Milliseconds before succeeding the promise.
     * @param TReturn $value Succeed the promise with this value.
     */
    public function __construct(int $time, $value = null)
    {
        $this->watcher = Loop::delay($time, function () use ($value) {
            $this->watcher = null;
            $this->resolve($value);
        });
    }

    /**
     * References the internal watcher in the event loop, keeping the loop running while this promise is pending.
     *
     * @return self
     */
    public function reference(): self
    {
        if ($this->watcher !== null) {
            Loop::reference($this->watcher);
        }

        return $this;
    }

    /**
     * Unreferences the internal watcher in the event loop, allowing the loop to stop while this promise is pending if
     * no other events are pending in the loop.
     *
     * @return self
     */
    public function unreference(): self
    {
        if ($this->watcher !== null) {
            Loop::unreference($this->watcher);
        }

        return $this;
    }
}
