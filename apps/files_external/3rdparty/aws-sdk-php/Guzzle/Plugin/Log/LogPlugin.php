<?php

namespace Guzzle\Plugin\Log;

use Guzzle\Common\Event;
use Guzzle\Log\LogAdapterInterface;
use Guzzle\Log\MessageFormatter;
use Guzzle\Log\ClosureLogAdapter;
use Guzzle\Http\EntityBody;
use Guzzle\Http\Message\EntityEnclosingRequestInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Plugin class that will add request and response logging to an HTTP request.
 *
 * The log plugin uses a message formatter that allows custom messages via template variable substitution.
 *
 * @see MessageLogger for a list of available log template variable substitutions
 */
class LogPlugin implements EventSubscriberInterface
{
    /** @var LogAdapterInterface Adapter responsible for writing log data */
    protected $logAdapter;

    /** @var MessageFormatter Formatter used to format messages before logging */
    protected $formatter;

    /** @var bool Whether or not to wire request and response bodies */
    protected $wireBodies;

    /**
     * @param LogAdapterInterface     $logAdapter Adapter object used to log message
     * @param string|MessageFormatter $formatter  Formatter used to format log messages or the formatter template
     * @param bool                    $wireBodies Set to true to track request and response bodies using a temporary
     *                                            buffer if the bodies are not repeatable.
     */
    public function __construct(
        LogAdapterInterface $logAdapter,
        $formatter = null,
        $wireBodies = false
    ) {
        $this->logAdapter = $logAdapter;
        $this->formatter = $formatter instanceof MessageFormatter ? $formatter : new MessageFormatter($formatter);
        $this->wireBodies = $wireBodies;
    }

    /**
     * Get a log plugin that outputs full request, response, and curl error information to stderr
     *
     * @param bool     $wireBodies Set to false to disable request/response body output when they use are not repeatable
     * @param resource $stream     Stream to write to when logging. Defaults to STDERR when it is available
     *
     * @return self
     */
    public static function getDebugPlugin($wireBodies = true, $stream = null)
    {
        if ($stream === null) {
            if (defined('STDERR')) {
                $stream = STDERR;
            } else {
                $stream = fopen('php://output', 'w');
            }
        }

        return new self(new ClosureLogAdapter(function ($m) use ($stream) {
            fwrite($stream, $m . PHP_EOL);
        }), "# Request:\n{request}\n\n# Response:\n{response}\n\n# Errors: {curl_code} {curl_error}", $wireBodies);
    }

    public static function getSubscribedEvents()
    {
        return array(
            'curl.callback.write' => array('onCurlWrite', 255),
            'curl.callback.read'  => array('onCurlRead', 255),
            'request.before_send' => array('onRequestBeforeSend', 255),
            'request.sent'        => array('onRequestSent', 255)
        );
    }

    /**
     * Event triggered when curl data is read from a request
     *
     * @param Event $event
     */
    public function onCurlRead(Event $event)
    {
        // Stream the request body to the log if the body is not repeatable
        if ($wire = $event['request']->getParams()->get('request_wire')) {
            $wire->write($event['read']);
        }
    }

    /**
     * Event triggered when curl data is written to a response
     *
     * @param Event $event
     */
    public function onCurlWrite(Event $event)
    {
        // Stream the response body to the log if the body is not repeatable
        if ($wire = $event['request']->getParams()->get('response_wire')) {
            $wire->write($event['write']);
        }
    }

    /**
     * Called before a request is sent
     *
     * @param Event $event
     */
    public function onRequestBeforeSend(Event $event)
    {
        if ($this->wireBodies) {
            $request = $event['request'];
            // Ensure that curl IO events are emitted
            $request->getCurlOptions()->set('emit_io', true);
            // We need to make special handling for content wiring and non-repeatable streams.
            if ($request instanceof EntityEnclosingRequestInterface && $request->getBody()
                && (!$request->getBody()->isSeekable() || !$request->getBody()->isReadable())
            ) {
                // The body of the request cannot be recalled so logging the body will require us to buffer it
                $request->getParams()->set('request_wire', EntityBody::factory());
            }
            if (!$request->getResponseBody()->isRepeatable()) {
                // The body of the response cannot be recalled so logging the body will require us to buffer it
                $request->getParams()->set('response_wire', EntityBody::factory());
            }
        }
    }

    /**
     * Triggers the actual log write when a request completes
     *
     * @param Event $event
     */
    public function onRequestSent(Event $event)
    {
        $request = $event['request'];
        $response = $event['response'];
        $handle = $event['handle'];

        if ($wire = $request->getParams()->get('request_wire')) {
            $request = clone $request;
            $request->setBody($wire);
        }

        if ($wire = $request->getParams()->get('response_wire')) {
            $response = clone $response;
            $response->setBody($wire);
        }

        // Send the log message to the adapter, adding a category and host
        $priority = $response && $response->isError() ? LOG_ERR : LOG_DEBUG;
        $message = $this->formatter->format($request, $response, $handle);
        $this->logAdapter->log($message, $priority, array(
            'request'  => $request,
            'response' => $response,
            'handle'   => $handle
        ));
    }
}
