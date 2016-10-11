<?php

namespace Guzzle\Http\Exception;

use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Message\Response;

/**
 * Http request exception thrown when a bad response is received
 */
class BadResponseException extends RequestException
{
    /** @var Response */
    private $response;

    /**
     * Factory method to create a new response exception based on the response code.
     *
     * @param RequestInterface $request  Request
     * @param Response         $response Response received
     *
     * @return BadResponseException
     */
    public static function factory(RequestInterface $request, Response $response)
    {
        if ($response->isClientError()) {
            $label = 'Client error response';
            $class = __NAMESPACE__ . '\\ClientErrorResponseException';
        } elseif ($response->isServerError()) {
            $label = 'Server error response';
            $class = __NAMESPACE__ . '\\ServerErrorResponseException';
        } else {
            $label = 'Unsuccessful response';
            $class = __CLASS__;
        }

        $message = $label . PHP_EOL . implode(PHP_EOL, array(
            '[status code] ' . $response->getStatusCode(),
            '[reason phrase] ' . $response->getReasonPhrase(),
            '[url] ' . $request->getUrl(),
        ));

        $e = new $class($message);
        $e->setResponse($response);
        $e->setRequest($request);

        return $e;
    }

    /**
     * Set the response that caused the exception
     *
     * @param Response $response Response to set
     */
    public function setResponse(Response $response)
    {
        $this->response = $response;
    }

    /**
     * Get the response that caused the exception
     *
     * @return Response
     */
    public function getResponse()
    {
        return $this->response;
    }
}
