<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
// use OCP namespace for all classes that are considered public.
// This means that they should be used by apps instead of the internal Nextcloud classes
namespace OCP;

use Closure;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Class IContainer
 *
 * IContainer is the basic interface to be used for any internal dependency injection mechanism
 *
 * @since 6.0.0
 */
interface IContainer extends ContainerInterface {
	/**
	 * Finds an entry of the container by its identifier and returns it.
	 *
	 * @template T
	 * @param class-string<T>|string $id Identifier of the entry to look for.
	 *
	 * @throws NotFoundExceptionInterface No entry was found for **this** identifier.
	 * @throws ContainerExceptionInterface Error while retrieving the entry.
	 *
	 * @return ($id is class-string<T> ? T : mixed) Entry.
	 * @since 34.0.0
	 */
	public function get(string $id);

	/**
	 * @template T
	 *
	 * If a parameter is not registered in the container try to instantiate it
	 * by using reflection to find out how to build the class
	 * @param class-string<T>|string $name
	 * @return ($name is class-string<T> ? T : mixed)
	 * @since 8.2.0
	 * @deprecated 20.0.0 use {@see self::get()}
	 * @throws ContainerExceptionInterface if the class could not be found or instantiated
	 */
	public function resolve(string $name): mixed;

	/**
	 * Look up a service for a given name in the container.
	 *
	 * @template T
	 * @param class-string<T>|string $name
	 * @param bool $autoload Should we try to autoload the service. If we are trying to resolve built in types this makes no sense for example
	 * @return ($name is class-string<T> ? T : mixed)
	 * @throws ContainerExceptionInterface if the query could not be resolved
	 * @throws NotFoundExceptionInterface if the name could not be found within the container
	 * @since 6.0.0
	 * @deprecated 20.0.0 use {@see self::get()}
	 */
	public function query(string $name, bool $autoload = true): mixed;

	/**
	 * A value is stored in the container with it's corresponding name
	 *
	 * @since 6.0.0
	 * @deprecated 20.0.0 use \OCP\AppFramework\Bootstrap\IRegistrationContext::registerParameter
	 */
	public function registerParameter(string $name, mixed $value): void;

	/**
	 * A service is registered in the container where a closure is passed in which will actually
	 * create the service on demand.
	 * In case the parameter $shared is set to true (the default usage) the once created service will remain in
	 * memory and be reused on subsequent calls.
	 * In case the parameter is false the service will be recreated on every call.
	 *
	 * @param \Closure(IContainer): mixed $closure
	 * @since 6.0.0
	 * @deprecated 20.0.0 use \OCP\AppFramework\Bootstrap\IRegistrationContext::registerService
	 */
	public function registerService(string $name, Closure $closure, bool $shared = true): void;

	/**
	 * Shortcut for returning a service from a service under a different key,
	 * e.g. to tell the container to return a class when queried for an
	 * interface
	 * @param string $alias the alias that should be registered
	 * @param string $target the target that should be resolved instead
	 * @since 8.2.0
	 * @deprecated 20.0.0 use \OCP\AppFramework\Bootstrap\IRegistrationContext::registerServiceAlias
	 */
	public function registerAlias(string $alias, string $target): void;
}
