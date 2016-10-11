<?php

namespace Guzzle\Plugin\Async;

use Guzzle\Common\Event;
use Guzzle\Http\Message\Response;
use Guzzle\Http\Exception\CurlException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Sends requests but does not wait for the response
 */
class AsyncPlugin implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array(
            'request.before_send'    => 'onBeforeSend',
            'request.exception'      => 'onRequestTimeout',
            'request.sent'           => 'onRequestSent',
            'curl.callback.progress' => 'onCurlProgress'
        );
    }

    /**
     * Event used to ensure that progress callback are emitted from the curl handle's request mediator.
     *
     * @param Event $event
     */
    public function onBeforeSend(Event $event)
    {
        // Ensure that progress callbacks are dispatched
        $event['request']->getCurlOptions()->set('progress', true);
    }

    /**
     * Event emitted when a curl progress function is called. When the amount of data uploaded == the amount of data to
     * upload OR any bytes have been downloaded, then time the request out after 1ms because we're done with
     * transmitting the request, and tell curl not download a body.
     *
     * @param Event $event
     */
    public function onCurlProgress(Event $event)
    {
        if ($event['handle'] &&
            ($event['downloaded'] || (isset($event['uploaded']) && $event['upload_size'] === $event['uploaded']))
        ) {
            // Timeout after 1ms
            curl_setopt($event['handle'], CURLOPT_TIMEOUT_MS, 1);
            // Even if the response is quick, tell curl not to download the body.
            // - Note that we can only perform this shortcut if the request transmitted a body so as to ensure that the
            //   request method is not converted to a HEAD request before the request was sent via curl.
            if ($event['uploaded']) {
                curl_setopt($event['handle'], CURLOPT_NOBODY, true);
            }
        }
    }

    /**
     * Event emitted when a curl exception occurs. Ignore the exception and set a mock response.
     *
     * @param Event $event
     */
    public function onRequestTimeout(Event $event)
    {
        if ($event['exception'] instanceof CurlException) {
            $event['request']->setResponse(new Response(200, array(
                'X-Guzzle-Async' => 'Did not wait for the response'
            )));
        }
    }

    /**
     * Event emitted when a request completes because it took less than 1ms. Add an X-Guzzle-Async header to notify the
     * caller that there is no body in the message.
     *
     * @param Event $event
     */
    public function onRequestSent(Event $event)
    {
        // Let the caller know this was meant to be async
        $event['request']->getResponse()->setHeader('X-Guzzle-Async', 'Did not wait for the response');
    }
}
