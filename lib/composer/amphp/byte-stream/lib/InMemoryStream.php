<?php

namespace Amp\ByteStream;

use Amp\Promise;
use Amp\Success;

/**
 * Input stream with a single already known data chunk.
 */
final class InMemoryStream implements InputStream
{
    private $contents;

    /**
     * @param string|null $contents Data chunk or `null` for no data chunk.
     */
    public function __construct(string $contents = null)
    {
        $this->contents = $contents;
    }

    /**
     * Reads data from the stream.
     *
     * @return Promise<string|null> Resolves with the full contents or `null` if the stream has closed / already been consumed.
     */
    public function read(): Promise
    {
        if ($this->contents === null) {
            return new Success;
        }

        $promise = new Success($this->contents);
        $this->contents = null;

        return $promise;
    }
}
