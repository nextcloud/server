<?php

namespace GuzzleHttp\Handler;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Utils;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Represents a cURL easy handle and the data it populates.
 *
 * @internal
 */
final class EasyHandle
{
    /**
     * @var resource|\CurlHandle cURL resource
     */
    public $handle;

    /**
     * @var StreamInterface Where data is being written
     */
    public $sink;

    /**
     * @var array Received HTTP headers so far
     */
    public $headers = [];

    /**
     * @var ResponseInterface|null Received response (if any)
     */
    public $response;

    /**
     * @var RequestInterface Request being sent
     */
    public $request;

    /**
     * @var array Request options
     */
    public $options = [];

    /**
     * @var int cURL error number (if any)
     */
    public $errno = 0;

    /**
     * @var \Throwable|null Exception during on_headers (if any)
     */
    public $onHeadersException;

    /**
     * @var \Exception|null Exception during createResponse (if any)
     */
    public $createResponseException;

    /**
     * Attach a response to the easy handle based on the received headers.
     *
     * @throws \RuntimeException if no headers have been received or the first
     *                           header line is invalid.
     */
    public function createResponse(): void
    {
        [$ver, $status, $reason, $headers] = HeaderProcessor::parseHeaders($this->headers);

        $normalizedKeys = Utils::normalizeHeaderKeys($headers);

        if (!empty($this->options['decode_content']) && isset($normalizedKeys['content-encoding'])) {
            $headers['x-encoded-content-encoding'] = $headers[$normalizedKeys['content-encoding']];
            unset($headers[$normalizedKeys['content-encoding']]);
            if (isset($normalizedKeys['content-length'])) {
                $headers['x-encoded-content-length'] = $headers[$normalizedKeys['content-length']];

                $bodyLength = (int) $this->sink->getSize();
                if ($bodyLength) {
                    $headers[$normalizedKeys['content-length']] = $bodyLength;
                } else {
                    unset($headers[$normalizedKeys['content-length']]);
                }
            }
        }

        // Attach a response to the easy handle with the parsed headers.
        $this->response = new Response(
            $status,
            $headers,
            $this->sink,
            $ver,
            $reason
        );
    }

    /**
     * @param string $name
     *
     * @return void
     *
     * @throws \BadMethodCallException
     */
    public function __get($name)
    {
        $msg = $name === 'handle' ? 'The EasyHandle has been released' : 'Invalid property: '.$name;
        throw new \BadMethodCallException($msg);
    }
}
