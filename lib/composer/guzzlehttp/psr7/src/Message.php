<?php

namespace GuzzleHttp\Psr7;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class Message
{
    /**
     * Returns the string representation of an HTTP message.
     *
     * @param MessageInterface $message Message to convert to a string.
     *
     * @return string
     */
    public static function toString(MessageInterface $message)
    {
        if ($message instanceof RequestInterface) {
            $msg = trim($message->getMethod() . ' '
                    . $message->getRequestTarget())
                . ' HTTP/' . $message->getProtocolVersion();
            if (!$message->hasHeader('host')) {
                $msg .= "\r\nHost: " . $message->getUri()->getHost();
            }
        } elseif ($message instanceof ResponseInterface) {
            $msg = 'HTTP/' . $message->getProtocolVersion() . ' '
                . $message->getStatusCode() . ' '
                . $message->getReasonPhrase();
        } else {
            throw new \InvalidArgumentException('Unknown message type');
        }

        foreach ($message->getHeaders() as $name => $values) {
            if (strtolower($name) === 'set-cookie') {
                foreach ($values as $value) {
                    $msg .= "\r\n{$name}: " . $value;
                }
            } else {
                $msg .= "\r\n{$name}: " . implode(', ', $values);
            }
        }

        return "{$msg}\r\n\r\n" . $message->getBody();
    }

    /**
     * Get a short summary of the message body.
     *
     * Will return `null` if the response is not printable.
     *
     * @param MessageInterface $message    The message to get the body summary
     * @param int              $truncateAt The maximum allowed size of the summary
     *
     * @return string|null
     */
    public static function bodySummary(MessageInterface $message, $truncateAt = 120)
    {
        $body = $message->getBody();

        if (!$body->isSeekable() || !$body->isReadable()) {
            return null;
        }

        $size = $body->getSize();

        if ($size === 0) {
            return null;
        }

        $summary = $body->read($truncateAt);
        $body->rewind();

        if ($size > $truncateAt) {
            $summary .= ' (truncated...)';
        }

        // Matches any printable character, including unicode characters:
        // letters, marks, numbers, punctuation, spacing, and separators.
        if (preg_match('/[^\pL\pM\pN\pP\pS\pZ\n\r\t]/u', $summary)) {
            return null;
        }

        return $summary;
    }

    /**
     * Attempts to rewind a message body and throws an exception on failure.
     *
     * The body of the message will only be rewound if a call to `tell()`
     * returns a value other than `0`.
     *
     * @param MessageInterface $message Message to rewind
     *
     * @throws \RuntimeException
     */
    public static function rewindBody(MessageInterface $message)
    {
        $body = $message->getBody();

        if ($body->tell()) {
            $body->rewind();
        }
    }

    /**
     * Parses an HTTP message into an associative array.
     *
     * The array contains the "start-line" key containing the start line of
     * the message, "headers" key containing an associative array of header
     * array values, and a "body" key containing the body of the message.
     *
     * @param string $message HTTP request or response to parse.
     *
     * @return array
     */
    public static function parseMessage($message)
    {
        if (!$message) {
            throw new \InvalidArgumentException('Invalid message');
        }

        $message = ltrim($message, "\r\n");

        $messageParts = preg_split("/\r?\n\r?\n/", $message, 2);

        if ($messageParts === false || count($messageParts) !== 2) {
            throw new \InvalidArgumentException('Invalid message: Missing header delimiter');
        }

        list($rawHeaders, $body) = $messageParts;
        $rawHeaders .= "\r\n"; // Put back the delimiter we split previously
        $headerParts = preg_split("/\r?\n/", $rawHeaders, 2);

        if ($headerParts === false || count($headerParts) !== 2) {
            throw new \InvalidArgumentException('Invalid message: Missing status line');
        }

        list($startLine, $rawHeaders) = $headerParts;

        if (preg_match("/(?:^HTTP\/|^[A-Z]+ \S+ HTTP\/)(\d+(?:\.\d+)?)/i", $startLine, $matches) && $matches[1] === '1.0') {
            // Header folding is deprecated for HTTP/1.1, but allowed in HTTP/1.0
            $rawHeaders = preg_replace(Rfc7230::HEADER_FOLD_REGEX, ' ', $rawHeaders);
        }

        /** @var array[] $headerLines */
        $count = preg_match_all(Rfc7230::HEADER_REGEX, $rawHeaders, $headerLines, PREG_SET_ORDER);

        // If these aren't the same, then one line didn't match and there's an invalid header.
        if ($count !== substr_count($rawHeaders, "\n")) {
            // Folding is deprecated, see https://tools.ietf.org/html/rfc7230#section-3.2.4
            if (preg_match(Rfc7230::HEADER_FOLD_REGEX, $rawHeaders)) {
                throw new \InvalidArgumentException('Invalid header syntax: Obsolete line folding');
            }

            throw new \InvalidArgumentException('Invalid header syntax');
        }

        $headers = [];

        foreach ($headerLines as $headerLine) {
            $headers[$headerLine[1]][] = $headerLine[2];
        }

        return [
            'start-line' => $startLine,
            'headers' => $headers,
            'body' => $body,
        ];
    }

    /**
     * Constructs a URI for an HTTP request message.
     *
     * @param string $path    Path from the start-line
     * @param array  $headers Array of headers (each value an array).
     *
     * @return string
     */
    public static function parseRequestUri($path, array $headers)
    {
        $hostKey = array_filter(array_keys($headers), function ($k) {
            return strtolower($k) === 'host';
        });

        // If no host is found, then a full URI cannot be constructed.
        if (!$hostKey) {
            return $path;
        }

        $host = $headers[reset($hostKey)][0];
        $scheme = substr($host, -4) === ':443' ? 'https' : 'http';

        return $scheme . '://' . $host . '/' . ltrim($path, '/');
    }

    /**
     * Parses a request message string into a request object.
     *
     * @param string $message Request message string.
     *
     * @return Request
     */
    public static function parseRequest($message)
    {
        $data = self::parseMessage($message);
        $matches = [];
        if (!preg_match('/^[\S]+\s+([a-zA-Z]+:\/\/|\/).*/', $data['start-line'], $matches)) {
            throw new \InvalidArgumentException('Invalid request string');
        }
        $parts = explode(' ', $data['start-line'], 3);
        $version = isset($parts[2]) ? explode('/', $parts[2])[1] : '1.1';

        $request = new Request(
            $parts[0],
            $matches[1] === '/' ? self::parseRequestUri($parts[1], $data['headers']) : $parts[1],
            $data['headers'],
            $data['body'],
            $version
        );

        return $matches[1] === '/' ? $request : $request->withRequestTarget($parts[1]);
    }

    /**
     * Parses a response message string into a response object.
     *
     * @param string $message Response message string.
     *
     * @return Response
     */
    public static function parseResponse($message)
    {
        $data = self::parseMessage($message);
        // According to https://tools.ietf.org/html/rfc7230#section-3.1.2 the space
        // between status-code and reason-phrase is required. But browsers accept
        // responses without space and reason as well.
        if (!preg_match('/^HTTP\/.* [0-9]{3}( .*|$)/', $data['start-line'])) {
            throw new \InvalidArgumentException('Invalid response string: ' . $data['start-line']);
        }
        $parts = explode(' ', $data['start-line'], 3);

        return new Response(
            (int) $parts[1],
            $data['headers'],
            $data['body'],
            explode('/', $parts[0])[1],
            isset($parts[2]) ? $parts[2] : null
        );
    }
}
