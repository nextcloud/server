<?php

namespace Guzzle\Http\Message;

/**
 * Request and response message interface
 */
interface MessageInterface
{
    /**
     * Get application and plugin specific parameters set on the message.
     *
     * @return \Guzzle\Common\Collection
     */
    public function getParams();

    /**
     * Add a header to an existing collection of headers.
     *
     * @param string $header Header name to add
     * @param string $value  Value of the header
     *
     * @return self
     */
    public function addHeader($header, $value);

    /**
     * Add and merge in an array of HTTP headers.
     *
     * @param array $headers Associative array of header data.
     *
     * @return self
     */
    public function addHeaders(array $headers);

    /**
     * Retrieve an HTTP header by name. Performs a case-insensitive search of all headers.
     *
     * @param string $header Header to retrieve.
     *
     * @return Header|null
     */
    public function getHeader($header);

    /**
     * Get all headers as a collection
     *
     * @return \Guzzle\Http\Message\Header\HeaderCollection
     */
    public function getHeaders();

    /**
     * Check if the specified header is present.
     *
     * @param string $header The header to check.
     *
     * @return bool
     */
    public function hasHeader($header);

    /**
     * Remove a specific HTTP header.
     *
     * @param string $header HTTP header to remove.
     *
     * @return self
     */
    public function removeHeader($header);

    /**
     * Set an HTTP header and overwrite any existing value for the header
     *
     * @param string $header Name of the header to set.
     * @param mixed  $value  Value to set.
     *
     * @return self
     */
    public function setHeader($header, $value);

    /**
     * Overwrite all HTTP headers with the supplied array of headers
     *
     * @param array $headers Associative array of header data.
     *
     * @return self
     */
    public function setHeaders(array $headers);

    /**
     * Get an array of message header lines (e.g. ["Host: example.com", ...])
     *
     * @return array
     */
    public function getHeaderLines();

    /**
     * Get the raw message headers as a string
     *
     * @return string
     */
    public function getRawHeaders();
}
