<?php
declare(strict_types=1);
namespace Psalm\Internal\LanguageServer;

/**
 * Event Emitter Interface
 *
 * Anything that accepts listeners and emits events should implement this
 * interface.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
interface EmitterInterface
{
    /**
     * Subscribe to an event.
     */
    public function on(string $eventName, callable $callBack, int $priority = 100): void;

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
    ) : bool;

    /**
     * Returns the list of listeners for an event.
     *
     * The list is returned as an array, and the list of events are sorted by
     * their priority.
     *
     * @return callable[]
     */
    public function listeners(string $eventName) : array;

    /**
     * Removes a specific listener from an event.
     *
     * If the listener could not be found, this method will return false. If it
     * was removed it will return true.
     */
    public function removeListener(string $eventName, callable $listener) : bool;
}
