<?php

namespace Amp;

/**
 * Emitter is a container for an iterator that can emit values using the emit() method and completed using the
 * complete() and fail() methods of this object. The contained iterator may be accessed using the iterate()
 * method. This object should not be part of a public API, but used internally to create and emit values to an
 * iterator.
 *
 * @template TValue
 */
final class Emitter
{
    /** @var Iterator<TValue> Has public emit, complete, and fail methods. */
    private $emitter;

    /** @var Iterator<TValue> Hides producer methods. */
    private $iterator;

    public function __construct()
    {
        $this->emitter = new class implements Iterator {
            use Internal\Producer {
                emit as public;
                complete as public;
                fail as public;
            }
        };

        $this->iterator = new Internal\PrivateIterator($this->emitter);
    }

    /**
     * @return Iterator
     * @psalm-return Iterator<TValue>
     */
    public function iterate(): Iterator
    {
        return $this->iterator;
    }

    /**
     * Emits a value to the iterator.
     *
     * @param mixed $value
     *
     * @psalm-param TValue $value
     *
     * @return Promise
     * @psalm-return Promise<null>
     * @psalm-suppress MixedInferredReturnType
     * @psalm-suppress MixedReturnStatement
     */
    public function emit($value): Promise
    {
        /** @psalm-suppress UndefinedInterfaceMethod */
        return $this->emitter->emit($value);
    }

    /**
     * Completes the iterator.
     *
     * @return void
     */
    public function complete()
    {
        /** @psalm-suppress UndefinedInterfaceMethod */
        $this->emitter->complete();
    }

    /**
     * Fails the iterator with the given reason.
     *
     * @param \Throwable $reason
     *
     * @return void
     */
    public function fail(\Throwable $reason)
    {
        /** @psalm-suppress UndefinedInterfaceMethod */
        $this->emitter->fail($reason);
    }
}
