<?php

namespace Guzzle\Plugin\Backoff;

use Guzzle\Common\Event;
use Guzzle\Common\AbstractHasDispatcher;
use Guzzle\Http\Message\EntityEnclosingRequestInterface;
use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Message\Response;
use Guzzle\Http\Curl\CurlMultiInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Plugin to automatically retry failed HTTP requests using a backoff strategy
 */
class BackoffPlugin extends AbstractHasDispatcher implements EventSubscriberInterface
{
    const DELAY_PARAM = CurlMultiInterface::BLOCKING;
    const RETRY_PARAM = 'plugins.backoff.retry_count';
    const RETRY_EVENT = 'plugins.backoff.retry';

    /** @var BackoffStrategyInterface Backoff strategy */
    protected $strategy;

    /**
     * @param BackoffStrategyInterface $strategy The backoff strategy used to determine whether or not to retry and
     *                                           the amount of delay between retries.
     */
    public function __construct(BackoffStrategyInterface $strategy = null)
    {
        $this->strategy = $strategy;
    }

    /**
     * Retrieve a basic truncated exponential backoff plugin that will retry HTTP errors and cURL errors
     *
     * @param int   $maxRetries Maximum number of retries
     * @param array $httpCodes  HTTP response codes to retry
     * @param array $curlCodes  cURL error codes to retry
     *
     * @return self
     */
    public static function getExponentialBackoff(
        $maxRetries = 3,
        array $httpCodes = null,
        array $curlCodes = null
    ) {
        return new self(new TruncatedBackoffStrategy($maxRetries,
            new HttpBackoffStrategy($httpCodes,
                new CurlBackoffStrategy($curlCodes,
                    new ExponentialBackoffStrategy()
                )
            )
        ));
    }

    public static function getAllEvents()
    {
        return array(self::RETRY_EVENT);
    }

    public static function getSubscribedEvents()
    {
        return array(
            'request.sent'      => 'onRequestSent',
            'request.exception' => 'onRequestSent',
            CurlMultiInterface::POLLING_REQUEST => 'onRequestPoll'
        );
    }

    /**
     * Called when a request has been sent  and isn't finished processing
     *
     * @param Event $event
     */
    public function onRequestSent(Event $event)
    {
        $request = $event['request'];
        $response = $event['response'];
        $exception = $event['exception'];

        $params = $request->getParams();
        $retries = (int) $params->get(self::RETRY_PARAM);
        $delay = $this->strategy->getBackoffPeriod($retries, $request, $response, $exception);

        if ($delay !== false) {
            // Calculate how long to wait until the request should be retried
            $params->set(self::RETRY_PARAM, ++$retries)
                ->set(self::DELAY_PARAM, microtime(true) + $delay);
            // Send the request again
            $request->setState(RequestInterface::STATE_TRANSFER);
            $this->dispatch(self::RETRY_EVENT, array(
                'request'  => $request,
                'response' => $response,
                'handle'   => $exception ? $exception->getCurlHandle() : null,
                'retries'  => $retries,
                'delay'    => $delay
            ));
        }
    }

    /**
     * Called when a request is polling in the curl multi object
     *
     * @param Event $event
     */
    public function onRequestPoll(Event $event)
    {
        $request = $event['request'];
        $delay = $request->getParams()->get(self::DELAY_PARAM);

        // If the duration of the delay has passed, retry the request using the pool
        if (null !== $delay && microtime(true) >= $delay) {
            // Remove the request from the pool and then add it back again. This is required for cURL to know that we
            // want to retry sending the easy handle.
            $request->getParams()->remove(self::DELAY_PARAM);
            // Rewind the request body if possible
            if ($request instanceof EntityEnclosingRequestInterface && $request->getBody()) {
                $request->getBody()->seek(0);
            }
            $multi = $event['curl_multi'];
            $multi->remove($request);
            $multi->add($request);
        }
    }
}
