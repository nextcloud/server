<?php

namespace Guzzle\Plugin\Mock;

use Guzzle\Common\Event;
use Guzzle\Common\Exception\InvalidArgumentException;
use Guzzle\Common\AbstractHasDispatcher;
use Guzzle\Http\Exception\CurlException;
use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Message\EntityEnclosingRequestInterface;
use Guzzle\Http\Message\Response;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Queues mock responses or exceptions and delivers mock responses or exceptions in a fifo order.
 */
class MockPlugin extends AbstractHasDispatcher implements EventSubscriberInterface, \Countable
{
    /** @var array Array of mock responses / exceptions */
    protected $queue = array();

    /** @var bool Whether or not to remove the plugin when the queue is empty */
    protected $temporary = false;

    /** @var array Array of requests that were mocked */
    protected $received = array();

    /** @var bool Whether or not to consume an entity body when a mock response is served */
    protected $readBodies;

    /**
     * @param array $items      Array of responses or exceptions to queue
     * @param bool  $temporary  Set to TRUE to remove the plugin when the queue is empty
     * @param bool  $readBodies Set to TRUE to consume the entity body when a mock is served
     */
    public function __construct(array $items = null, $temporary = false, $readBodies = false)
    {
        $this->readBodies = $readBodies;
        $this->temporary = $temporary;
        if ($items) {
            foreach ($items as $item) {
                if ($item instanceof \Exception) {
                    $this->addException($item);
                } else {
                    $this->addResponse($item);
                }
            }
        }
    }

    public static function getSubscribedEvents()
    {
        // Use a number lower than the CachePlugin
        return array('request.before_send' => array('onRequestBeforeSend', -999));
    }

    public static function getAllEvents()
    {
        return array('mock.request');
    }

    /**
     * Get a mock response from a file
     *
     * @param string $path File to retrieve a mock response from
     *
     * @return Response
     * @throws InvalidArgumentException if the file is not found
     */
    public static function getMockFile($path)
    {
        if (!file_exists($path)) {
            throw new InvalidArgumentException('Unable to open mock file: ' . $path);
        }

        return Response::fromMessage(file_get_contents($path));
    }

    /**
     * Set whether or not to consume the entity body of a request when a mock
     * response is used
     *
     * @param bool $readBodies Set to true to read and consume entity bodies
     *
     * @return self
     */
    public function readBodies($readBodies)
    {
        $this->readBodies = $readBodies;

        return $this;
    }

    /**
     * Returns the number of remaining mock responses
     *
     * @return int
     */
    public function count()
    {
        return count($this->queue);
    }

    /**
     * Add a response to the end of the queue
     *
     * @param string|Response $response Response object or path to response file
     *
     * @return MockPlugin
     * @throws InvalidArgumentException if a string or Response is not passed
     */
    public function addResponse($response)
    {
        if (!($response instanceof Response)) {
            if (!is_string($response)) {
                throw new InvalidArgumentException('Invalid response');
            }
            $response = self::getMockFile($response);
        }

        $this->queue[] = $response;

        return $this;
    }

    /**
     * Add an exception to the end of the queue
     *
     * @param CurlException $e Exception to throw when the request is executed
     *
     * @return MockPlugin
     */
    public function addException(CurlException $e)
    {
        $this->queue[] = $e;

        return $this;
    }

    /**
     * Clear the queue
     *
     * @return MockPlugin
     */
    public function clearQueue()
    {
        $this->queue = array();

        return $this;
    }

    /**
     * Returns an array of mock responses remaining in the queue
     *
     * @return array
     */
    public function getQueue()
    {
        return $this->queue;
    }

    /**
     * Check if this is a temporary plugin
     *
     * @return bool
     */
    public function isTemporary()
    {
        return $this->temporary;
    }

    /**
     * Get a response from the front of the list and add it to a request
     *
     * @param RequestInterface $request Request to mock
     *
     * @return self
     * @throws CurlException When request.send is called and an exception is queued
     */
    public function dequeue(RequestInterface $request)
    {
        $this->dispatch('mock.request', array('plugin' => $this, 'request' => $request));

        $item = array_shift($this->queue);
        if ($item instanceof Response) {
            if ($this->readBodies && $request instanceof EntityEnclosingRequestInterface) {
                $request->getEventDispatcher()->addListener('request.sent', $f = function (Event $event) use (&$f) {
                    while ($data = $event['request']->getBody()->read(8096));
                    // Remove the listener after one-time use
                    $event['request']->getEventDispatcher()->removeListener('request.sent', $f);
                });
            }
            $request->setResponse($item);
        } elseif ($item instanceof CurlException) {
            // Emulates exceptions encountered while transferring requests
            $item->setRequest($request);
            $state = $request->setState(RequestInterface::STATE_ERROR, array('exception' => $item));
            // Only throw if the exception wasn't handled
            if ($state == RequestInterface::STATE_ERROR) {
                throw $item;
            }
        }

        return $this;
    }

    /**
     * Clear the array of received requests
     */
    public function flush()
    {
        $this->received = array();
    }

    /**
     * Get an array of requests that were mocked by this plugin
     *
     * @return array
     */
    public function getReceivedRequests()
    {
        return $this->received;
    }

    /**
     * Called when a request is about to be sent
     *
     * @param Event $event
     * @throws \OutOfBoundsException When queue is empty
     */
    public function onRequestBeforeSend(Event $event)
    {
        if (!$this->queue) {
            throw new \OutOfBoundsException('Mock queue is empty');
        }

        $request = $event['request'];
        $this->received[] = $request;
        // Detach the filter from the client so it's a one-time use
        if ($this->temporary && count($this->queue) == 1 && $request->getClient()) {
            $request->getClient()->getEventDispatcher()->removeSubscriber($this);
        }
        $this->dequeue($request);
    }
}
