<?php

namespace Guzzle\Plugin\Backoff;

use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Message\Response;
use Guzzle\Http\Exception\HttpException;

/**
 * Strategy that will not retry more than a certain number of times.
 */
class TruncatedBackoffStrategy extends AbstractBackoffStrategy
{
    /** @var int Maximum number of retries per request */
    protected $max;

    /**
     * @param int                      $maxRetries Maximum number of retries per request
     * @param BackoffStrategyInterface $next The optional next strategy
     */
    public function __construct($maxRetries, BackoffStrategyInterface $next = null)
    {
        $this->max = $maxRetries;
        $this->next = $next;
    }

    public function makesDecision()
    {
        return true;
    }

    protected function getDelay($retries, RequestInterface $request, Response $response = null, HttpException $e = null)
    {
        return $retries < $this->max ? null : false;
    }
}
