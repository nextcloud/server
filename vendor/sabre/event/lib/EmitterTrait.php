<?php

declare(strict_types=1);

namespace Sabre\Event;

/**
 * Event Emitter Trait.
 *
 * This trait contains all the basic functions to implement an
 * EventEmitterInterface.
 *
 * Using the trait + interface allows you to add EventEmitter capabilities
 * without having to change your base-class.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
trait EmitterTrait
{
    /**
     * Subscribe to an event.
     */
    public function on(string $eventName, callable $callBack, int $priority = 100)
    {
        if (!isset($this->listeners[$eventName])) {
            $this->listeners[$eventName] = [
                true,  // If there's only one item, it's sorted
                [$priority],
                [$callBack],
            ];
        } else {
            $this->listeners[$eventName][0] = false; // marked as unsorted
            $this->listeners[$eventName][1][] = $priority;
            $this->listeners[$eventName][2][] = $callBack;
        }
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
    public function emit(string $eventName, array $arguments = [], ?callable $continueCallBack = null): bool
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
        if (!isset($this->listeners[$eventName])) {
            return [];
        }

        // The list is not sorted
        if (!$this->listeners[$eventName][0]) {
            // Sorting
            \array_multisort($this->listeners[$eventName][1], SORT_NUMERIC, $this->listeners[$eventName][2]);

            // Marking the listeners as sorted
            $this->listeners[$eventName][0] = true;
        }

        return $this->listeners[$eventName][2];
    }

    /**
     * Removes a specific listener from an event.
     *
     * If the listener could not be found, this method will return false. If it
     * was removed it will return true.
     */
    public function removeListener(string $eventName, callable $listener): bool
    {
        if (!isset($this->listeners[$eventName])) {
            return false;
        }
        foreach ($this->listeners[$eventName][2] as $index => $check) {
            if ($check === $listener) {
                unset($this->listeners[$eventName][1][$index]);
                unset($this->listeners[$eventName][2][$index]);

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
    public function removeAllListeners(?string $eventName = null)
    {
        if (!\is_null($eventName)) {
            unset($this->listeners[$eventName]);
        } else {
            $this->listeners = [];
        }
    }

    /**
     * The list of listeners.
     *
     * @var array
     */
    protected $listeners = [];
}
