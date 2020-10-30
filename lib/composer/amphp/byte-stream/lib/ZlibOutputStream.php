<?php

namespace Amp\ByteStream;

use Amp\Promise;

/**
 * Allows compression of output streams using Zlib.
 */
final class ZlibOutputStream implements OutputStream
{
    /** @var OutputStream|null */
    private $destination;
    /** @var int */
    private $encoding;
    /** @var array */
    private $options;
    /** @var resource|null */
    private $resource;

    /**
     * @param OutputStream $destination Output stream to write the compressed data to.
     * @param int          $encoding Compression encoding to use, see `deflate_init()`.
     * @param array        $options Compression options to use, see `deflate_init()`.
     *
     * @throws StreamException If an invalid encoding or invalid options have been passed.
     *
     * @see http://php.net/manual/en/function.deflate-init.php
     */
    public function __construct(OutputStream $destination, int $encoding, array $options = [])
    {
        $this->destination = $destination;
        $this->encoding = $encoding;
        $this->options = $options;
        $this->resource = @\deflate_init($encoding, $options);

        if ($this->resource === false) {
            throw new StreamException("Failed initializing deflate context");
        }
    }

    /** @inheritdoc */
    public function write(string $data): Promise
    {
        if ($this->resource === null) {
            throw new ClosedException("The stream has already been closed");
        }

        \assert($this->destination !== null);

        $compressed = \deflate_add($this->resource, $data, \ZLIB_SYNC_FLUSH);

        if ($compressed === false) {
            throw new StreamException("Failed adding data to deflate context");
        }

        $promise = $this->destination->write($compressed);
        $promise->onResolve(function ($error) {
            if ($error) {
                $this->close();
            }
        });

        return $promise;
    }

    /** @inheritdoc */
    public function end(string $finalData = ""): Promise
    {
        if ($this->resource === null) {
            throw new ClosedException("The stream has already been closed");
        }

        \assert($this->destination !== null);

        $compressed = \deflate_add($this->resource, $finalData, \ZLIB_FINISH);

        if ($compressed === false) {
            throw new StreamException("Failed adding data to deflate context");
        }

        $promise = $this->destination->end($compressed);
        $promise->onResolve(function () {
            $this->close();
        });

        return $promise;
    }

    /**
     * @internal
     * @return void
     */
    private function close()
    {
        $this->resource = null;
        $this->destination = null;
    }

    /**
     * Gets the used compression encoding.
     *
     * @return int Encoding specified on construction time.
     */
    public function getEncoding(): int
    {
        return $this->encoding;
    }

    /**
     * Gets the used compression options.
     *
     * @return array Options array passed on construction time.
     */
    public function getOptions(): array
    {
        return $this->options;
    }
}
