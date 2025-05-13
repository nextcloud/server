<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\EventDispatcher;

use Psr\EventDispatcher\StoppableEventInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Symfony Event Dispatcher Override
 */
class CoreDispatcher extends EventDispatcher {

	public function __construct(
		private LoggerInterface $logger
	) {
		parent::__construct();
	}

	/**
     * Triggers the listeners of an event.
     *
     * This method can be overridden to add functionality that is executed
     * for each listener.
     *
     * @param callable[] $listeners The event listeners
     * @param string     $eventName The name of the event to dispatch
     * @param object     $event     The event object to pass to the event handlers/listeners
     *
     * @return void
     */
    protected function callListeners(iterable $listeners, string $eventName, object $event)
    {
        $stoppable = $event instanceof StoppableEventInterface;

        foreach ($listeners as $listener) {
            if ($stoppable && $event->isPropagationStopped()) {
                break;
            }
			// execute the listener and catch any exceptions to prevent an error in a listener from breaking the emitting process
			try {
				$listener($event, $eventName, $this);
			} catch (\Throwable $e) {
				$this->logger->error('Error occurred while executing an event listener ' . get_class($event), ['exception' => $e]);
			}
        }
    }
	
}
