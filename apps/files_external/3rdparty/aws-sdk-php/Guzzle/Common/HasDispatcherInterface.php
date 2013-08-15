<?php

namespace Guzzle\Common;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Holds an event dispatcher
 */
interface HasDispatcherInterface
{
    /**
     * Get a list of all of the events emitted from the class
     *
     * @return array
     */
    public static function getAllEvents();

    /**
     * Set the EventDispatcher of the request
     *
     * @param EventDispatcherInterface $eventDispatcher
     *
     * @return self
     */
    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher);

    /**
     * Get the EventDispatcher of the request
     *
     * @return EventDispatcherInterface
     */
    public function getEventDispatcher();

    /**
     * Helper to dispatch Guzzle events and set the event name on the event
     *
     * @param string $eventName Name of the event to dispatch
     * @param array  $context   Context of the event
     */
    public function dispatch($eventName, array $context = array());

    /**
     * Add an event subscriber to the dispatcher
     *
     * @param EventSubscriberInterface $subscriber Event subscriber
     *
     * @return self
     */
    public function addSubscriber(EventSubscriberInterface $subscriber);
}
