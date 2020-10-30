<?php
declare(strict_types=1);
namespace Psalm\Internal\LanguageServer;

use const SORT_NUMERIC;

/**
 * Event Emitter Trait
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
     * The list of listeners
     *
     * @var array<string, array{0: bool, 1: int[], 2: callable[]}>
     */
    protected $listeners = [];

    /**
     * Subscribe to an event.
     */
    public function on(string $eventName, callable $callBack, int $priority = 100): void
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
     *
     * @param list<mixed> $arguments
     */
    public function emit(
        string $eventName,
        array $arguments = [],
        ?callable $continueCallBack = null
    ) : bool {
        if ($continueCallBack === null) {
            foreach ($this->listeners($eventName) as $listener) {
                /** @psalm-suppress MixedAssignment */
                $result = \call_user_func_array($listener, $arguments);
                if ($result === false) {
                    return false;
                }
            }
        } else {
            $listeners = $this->listeners($eventName);
            $counter = \count($listeners);

            foreach ($listeners as $listener) {
                --$counter;
                /** @psalm-suppress MixedAssignment */
                $result = \call_user_func_array($listener, $arguments);
                if ($result === false) {
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
    public function listeners(string $eventName) : array
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
    public function removeListener(string $eventName, callable $listener) : bool
    {
        if (!isset($this->listeners[$eventName])) {
            return false;
        }
        foreach ($this->listeners[$eventName][2] as $index => $check) {
            if ($check === $listener) {
                unset($this->listeners[$eventName][1][$index], $this->listeners[$eventName][2][$index]);

                return true;
            }
        }

        return false;
    }
}
