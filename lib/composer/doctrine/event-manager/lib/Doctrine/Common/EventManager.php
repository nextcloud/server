<?php

namespace Doctrine\Common;

use function spl_object_hash;

/**
 * The EventManager is the central point of Doctrine's event listener system.
 * Listeners are registered on the manager and events are dispatched through the
 * manager.
 */
class EventManager
{
    /**
     * Map of registered listeners.
     * <event> => <listeners>
     *
     * @var object[][]
     */
    private $_listeners = [];

    /**
     * Dispatches an event to all registered listeners.
     *
     * @param string         $eventName The name of the event to dispatch. The name of the event is
     *                                  the name of the method that is invoked on listeners.
     * @param EventArgs|null $eventArgs The event arguments to pass to the event handlers/listeners.
     *                                  If not supplied, the single empty EventArgs instance is used.
     *
     * @return void
     */
    public function dispatchEvent($eventName, ?EventArgs $eventArgs = null)
    {
        if (! isset($this->_listeners[$eventName])) {
            return;
        }

        $eventArgs = $eventArgs ?? EventArgs::getEmptyInstance();

        foreach ($this->_listeners[$eventName] as $listener) {
            $listener->$eventName($eventArgs);
        }
    }

    /**
     * Gets the listeners of a specific event or all listeners.
     *
     * @param string|null $event The name of the event.
     *
     * @return object[]|object[][] The event listeners for the specified event, or all event listeners.
     */
    public function getListeners($event = null)
    {
        return $event ? $this->_listeners[$event] : $this->_listeners;
    }

    /**
     * Checks whether an event has any registered listeners.
     *
     * @param string $event
     *
     * @return bool TRUE if the specified event has any listeners, FALSE otherwise.
     */
    public function hasListeners($event)
    {
        return ! empty($this->_listeners[$event]);
    }

    /**
     * Adds an event listener that listens on the specified events.
     *
     * @param string|string[] $events   The event(s) to listen on.
     * @param object          $listener The listener object.
     *
     * @return void
     */
    public function addEventListener($events, $listener)
    {
        // Picks the hash code related to that listener
        $hash = spl_object_hash($listener);

        foreach ((array) $events as $event) {
            // Overrides listener if a previous one was associated already
            // Prevents duplicate listeners on same event (same instance only)
            $this->_listeners[$event][$hash] = $listener;
        }
    }

    /**
     * Removes an event listener from the specified events.
     *
     * @param string|string[] $events
     * @param object          $listener
     *
     * @return void
     */
    public function removeEventListener($events, $listener)
    {
        // Picks the hash code related to that listener
        $hash = spl_object_hash($listener);

        foreach ((array) $events as $event) {
            unset($this->_listeners[$event][$hash]);
        }
    }

    /**
     * Adds an EventSubscriber. The subscriber is asked for all the events it is
     * interested in and added as a listener for these events.
     *
     * @param EventSubscriber $subscriber The subscriber.
     *
     * @return void
     */
    public function addEventSubscriber(EventSubscriber $subscriber)
    {
        $this->addEventListener($subscriber->getSubscribedEvents(), $subscriber);
    }

    /**
     * Removes an EventSubscriber. The subscriber is asked for all the events it is
     * interested in and removed as a listener for these events.
     *
     * @param EventSubscriber $subscriber The subscriber.
     *
     * @return void
     */
    public function removeEventSubscriber(EventSubscriber $subscriber)
    {
        $this->removeEventListener($subscriber->getSubscribedEvents(), $subscriber);
    }
}
