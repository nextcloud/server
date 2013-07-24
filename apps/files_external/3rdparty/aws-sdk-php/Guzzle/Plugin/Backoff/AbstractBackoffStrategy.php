<?php

namespace Guzzle\Plugin\Backoff;

use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Message\Response;
use Guzzle\Http\Exception\HttpException;

/**
 * Abstract backoff strategy that allows for a chain of responsibility
 */
abstract class AbstractBackoffStrategy implements BackoffStrategyInterface
{
    /** @var AbstractBackoffStrategy Next strategy in the chain */
    protected $next;

    /** @param AbstractBackoffStrategy $next Next strategy in the chain */
    public function setNext(AbstractBackoffStrategy $next)
    {
        $this->next = $next;
    }

    /**
     * Get the next backoff strategy in the chain
     *
     * @return AbstractBackoffStrategy|null
     */
    public function getNext()
    {
        return $this->next;
    }

    public function getBackoffPeriod(
        $retries,
        RequestInterface $request,
        Response $response = null,
        HttpException $e = null
    ) {
        $delay = $this->getDelay($retries, $request, $response, $e);
        if ($delay === false) {
            // The strategy knows that this must not be retried
            return false;
        } elseif ($delay === null) {
            // If the strategy is deferring a decision and the next strategy will not make a decision then return false
            return !$this->next || !$this->next->makesDecision()
                ? false
                : $this->next->getBackoffPeriod($retries, $request, $response, $e);
        } elseif ($delay === true) {
            // if the strategy knows that it must retry but is deferring to the next to determine the delay
            if (!$this->next) {
                return 0;
            } else {
                $next = $this->next;
                while ($next->makesDecision() && $next->getNext()) {
                    $next = $next->getNext();
                }
                return !$next->makesDecision() ? $next->getBackoffPeriod($retries, $request, $response, $e) : 0;
            }
        } else {
            return $delay;
        }
    }

    /**
     * Check if the strategy does filtering and makes decisions on whether or not to retry.
     *
     * Strategies that return false will never retry if all of the previous strategies in a chain defer on a backoff
     * decision.
     *
     * @return bool
     */
    abstract public function makesDecision();

    /**
     * Implement the concrete strategy
     *
     * @param int              $retries  Number of retries of the request
     * @param RequestInterface $request  Request that was sent
     * @param Response         $response Response that was received. Note that there may not be a response
     * @param HttpException    $e        Exception that was encountered if any
     *
     * @return bool|int|null Returns false to not retry or the number of seconds to delay between retries. Return true
     *                       or null to defer to the next strategy if available, and if not, return 0.
     */
    abstract protected function getDelay(
        $retries,
        RequestInterface $request,
        Response $response = null,
        HttpException $e = null
    );
}
