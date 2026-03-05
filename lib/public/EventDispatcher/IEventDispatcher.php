<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\EventDispatcher;

/**
 * Event dispatcher service of Nextcloud
 *
 * @since 17.0.0
 */
interface IEventDispatcher {
	/**
	 * @template T of \OCP\EventDispatcher\Event
	 * @param string $eventName preferably the fully-qualified class name of the Event sub class
	 * @psalm-param string|class-string<T> $eventName preferably the fully-qualified class name of the Event sub class
	 * @param callable $listener the object that is invoked when a matching event is dispatched
	 * @psalm-param callable(T):void $listener
	 * @param int $priority The higher this value, the earlier an event
	 *                      listener will be triggered in the chain (defaults to 0)
	 *
	 * @since 17.0.0
	 */
	public function addListener(string $eventName, callable $listener, int $priority = 0): void;

	/**
	 * @template T of \OCP\EventDispatcher\Event
	 * @param string $eventName preferably the fully-qualified class name of the Event sub class
	 * @psalm-param string|class-string<T> $eventName preferably the fully-qualified class name of the Event sub class
	 * @param callable $listener the object that is invoked when a matching event is dispatched
	 * @psalm-param callable(T):void $listener
	 *
	 * @since 19.0.0
	 */
	public function removeListener(string $eventName, callable $listener): void;

	/**
	 * @template T of \OCP\EventDispatcher\Event
	 * @param string $eventName preferably the fully-qualified class name of the Event sub class to listen for
	 * @psalm-param string|class-string<T> $eventName preferably the fully-qualified class name of the Event sub class to listen for
	 * @param string $className fully qualified class name (or ::class notation) of a \OCP\EventDispatcher\IEventListener that can be built by the DI container
	 * @psalm-param class-string<\OCP\EventDispatcher\IEventListener<T>> $className fully qualified class name that can be built by the DI container
	 * @param int $priority The higher this value, the earlier an event
	 *                      listener will be triggered in the chain (defaults to 0)
	 *
	 * @since 17.0.0
	 */
	public function addServiceListener(string $eventName, string $className, int $priority = 0): void;

	/**
	 * @template T of \OCP\EventDispatcher\Event
	 * @param string $eventName preferably the fully-qualified class name of the Event sub class
	 *
	 * @return bool TRUE if event has registered listeners
	 * @since 29.0.0
	 */
	public function hasListeners(string $eventName): bool;

	/**
	 * @template T of \OCP\EventDispatcher\Event
	 * @param string $eventName
	 * @psalm-param string|class-string<T> $eventName
	 * @param Event $event
	 * @psalm-param T $event
	 *
	 * @since 17.0.0
	 * @deprecated 21.0.0 use \OCP\EventDispatcher\IEventDispatcher::dispatchTyped
	 */
	public function dispatch(string $eventName, Event $event): void;

	/**
	 * Dispatch a typed event
	 *
	 * Only use this with subclasses of ``\OCP\EventDispatcher\Event``.
	 * The object's class will determine the event name.
	 *
	 * @param Event $event
	 *
	 * @since 18.0.0
	 */
	public function dispatchTyped(Event $event): void;
}
