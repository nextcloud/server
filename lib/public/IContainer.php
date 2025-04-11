<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
// use OCP namespace for all classes that are considered public.
// This means that they should be used by apps instead of the internal Nextcloud classes

namespace OCP;

use Closure;
use OCP\AppFramework\QueryException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Class IContainer
 *
 * IContainer is the basic interface to be used for any internal dependency injection mechanism
 *
 * @since 6.0.0
 * @deprecated 20.0.0 use \Psr\Container\ContainerInterface
 */
interface IContainer extends ContainerInterface {
	/**
	 * @template T
	 *
	 * If a parameter is not registered in the container try to instantiate it
	 * by using reflection to find out how to build the class
	 * @param string $name the class name to resolve
	 * @psalm-param string|class-string<T> $name
	 * @return \stdClass
	 * @psalm-return ($name is class-string ? T : mixed)
	 * @since 8.2.0
	 * @deprecated 20.0.0 use \Psr\Container\ContainerInterface::get
	 * @throws ContainerExceptionInterface if the class could not be found or instantiated
	 * @throws QueryException if the class could not be found or instantiated
	 */
	public function resolve($name);

	/**
	 * Look up a service for a given name in the container.
	 *
	 * @template T
	 *
	 * @param string $name
	 * @psalm-param string|class-string<T> $name
	 * @param bool $autoload Should we try to autoload the service. If we are trying to resolve built in types this makes no sense for example
	 * @return mixed
	 * @psalm-return ($name is class-string ? T : mixed)
	 * @throws ContainerExceptionInterface if the query could not be resolved
	 * @throws NotFoundExceptionInterface if the name could not be found within the container
	 * @throws QueryException if the query could not be resolved
	 * @since 6.0.0
	 * @deprecated 20.0.0 use \Psr\Container\ContainerInterface::get
	 */
	public function query(string $name, bool $autoload = true);

	/**
	 * A value is stored in the container with it's corresponding name
	 *
	 * @param string $name
	 * @param mixed $value
	 * @return void
	 * @since 6.0.0
	 * @deprecated 20.0.0 use \OCP\AppFramework\Bootstrap\IRegistrationContext::registerParameter
	 */
	public function registerParameter($name, $value);

	/**
	 * A service is registered in the container where a closure is passed in which will actually
	 * create the service on demand.
	 * In case the parameter $shared is set to true (the default usage) the once created service will remain in
	 * memory and be reused on subsequent calls.
	 * In case the parameter is false the service will be recreated on every call.
	 *
	 * @param string $name
	 * @param \Closure $closure
	 * @param bool $shared
	 * @return void
	 * @since 6.0.0
	 * @deprecated 20.0.0 use \OCP\AppFramework\Bootstrap\IRegistrationContext::registerService
	 */
	public function registerService($name, Closure $closure, $shared = true);

	/**
	 * Shortcut for returning a service from a service under a different key,
	 * e.g. to tell the container to return a class when queried for an
	 * interface
	 * @param string $alias the alias that should be registered
	 * @param string $target the target that should be resolved instead
	 * @since 8.2.0
	 * @deprecated 20.0.0 use \OCP\AppFramework\Bootstrap\IRegistrationContext::registerServiceAlias
	 */
	public function registerAlias($alias, $target);
}
