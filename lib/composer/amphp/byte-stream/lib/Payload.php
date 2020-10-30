<?php

namespace Amp\ByteStream;

use Amp\Coroutine;
use Amp\Promise;
use function Amp\call;

/**
 * Creates a buffered message from an InputStream. The message can be consumed in chunks using the read() API or it may
 * be buffered and accessed in its entirety by calling buffer(). Once buffering is requested through buffer(), the
 * stream cannot be read in chunks. On destruct any remaining data is read from the InputStream given to this class.
 */
class Payload implements InputStream
{
    /** @var InputStream */
    private $stream;

    /** @var \Amp\Promise|null */
    private $promise;

    /** @var \Amp\Promise|null */
    private $lastRead;

    /**
     * @param \Amp\ByteStream\InputStream $stream
     */
    public function __construct(InputStream $stream)
    {
        $this->stream = $stream;
    }

    public function __destruct()
    {
        if (!$this->promise) {
            Promise\rethrow(new Coroutine($this->consume()));
        }
    }

    private function consume(): \Generator
    {
        try {
            if ($this->lastRead && null === yield $this->lastRead) {
                return;
            }

            while (null !== yield $this->stream->read()) {
                // Discard unread bytes from message.
            }
        } catch (\Throwable $exception) {
            // If exception is thrown here the connection closed anyway.
        }
    }

    /**
     * @inheritdoc
     *
     * @throws \Error If a buffered message was requested by calling buffer().
     */
    final public function read(): Promise
    {
        if ($this->promise) {
            throw new \Error("Cannot stream message data once a buffered message has been requested");
        }

        return $this->lastRead = $this->stream->read();
    }

    /**
     * Buffers the entire message and resolves the returned promise then.
     *
     * @return Promise<string> Resolves with the entire message contents.
     */
    final public function buffer(): Promise
    {
        if ($this->promise) {
            return $this->promise;
        }

        return $this->promise = call(function () {
            $buffer = '';
            if ($this->lastRead && null === yield $this->lastRead) {
                return $buffer;
            }

            while (null !== $chunk = yield $this->stream->read()) {
                $buffer .= $chunk;
            }
            return $buffer;
        });
    }
}
