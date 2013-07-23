<?php

namespace Guzzle\Plugin\Backoff;

use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Message\Response;
use Guzzle\Http\Exception\HttpException;
use Guzzle\Http\Exception\CurlException;

/**
 * Strategy used to retry when certain cURL error codes are encountered.
 */
class CurlBackoffStrategy extends AbstractErrorCodeBackoffStrategy
{
    /** @var array Default cURL errors to retry */
    protected static $defaultErrorCodes = array(
        CURLE_COULDNT_RESOLVE_HOST, CURLE_COULDNT_CONNECT, CURLE_WRITE_ERROR, CURLE_READ_ERROR,
        CURLE_OPERATION_TIMEOUTED, CURLE_SSL_CONNECT_ERROR, CURLE_HTTP_PORT_FAILED, CURLE_GOT_NOTHING,
        CURLE_SEND_ERROR, CURLE_RECV_ERROR
    );

    protected function getDelay($retries, RequestInterface $request, Response $response = null, HttpException $e = null)
    {
        if ($e && $e instanceof CurlException) {
            return isset($this->errorCodes[$e->getErrorNo()]) ? true : null;
        }
    }
}
