<?php

declare(strict_types=1);

namespace Sabre\HTTP;

/**
 * The MessageInterface is the base interface that's used by both
 * the RequestInterface and ResponseInterface.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
interface MessageInterface
{
    /**
     * Returns the body as a readable stream resource.
     *
     * Note that the stream may not be rewindable, and therefore may only be
     * read once.
     *
     * @return resource
     */
    public function getBodyAsStream();

    /**
     * Returns the body as a string.
     *
     * Note that because the underlying data may be based on a stream, this
     * method could only work correctly the first time.
     */
    public function getBodyAsString(): string;

    /**
     * Returns the message body, as it's internal representation.
     *
     * This could be either a string, a stream or a callback writing the body to php://output
     *
     * @return resource|string|callable
     */
    public function getBody();

    /**
     * Updates the body resource with a new stream.
     *
     * @param resource|string|callable $body
     */
    public function setBody($body);

    /**
     * Returns all the HTTP headers as an array.
     *
     * Every header is returned as an array, with one or more values.
     */
    public function getHeaders(): array;

    /**
     * Will return true or false, depending on if a HTTP header exists.
     */
    public function hasHeader(string $name): bool;

    /**
     * Returns a specific HTTP header, based on it's name.
     *
     * The name must be treated as case-insensitive.
     * If the header does not exist, this method must return null.
     *
     * If a header appeared more than once in a HTTP request, this method will
     * concatenate all the values with a comma.
     *
     * Note that this not make sense for all headers. Some, such as
     * `Set-Cookie` cannot be logically combined with a comma. In those cases
     * you *should* use getHeaderAsArray().
     *
     * @return string|null
     */
    public function getHeader(string $name);

    /**
     * Returns a HTTP header as an array.
     *
     * For every time the HTTP header appeared in the request or response, an
     * item will appear in the array.
     *
     * If the header did not exists, this method will return an empty array.
     *
     * @return string[]
     */
    public function getHeaderAsArray(string $name): array;

    /**
     * Updates a HTTP header.
     *
     * The case-sensitity of the name value must be retained as-is.
     *
     * If the header already existed, it will be overwritten.
     *
     * @param string|string[] $value
     */
    public function setHeader(string $name, $value);

    /**
     * Sets a new set of HTTP headers.
     *
     * The headers array should contain headernames for keys, and their value
     * should be specified as either a string or an array.
     *
     * Any header that already existed will be overwritten.
     */
    public function setHeaders(array $headers);

    /**
     * Adds a HTTP header.
     *
     * This method will not overwrite any existing HTTP header, but instead add
     * another value. Individual values can be retrieved with
     * getHeadersAsArray.
     *
     * @param string|string[] $value
     */
    public function addHeader(string $name, $value);

    /**
     * Adds a new set of HTTP headers.
     *
     * Any existing headers will not be overwritten.
     */
    public function addHeaders(array $headers);

    /**
     * Removes a HTTP header.
     *
     * The specified header name must be treated as case-insenstive.
     * This method should return true if the header was successfully deleted,
     * and false if the header did not exist.
     */
    public function removeHeader(string $name): bool;

    /**
     * Sets the HTTP version.
     *
     * Should be 1.0, 1.1 or 2.0.
     */
    public function setHttpVersion(string $version);

    /**
     * Returns the HTTP version.
     */
    public function getHttpVersion(): string;
}
