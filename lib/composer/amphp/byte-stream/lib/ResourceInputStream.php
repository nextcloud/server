<?php

namespace Amp\ByteStream;

use Amp\Deferred;
use Amp\Loop;
use Amp\Promise;
use Amp\Success;

/**
 * Input stream abstraction for PHP's stream resources.
 */
final class ResourceInputStream implements InputStream
{
    const DEFAULT_CHUNK_SIZE = 8192;

    /** @var resource|null */
    private $resource;

    /** @var string */
    private $watcher;

    /** @var Deferred|null */
    private $deferred;

    /** @var bool */
    private $readable = true;

    /** @var int */
    private $chunkSize;

    /** @var bool */
    private $useSingleRead;

    /** @var callable */
    private $immediateCallable;

    /** @var string|null */
    private $immediateWatcher;

    /**
     * @param resource $stream Stream resource.
     * @param int      $chunkSize Chunk size per read operation.
     *
     * @throws \Error If an invalid stream or parameter has been passed.
     */
    public function __construct($stream, int $chunkSize = self::DEFAULT_CHUNK_SIZE)
    {
        if (!\is_resource($stream) || \get_resource_type($stream) !== 'stream') {
            throw new \Error("Expected a valid stream");
        }

        $meta = \stream_get_meta_data($stream);
        $useSingleRead = $meta["stream_type"] === "udp_socket" || $meta["stream_type"] === "STDIO";
        $this->useSingleRead = $useSingleRead;

        if (\strpos($meta["mode"], "r") === false && \strpos($meta["mode"], "+") === false) {
            throw new \Error("Expected a readable stream");
        }

        \stream_set_blocking($stream, false);
        \stream_set_read_buffer($stream, 0);

        $this->resource = &$stream;
        $this->chunkSize = &$chunkSize;

        $deferred = &$this->deferred;
        $readable = &$this->readable;

        $this->watcher = Loop::onReadable($this->resource, static function ($watcher) use (
            &$deferred,
            &$readable,
            &$stream,
            &$chunkSize,
            $useSingleRead
        ) {
            if ($useSingleRead) {
                $data = @\fread($stream, $chunkSize);
            } else {
                $data = @\stream_get_contents($stream, $chunkSize);
            }

            \assert($data !== false, "Trying to read from a previously fclose()'d resource. Do NOT manually fclose() resources the loop still has a reference to.");

            // Error suppression, because pthreads does crazy things with resources,
            // which might be closed during two operations.
            // See https://github.com/amphp/byte-stream/issues/32
            if ($data === '' && @\feof($stream)) {
                $readable = false;
                $stream = null;
                $data = null; // Stream closed, resolve read with null.
                Loop::cancel($watcher);
            } else {
                Loop::disable($watcher);
            }

            $temp = $deferred;
            $deferred = null;

            \assert($temp instanceof Deferred);
            $temp->resolve($data);
        });

        $this->immediateCallable = static function ($watcherId, $data) use (&$deferred) {
            $temp = $deferred;
            $deferred = null;

            \assert($temp instanceof Deferred);
            $temp->resolve($data);
        };

        Loop::disable($this->watcher);
    }

    /** @inheritdoc */
    public function read(): Promise
    {
        if ($this->deferred !== null) {
            throw new PendingReadError;
        }

        if (!$this->readable) {
            return new Success; // Resolve with null on closed stream.
        }

        \assert($this->resource !== null);

        // Attempt a direct read, because Windows suffers from slow I/O on STDIN otherwise.
        if ($this->useSingleRead) {
            $data = @\fread($this->resource, $this->chunkSize);
        } else {
            $data = @\stream_get_contents($this->resource, $this->chunkSize);
        }

        \assert($data !== false, "Trying to read from a previously fclose()'d resource. Do NOT manually fclose() resources the loop still has a reference to.");

        if ($data === '') {
            // Error suppression, because pthreads does crazy things with resources,
            // which might be closed during two operations.
            // See https://github.com/amphp/byte-stream/issues/32
            if (@\feof($this->resource)) {
                $this->readable = false;
                $this->resource = null;
                Loop::cancel($this->watcher);

                return new Success; // Stream closed, resolve read with null.
            }

            $this->deferred = new Deferred;
            Loop::enable($this->watcher);

            return $this->deferred->promise();
        }

        // Prevent an immediate read → write loop from blocking everything
        // See e.g. examples/benchmark-throughput.php
        $this->deferred = new Deferred;
        $this->immediateWatcher = Loop::defer($this->immediateCallable, $data);

        return $this->deferred->promise();
    }

    /**
     * Closes the stream forcefully. Multiple `close()` calls are ignored.
     *
     * @return void
     */
    public function close()
    {
        if ($this->resource) {
            // Error suppression, as resource might already be closed
            $meta = @\stream_get_meta_data($this->resource);

            if ($meta && \strpos($meta["mode"], "+") !== false) {
                @\stream_socket_shutdown($this->resource, \STREAM_SHUT_RD);
            } else {
                /** @psalm-suppress InvalidPropertyAssignmentValue */
                @\fclose($this->resource);
            }
        }

        $this->free();
    }

    /**
     * Nulls reference to resource, marks stream unreadable, and succeeds any pending read with null.
     *
     * @return void
     */
    private function free()
    {
        $this->readable = false;
        $this->resource = null;

        if ($this->deferred !== null) {
            $deferred = $this->deferred;
            $this->deferred = null;
            $deferred->resolve();
        }

        Loop::cancel($this->watcher);

        if ($this->immediateWatcher !== null) {
            Loop::cancel($this->immediateWatcher);
        }
    }

    /**
     * @return resource|null The stream resource or null if the stream has closed.
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * @return void
     */
    public function setChunkSize(int $chunkSize)
    {
        $this->chunkSize = $chunkSize;
    }

    /**
     * References the read watcher, so the loop keeps running in case there's an active read.
     *
     * @return void
     *
     * @see Loop::reference()
     */
    public function reference()
    {
        if (!$this->resource) {
            throw new \Error("Resource has already been freed");
        }

        Loop::reference($this->watcher);
    }

    /**
     * Unreferences the read watcher, so the loop doesn't keep running even if there are active reads.
     *
     * @return void
     *
     * @see Loop::unreference()
     */
    public function unreference()
    {
        if (!$this->resource) {
            throw new \Error("Resource has already been freed");
        }

        Loop::unreference($this->watcher);
    }

    public function __destruct()
    {
        if ($this->resource !== null) {
            $this->free();
        }
    }
}
