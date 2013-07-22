<?php

namespace Guzzle\Parser\Message;

/**
 * HTTP message parser interface used to parse HTTP messages into an array
 */
interface MessageParserInterface
{
    /**
     * Parse an HTTP request message into an associative array of parts.
     *
     * @param string $message HTTP request to parse
     *
     * @return array|bool Returns false if the message is invalid
     */
    public function parseRequest($message);

    /**
     * Parse an HTTP response message into an associative array of parts.
     *
     * @param string $message HTTP response to parse
     *
     * @return array|bool Returns false if the message is invalid
     */
    public function parseResponse($message);
}
