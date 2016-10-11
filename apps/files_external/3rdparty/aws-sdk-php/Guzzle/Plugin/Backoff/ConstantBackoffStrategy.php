<?php

namespace Guzzle\Plugin\Backoff;

use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Message\Response;
use Guzzle\Http\Exception\HttpException;

/**
 * Will retry the request using the same amount of delay for each retry.
 *
 * Warning: If no decision making strategies precede this strategy in the the chain, then all requests will be retried
 */
class ConstantBackoffStrategy extends AbstractBackoffStrategy
{
    /** @var int Amount of time for each delay */
    protected $delay;

    /** @param int $delay Amount of time to delay between each additional backoff */
    public function __construct($delay)
    {
        $this->delay = $delay;
    }

    public function makesDecision()
    {
        return false;
    }

    protected function getDelay($retries, RequestInterface $request, Response $response = null, HttpException $e = null)
    {
        return $this->delay;
    }
}
