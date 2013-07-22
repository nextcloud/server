<?php

namespace Guzzle\Http;

use Guzzle\Stream\StreamInterface;

/**
 * Entity body used with an HTTP request or response
 */
interface EntityBodyInterface extends StreamInterface
{
    /**
     * Specify a custom callback used to rewind a non-seekable stream. This can be useful entity enclosing requests
     * that are redirected.
     *
     * @param mixed $callable Callable to invoke to rewind a non-seekable stream. The callback must accept an
     *                        EntityBodyInterface object, perform the rewind if possible, and return a boolean
     *                        representing whether or not the rewind was successful.
     * @return self
     */
    public function setRewindFunction($callable);

    /**
     * If the stream is readable, compress the data in the stream using deflate compression. The uncompressed stream is
     * then closed, and the compressed stream then becomes the wrapped stream.
     *
     * @param string $filter Compression filter
     *
     * @return bool Returns TRUE on success or FALSE on failure
     */
    public function compress($filter = 'zlib.deflate');

    /**
     * Decompress a deflated string. Once uncompressed, the uncompressed string is then used as the wrapped stream.
     *
     * @param string $filter De-compression filter
     *
     * @return bool Returns TRUE on success or FALSE on failure
     */
    public function uncompress($filter = 'zlib.inflate');

    /**
     * Get the Content-Length of the entity body if possible (alias of getSize)
     *
     * @return int|bool Returns the Content-Length or false on failure
     */
    public function getContentLength();

    /**
     * Guess the Content-Type of a local stream
     *
     * @return string|null
     * @see http://www.php.net/manual/en/function.finfo-open.php
     */
    public function getContentType();

    /**
     * Get an MD5 checksum of the stream's contents
     *
     * @param bool $rawOutput    Whether or not to use raw output
     * @param bool $base64Encode Whether or not to base64 encode raw output (only if raw output is true)
     *
     * @return bool|string Returns an MD5 string on success or FALSE on failure
     */
    public function getContentMd5($rawOutput = false, $base64Encode = false);

    /**
     * Get the Content-Encoding of the EntityBody
     *
     * @return bool|string
     */
    public function getContentEncoding();
}
