<?php

namespace Amp;

/**
 * @template-covariant TValue
 * @template-implements Iterator<TValue>
 */
final class Producer implements Iterator
{
    /**
     * @use Internal\Producer<TValue>
     */
    use CallableMaker, Internal\Producer;

    /**
     * @param callable(callable(TValue):Promise):\Generator $producer
     *
     * @throws \Error Thrown if the callable does not return a Generator.
     */
    public function __construct(callable $producer)
    {
        $result = $producer($this->callableFromInstanceMethod("emit"));

        if (!$result instanceof \Generator) {
            throw new \Error("The callable did not return a Generator");
        }

        $coroutine = new Coroutine($result);
        $coroutine->onResolve(function ($exception) {
            if ($this->complete) {
                return;
            }

            if ($exception) {
                $this->fail($exception);
                return;
            }

            $this->complete();
        });
    }
}
