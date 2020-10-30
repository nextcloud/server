<?php

namespace Amp\ByteStream;

use Amp\Deferred;
use Amp\Failure;
use Amp\Iterator;
use Amp\Promise;

final class IteratorStream implements InputStream
{
    /** @var Iterator<string> */
    private $iterator;
    /** @var \Throwable|null */
    private $exception;
    /** @var bool */
    private $pending = false;

    /**
     * @psam-param Iterator<string> $iterator
     */
    public function __construct(Iterator $iterator)
    {
        $this->iterator = $iterator;
    }

    /** @inheritdoc */
    public function read(): Promise
    {
        if ($this->exception) {
            return new Failure($this->exception);
        }

        if ($this->pending) {
            throw new PendingReadError;
        }

        $this->pending = true;
        /** @var Deferred<string|null> $deferred */
        $deferred = new Deferred;

        $this->iterator->advance()->onResolve(function ($error, $hasNextElement) use ($deferred) {
            $this->pending = false;

            if ($error) {
                $this->exception = $error;
                $deferred->fail($error);
            } elseif ($hasNextElement) {
                $chunk = $this->iterator->getCurrent();

                if (!\is_string($chunk)) {
                    $this->exception = new StreamException(\sprintf(
                        "Unexpected iterator value of type '%s', expected string",
                        \is_object($chunk) ? \get_class($chunk) : \gettype($chunk)
                    ));

                    $deferred->fail($this->exception);

                    return;
                }

                $deferred->resolve($chunk);
            } else {
                $deferred->resolve();
            }
        });

        return $deferred->promise();
    }
}
