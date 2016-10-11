<?php

namespace Guzzle\Http\Exception;

use Guzzle\Common\Exception\ExceptionCollection;
use Guzzle\Http\Message\RequestInterface;

/**
 * Exception encountered during a multi transfer
 */
class MultiTransferException extends ExceptionCollection
{
    protected $successfulRequests = array();
    protected $failedRequests = array();
    protected $exceptionForRequest = array();

    /**
     * Get all of the requests in the transfer
     *
     * @return array
     */
    public function getAllRequests()
    {
        return array_merge($this->successfulRequests, $this->failedRequests);
    }

    /**
     * Add to the array of successful requests
     *
     * @param RequestInterface $request Successful request
     *
     * @return self
     */
    public function addSuccessfulRequest(RequestInterface $request)
    {
        $this->successfulRequests[] = $request;

        return $this;
    }

    /**
     * Add to the array of failed requests
     *
     * @param RequestInterface $request Failed request
     *
     * @return self
     */
    public function addFailedRequest(RequestInterface $request)
    {
        $this->failedRequests[] = $request;

        return $this;
    }

    /**
     * Add to the array of failed requests and associate with exceptions
     *
     * @param RequestInterface $request   Failed request
     * @param \Exception       $exception Exception to add and associate with
     *
     * @return self
     */
    public function addFailedRequestWithException(RequestInterface $request, \Exception $exception)
    {
        $this->add($exception)
             ->addFailedRequest($request)
             ->exceptionForRequest[spl_object_hash($request)] = $exception;

        return $this;
    }

    /**
     * Get the Exception that caused the given $request to fail
     *
     * @param RequestInterface $request Failed command
     *
     * @return \Exception|null
     */
    public function getExceptionForFailedRequest(RequestInterface $request)
    {
        $oid = spl_object_hash($request);

        return isset($this->exceptionForRequest[$oid]) ? $this->exceptionForRequest[$oid] : null;
    }

    /**
     * Set all of the successful requests
     *
     * @param array Array of requests
     *
     * @return self
     */
    public function setSuccessfulRequests(array $requests)
    {
        $this->successfulRequests = $requests;

        return $this;
    }

    /**
     * Set all of the failed requests
     *
     * @param array Array of requests
     *
     * @return self
     */
    public function setFailedRequests(array $requests)
    {
        $this->failedRequests = $requests;

        return $this;
    }

    /**
     * Get an array of successful requests sent in the multi transfer
     *
     * @return array
     */
    public function getSuccessfulRequests()
    {
        return $this->successfulRequests;
    }

    /**
     * Get an array of failed requests sent in the multi transfer
     *
     * @return array
     */
    public function getFailedRequests()
    {
        return $this->failedRequests;
    }

    /**
     * Check if the exception object contains a request
     *
     * @param RequestInterface $request Request to check
     *
     * @return bool
     */
    public function containsRequest(RequestInterface $request)
    {
        return in_array($request, $this->failedRequests, true) || in_array($request, $this->successfulRequests, true);
    }
}
