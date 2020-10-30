<?php

namespace Amp\ByteStream;

use Amp\Coroutine;
use Amp\Deferred;
use Amp\Failure;
use Amp\Promise;
use Amp\Success;

/**
 * Creates a buffered message from an InputStream. The message can be consumed in chunks using the read() API or it may
 * be buffered and accessed in its entirety by waiting for the promise to resolve.
 *
 * Other implementations may extend this class to add custom properties such as a `isBinary()` flag for WebSocket
 * messages.
 *
 * Buffering Example:
 *
 * $stream = new Message($inputStream);
 * $content = yield $stream;
 *
 * Streaming Example:
 *
 * $stream = new Message($inputStream);
 *
 * while (($chunk = yield $stream->read()) !== null) {
 *     // Immediately use $chunk, reducing memory consumption since the entire message is never buffered.
 * }
 *
 * @deprecated Use Amp\ByteStream\Payload instead.
 */
class Message implements InputStream, Promise
{
    /** @var InputStream */
    private $source;

    /** @var string */
    private $buffer = "";

    /** @var Deferred|null */
    private $pendingRead;

    /** @var Coroutine|null */
    private $coroutine;

    /** @var bool True if onResolve() has been called. */
    private $buffering = false;

    /** @var Deferred|null */
    private $backpressure;

    /** @var bool True if the iterator has completed. */
    private $complete = false;

    /** @var \Throwable|null Used to fail future reads on failure. */
    private $error;

    /**
     * @param InputStream $source An iterator that only emits strings.
     */
    public function __construct(InputStream $source)
    {
        $this->source = $source;
    }

    private function consume(): \Generator
    {
        while (($chunk = yield $this->source->read()) !== null) {
            $buffer = $this->buffer .= $chunk;

            if ($buffer === "") {
                continue; // Do not succeed reads with empty string.
            } elseif ($this->pendingRead) {
                $deferred = $this->pendingRead;
                $this->pendingRead = null;
                $this->buffer = "";
                $deferred->resolve($buffer);
                $buffer = ""; // Destroy last emitted chunk to free memory.
            } elseif (!$this->buffering) {
                $buffer = ""; // Destroy last emitted chunk to free memory.
                $this->backpressure = new Deferred;
                yield $this->backpressure->promise();
            }
        }

        $this->complete = true;

        if ($this->pendingRead) {
            $deferred = $this->pendingRead;
            $this->pendingRead = null;
            $deferred->resolve($this->buffer !== "" ? $this->buffer : null);
            $this->buffer = "";
        }

        return $this->buffer;
    }

    /** @inheritdoc */
    final public function read(): Promise
    {
        if ($this->pendingRead) {
            throw new PendingReadError;
        }

        if ($this->coroutine === null) {
            $this->coroutine = new Coroutine($this->consume());
            $this->coroutine->onResolve(function ($error) {
                if ($error) {
                    $this->error = $error;
                }

                if ($this->pendingRead) {
                    $deferred = $this->pendingRead;
                    $this->pendingRead = null;
                    $deferred->fail($error);
                }
            });
        }

        if ($this->error) {
            return new Failure($this->error);
        }

        if ($this->buffer !== "") {
            $buffer = $this->buffer;
            $this->buffer = "";

            if ($this->backpressure) {
                $backpressure = $this->backpressure;
                $this->backpressure = null;
                $backpressure->resolve();
            }

            return new Success($buffer);
        }

        if ($this->complete) {
            return new Success;
        }

        $this->pendingRead = new Deferred;
        return $this->pendingRead->promise();
    }

    /** @inheritdoc */
    final public function onResolve(callable $onResolved)
    {
        $this->buffering = true;

        if ($this->coroutine === null) {
            $this->coroutine = new Coroutine($this->consume());
        }

        if ($this->backpressure) {
            $backpressure = $this->backpressure;
            $this->backpressure = null;
            $backpressure->resolve();
        }

        $this->coroutine->onResolve($onResolved);
    }

    /**
     * Exposes the source input stream.
     *
     * This might be required to resolve a promise with an InputStream, because promises in Amp can't be resolved with
     * other promises.
     *
     * @return InputStream
     */
    final public function getInputStream(): InputStream
    {
        return $this->source;
    }
}
