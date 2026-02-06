<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\EventDispatcher;

use OC\Broadcast\Events\BroadcastEvent;
use OC\Log;
use OC\Log\PsrLoggerAdapter;
use OCP\Broadcast\Events\IBroadcastEvent;
use OCP\EventDispatcher\ABroadcastedEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher as SymfonyDispatcher;
use function get_class;

class EventDispatcher implements IEventDispatcher {
	public function __construct(
		private SymfonyDispatcher $dispatcher,
		private ContainerInterface $container,
		private LoggerInterface $logger,
	) {
		// inject the event dispatcher into the logger
		// this is done here because there is a cyclic dependency between the event dispatcher and logger
		if ($this->logger instanceof Log || $this->logger instanceof PsrLoggerAdapter) {
			$this->logger->setEventDispatcher($this);
		}
	}

	public function addListener(string $eventName,
		callable $listener,
		int $priority = 0): void {
		$this->dispatcher->addListener($eventName, $listener, $priority);
	}

	public function removeListener(string $eventName,
		callable $listener): void {
		$this->dispatcher->removeListener($eventName, $listener);
	}

	public function addServiceListener(string $eventName,
		string $className,
		int $priority = 0): void {
		$listener = new ServiceEventListener(
			$this->container,
			$className,
			$this->logger
		);

		$this->addListener($eventName, $listener, $priority);
	}

	public function hasListeners(string $eventName): bool {
		return $this->dispatcher->hasListeners($eventName);
	}

	/**
	 * @deprecated
	 */
	public function dispatch(string $eventName,
		Event $event): void {
		$this->dispatcher->dispatch($event, $eventName);

		if ($event instanceof ABroadcastedEvent && !$event->isPropagationStopped()) {
			// Propagate broadcast
			$this->dispatch(
				IBroadcastEvent::class,
				new BroadcastEvent($event)
			);
		}
	}

	public function dispatchTyped(Event $event): void {
		$this->dispatch(get_class($event), $event);
	}

	/**
	 * @return SymfonyDispatcher
	 * @deprecated 20.0.0
	 */
	public function getSymfonyDispatcher(): SymfonyDispatcher {
		return $this->dispatcher;
	}
}
