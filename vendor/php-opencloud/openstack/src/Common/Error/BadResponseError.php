<?php

declare(strict_types=1);

namespace OpenStack\Common\Error;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Represents a HTTP-specific error, caused by 4xx or 5xx response statuses.
 */
class BadResponseError extends BaseError
{
    /** @var RequestInterface */
    private $request;

    /** @var ResponseInterface */
    private $response;

    public function setRequest(RequestInterface $request)
    {
        $this->request = $request;
    }

    public function setResponse(ResponseInterface $response)
    {
        $this->response = $response;
    }

    public function getRequest(): RequestInterface
    {
        return $this->request;
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }
}
