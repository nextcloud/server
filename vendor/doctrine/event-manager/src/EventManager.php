<?php

namespace Doctrine\Common;

use Doctrine\Deprecations\Deprecation;

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
     * @var array<string, object[]>
     */
    private $listeners = [];

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
        if (! isset($this->listeners[$eventName])) {
            return;
        }

        $eventArgs = $eventArgs ?? EventArgs::getEmptyInstance();

        foreach ($this->listeners[$eventName] as $listener) {
            $listener->$eventName($eventArgs);
        }
    }

    /**
     * Gets the listeners of a specific event.
     *
     * @param string|null $event The name of the event.
     *
     * @return object[]|array<string, object[]> The event listeners for the specified event, or all event listeners.
     * @psalm-return ($event is null ? array<string, object[]> : object[])
     */
    public function getListeners($event = null)
    {
        if ($event === null) {
            Deprecation::trigger(
                'doctrine/event-manager',
                'https://github.com/doctrine/event-manager/pull/50',
                'Calling %s without an event name is deprecated. Call getAllListeners() instead.',
                __METHOD__
            );

            return $this->getAllListeners();
        }

        return $this->listeners[$event] ?? [];
    }

    /**
     * Gets all listeners keyed by event name.
     *
     * @return array<string, object[]> The event listeners for the specified event, or all event listeners.
     */
    public function getAllListeners(): array
    {
        return $this->listeners;
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
        return ! empty($this->listeners[$event]);
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
            $this->listeners[$event][$hash] = $listener;
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
            unset($this->listeners[$event][$hash]);
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
