<?php

namespace Guzzle\Plugin\Backoff;

use Guzzle\Common\Exception\InvalidArgumentException;
use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Message\Response;
use Guzzle\Http\Exception\HttpException;

/**
 * Strategy that will invoke a closure to determine whether or not to retry with a delay
 */
class CallbackBackoffStrategy extends AbstractBackoffStrategy
{
    /** @var \Closure|array|mixed Callable method to invoke */
    protected $callback;

    /** @var bool Whether or not this strategy makes a retry decision */
    protected $decision;

    /**
     * @param \Closure|array|mixed     $callback Callable method to invoke
     * @param bool                     $decision Set to true if this strategy makes a backoff decision
     * @param BackoffStrategyInterface $next     The optional next strategy
     *
     * @throws InvalidArgumentException
     */
    public function __construct($callback, $decision, BackoffStrategyInterface $next = null)
    {
        if (!is_callable($callback)) {
            throw new InvalidArgumentException('The callback must be callable');
        }
        $this->callback = $callback;
        $this->decision = (bool) $decision;
        $this->next = $next;
    }

    public function makesDecision()
    {
        return $this->decision;
    }

    protected function getDelay($retries, RequestInterface $request, Response $response = null, HttpException $e = null)
    {
        return call_user_func($this->callback, $retries, $request, $response, $e);
    }
}
