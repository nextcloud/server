<?php

declare(strict_types=1);

namespace Sabre\Event;

/**
 * Wildcard Emitter Trait.
 *
 * This trait provides the implementation for WildCardEmitter
 * Refer to that class for the full documentation about this
 * trait.
 *
 * Normally you can just instantiate that class, but if you want to add
 * emitter functionality to existing classes, using the trait might be a
 * better way to do this.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
trait WildcardEmitterTrait
{
    /**
     * Subscribe to an event.
     */
    public function on(string $eventName, callable $callBack, int $priority = 100)
    {
        // If it ends with a wildcard, we use the wildcardListeners array
        if ('*' === $eventName[\strlen($eventName) - 1]) {
            $eventName = \substr($eventName, 0, -1);
            $listeners = &$this->wildcardListeners;
        } else {
            $listeners = &$this->listeners;
        }

        // Always fully reset the listener index. This is fairly sane for most
        // applications, because there's a clear "event registering" and "event
        // emitting" phase, but can be slow if there's a lot adding and removing
        // of listeners during emitting of events.
        $this->listenerIndex = [];

        if (!isset($listeners[$eventName])) {
            $listeners[$eventName] = [];
        }
        $listeners[$eventName][] = [$priority, $callBack];
    }

    /**
     * Subscribe to an event exactly once.
     */
    public function once(string $eventName, callable $callBack, int $priority = 100)
    {
        $wrapper = null;
        $wrapper = function () use ($eventName, $callBack, &$wrapper) {
            $this->removeListener($eventName, $wrapper);

            return \call_user_func_array($callBack, \func_get_args());
        };

        $this->on($eventName, $wrapper, $priority);
    }

    /**
     * Emits an event.
     *
     * This method will return true if 0 or more listeners were successfully
     * handled. false is returned if one of the events broke the event chain.
     *
     * If the continueCallBack is specified, this callback will be called every
     * time before the next event handler is called.
     *
     * If the continueCallback returns false, event propagation stops. This
     * allows you to use the eventEmitter as a means for listeners to implement
     * functionality in your application, and break the event loop as soon as
     * some condition is fulfilled.
     *
     * Note that returning false from an event subscriber breaks propagation
     * and returns false, but if the continue-callback stops propagation, this
     * is still considered a 'successful' operation and returns true.
     *
     * Lastly, if there are 5 event handlers for an event. The continueCallback
     * will be called at most 4 times.
     */
    public function emit(string $eventName, array $arguments = [], callable $continueCallBack = null): bool
    {
        if (\is_null($continueCallBack)) {
            foreach ($this->listeners($eventName) as $listener) {
                $result = \call_user_func_array($listener, $arguments);
                if (false === $result) {
                    return false;
                }
            }
        } else {
            $listeners = $this->listeners($eventName);
            $counter = \count($listeners);

            foreach ($listeners as $listener) {
                --$counter;
                $result = \call_user_func_array($listener, $arguments);
                if (false === $result) {
                    return false;
                }

                if ($counter > 0) {
                    if (!$continueCallBack()) {
                        break;
                    }
                }
            }
        }

        return true;
    }

    /**
     * Returns the list of listeners for an event.
     *
     * The list is returned as an array, and the list of events are sorted by
     * their priority.
     *
     * @return callable[]
     */
    public function listeners(string $eventName): array
    {
        if (!\array_key_exists($eventName, $this->listenerIndex)) {
            // Create a new index.
            $listeners = [];
            $listenersPriority = [];
            if (isset($this->listeners[$eventName])) {
                foreach ($this->listeners[$eventName] as $listener) {
                    $listenersPriority[] = $listener[0];
                    $listeners[] = $listener[1];
                }
            }

            foreach ($this->wildcardListeners as $wcEvent => $wcListeners) {
                // Wildcard match
                if (\substr($eventName, 0, \strlen($wcEvent)) === $wcEvent) {
                    foreach ($wcListeners as $listener) {
                        $listenersPriority[] = $listener[0];
                        $listeners[] = $listener[1];
                    }
                }
            }

            // Sorting by priority
            \array_multisort($listenersPriority, SORT_NUMERIC, $listeners);

            // Creating index
            $this->listenerIndex[$eventName] = $listeners;
        }

        return $this->listenerIndex[$eventName];
    }

    /**
     * Removes a specific listener from an event.
     *
     * If the listener could not be found, this method will return false. If it
     * was removed it will return true.
     */
    public function removeListener(string $eventName, callable $listener): bool
    {
        // If it ends with a wildcard, we use the wildcardListeners array
        if ('*' === $eventName[\strlen($eventName) - 1]) {
            $eventName = \substr($eventName, 0, -1);
            $listeners = &$this->wildcardListeners;
        } else {
            $listeners = &$this->listeners;
        }

        if (!isset($listeners[$eventName])) {
            return false;
        }

        foreach ($listeners[$eventName] as $index => $check) {
            if ($check[1] === $listener) {
                // Remove listener
                unset($listeners[$eventName][$index]);
                // Reset index
                $this->listenerIndex = [];

                return true;
            }
        }

        return false;
    }

    /**
     * Removes all listeners.
     *
     * If the eventName argument is specified, all listeners for that event are
     * removed. If it is not specified, every listener for every event is
     * removed.
     */
    public function removeAllListeners(string $eventName = null)
    {
        if (\is_null($eventName)) {
            $this->listeners = [];
            $this->wildcardListeners = [];
        } else {
            if ('*' === $eventName[\strlen($eventName) - 1]) {
                // Wildcard event
                unset($this->wildcardListeners[\substr($eventName, 0, -1)]);
            } else {
                unset($this->listeners[$eventName]);
            }
        }

        // Reset index
        $this->listenerIndex = [];
    }

    /**
     * The list of listeners.
     */
    protected $listeners = [];

    /**
     * The list of "wildcard listeners".
     */
    protected $wildcardListeners = [];

    /**
     * An index of listeners for a specific event name. This helps speeding
     * up emitting events after all listeners have been set.
     *
     * If the list of listeners changes though, the index clears.
     */
    protected $listenerIndex = [];
}
