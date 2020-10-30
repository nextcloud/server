<?php

namespace Amp\ByteStream;

use Amp\Promise;
use function Amp\call;

/**
 * Allows decompression of input streams using Zlib.
 */
final class ZlibInputStream implements InputStream
{
    /** @var InputStream|null */
    private $source;
    /** @var int */
    private $encoding;
    /** @var array */
    private $options;
    /** @var resource|null */
    private $resource;

    /**
     * @param InputStream $source Input stream to read compressed data from.
     * @param int         $encoding Compression algorithm used, see `inflate_init()`.
     * @param array       $options Algorithm options, see `inflate_init()`.
     *
     * @throws StreamException
     * @throws \Error
     *
     * @see http://php.net/manual/en/function.inflate-init.php
     */
    public function __construct(InputStream $source, int $encoding, array $options = [])
    {
        $this->source = $source;
        $this->encoding = $encoding;
        $this->options = $options;
        $this->resource = @\inflate_init($encoding, $options);

        if ($this->resource === false) {
            throw new StreamException("Failed initializing deflate context");
        }
    }

    /** @inheritdoc */
    public function read(): Promise
    {
        return call(function () {
            if ($this->resource === null) {
                return null;
            }

            \assert($this->source !== null);

            $data = yield $this->source->read();

            // Needs a double guard, as stream might have been closed while reading
            /** @psalm-suppress ParadoxicalCondition */
            if ($this->resource === null) {
                return null;
            }

            if ($data === null) {
                $decompressed = @\inflate_add($this->resource, "", \ZLIB_FINISH);

                if ($decompressed === false) {
                    throw new StreamException("Failed adding data to deflate context");
                }

                $this->close();

                return $decompressed;
            }

            $decompressed = @\inflate_add($this->resource, $data, \ZLIB_SYNC_FLUSH);

            if ($decompressed === false) {
                throw new StreamException("Failed adding data to deflate context");
            }

            return $decompressed;
        });
    }

    /**
     * @internal
     * @return void
     */
    private function close()
    {
        $this->resource = null;
        $this->source = null;
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
