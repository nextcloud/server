<?php
/**
 * Copyright 2010-2013 Amazon.com, Inc. or its affiliates. All Rights Reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License").
 * You may not use this file except in compliance with the License.
 * A copy of the License is located at
 *
 * http://aws.amazon.com/apache2.0
 *
 * or in the "license" file accompanying this file. This file is distributed
 * on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either
 * express or implied. See the License for the specific language governing
 * permissions and limitations under the License.
 */

namespace Aws\Common\Exception;

use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Message\Response;

/**
 * Default AWS exception
 */
class ServiceResponseException extends RuntimeException
{
    /**
     * @var Response Response
     */
    protected $response;

    /**
     * @var RequestInterface Request
     */
    protected $request;

    /**
     * @var string Request ID
     */
    protected $requestId;

    /**
     * @var string Exception type (client / server)
     */
    protected $exceptionType;

    /**
     * @var string Exception code
     */
    protected $exceptionCode;

    /**
     * Set the exception code
     *
     * @param string $code Exception code
     */
    public function setExceptionCode($code)
    {
        $this->exceptionCode = $code;
    }

    /**
     * Get the exception code
     *
     * @return string|null
     */
    public function getExceptionCode()
    {
        return $this->exceptionCode;
    }

    /**
     * Set the exception type
     *
     * @param string $type Exception type
     */
    public function setExceptionType($type)
    {
        $this->exceptionType = $type;
    }

    /**
     * Get the exception type (one of client or server)
     *
     * @return string|null
     */
    public function getExceptionType()
    {
        return $this->exceptionType;
    }

    /**
     * Set the request ID
     *
     * @param string $id Request ID
     */
    public function setRequestId($id)
    {
        $this->requestId = $id;
    }

    /**
     * Get the Request ID
     *
     * @return string|null
     */
    public function getRequestId()
    {
        return $this->requestId;
    }

    /**
     * Set the associated response
     *
     * @param Response $response Response
     */
    public function setResponse(Response $response)
    {
        $this->response = $response;
    }

    /**
     * Get the associated response object
     *
     * @return Response|null
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Set the associated request
     *
     * @param RequestInterface $request
     */
    public function setRequest(RequestInterface $request)
    {
        $this->request = $request;
    }

    /**
     * Get the associated request object
     *
     * @return RequestInterface|null
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Get the status code of the response
     *
     * @return int|null
     */
    public function getStatusCode()
    {
        return $this->response ? $this->response->getStatusCode() : null;
    }

    /**
     * Cast to a string
     *
     * @return string
     */
    public function __toString()
    {
        $message = get_class($this) . ': '
            . 'AWS Error Code: ' . $this->getExceptionCode() . ', '
            . 'Status Code: ' . $this->getStatusCode() . ', '
            . 'AWS Request ID: ' . $this->getRequestId() . ', '
            . 'AWS Error Type: ' . $this->getExceptionType() . ', '
            . 'AWS Error Message: ' . $this->getMessage();

        // Add the User-Agent if available
        if ($this->request) {
            $message .= ', ' . 'User-Agent: ' . $this->request->getHeader('User-Agent');
        }

        return $message;
    }
}
