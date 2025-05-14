<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\AppFramework\Utility;

use ArrayAccess;
use Closure;
use OCP\AppFramework\QueryException;
use OCP\IContainer;
use Pimple\Container;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use ReflectionParameter;
use function class_exists;

/**
 * SimpleContainer is a simple implementation of a container on basis of Pimple
 */
class SimpleContainer implements ArrayAccess, ContainerInterface, IContainer {
	public static bool $useLazyObjects = false;

	private Container $container;

	public function __construct() {
		$this->container = new Container();
	}

	/**
	 * @template T
	 * @param class-string<T>|string $id
	 * @return T|mixed
	 * @psalm-template S as class-string<T>|string
	 * @psalm-param S $id
	 * @psalm-return (S is class-string<T> ? T : mixed)
	 */
	public function get(string $id): mixed {
		return $this->query($id);
	}

	public function has(string $id): bool {
		// If a service is no registered but is an existing class, we can probably load it
		return isset($this->container[$id]) || class_exists($id);
	}

	/**
	 * @param ReflectionClass $class the class to instantiate
	 * @return object the created class
	 * @suppress PhanUndeclaredClassInstanceof
	 */
	private function buildClass(ReflectionClass $class): object {
		$constructor = $class->getConstructor();
		if ($constructor === null) {
			/* No constructor, return a instance directly */
			return $class->newInstance();
		}
		if (PHP_VERSION_ID >= 80400 && self::$useLazyObjects) {
			/* For PHP>=8.4, use a lazy ghost to delay constructor and dependency resolving */
			/** @psalm-suppress UndefinedMethod */
			return $class->newLazyGhost(function (object $object) use ($constructor): void {
				/** @psalm-suppress DirectConstructorCall For lazy ghosts we have to call the constructor directly */
				$object->__construct(...$this->buildClassConstructorParameters($constructor));
			});
		} else {
			return $class->newInstanceArgs($this->buildClassConstructorParameters($constructor));
		}
	}

	private function buildClassConstructorParameters(\ReflectionMethod $constructor): array {
		return array_map(function (ReflectionParameter $parameter) {
			$parameterType = $parameter->getType();

			$resolveName = $parameter->getName();

			// try to find out if it is a class or a simple parameter
			if ($parameterType !== null && ($parameterType instanceof ReflectionNamedType) && !$parameterType->isBuiltin()) {
				$resolveName = $parameterType->getName();
			}

			try {
				$builtIn = $parameterType !== null && ($parameterType instanceof ReflectionNamedType)
							&& $parameterType->isBuiltin();
				return $this->query($resolveName, !$builtIn);
			} catch (ContainerExceptionInterface $e) {
				// Service not found, use the default value when available
				if ($parameter->isDefaultValueAvailable()) {
					return $parameter->getDefaultValue();
				}

				if ($parameterType !== null && ($parameterType instanceof ReflectionNamedType) && !$parameterType->isBuiltin()) {
					$resolveName = $parameter->getName();
					try {
						return $this->query($resolveName);
					} catch (ContainerExceptionInterface $e2) {
						// Pass null if typed and nullable
						if ($parameter->allowsNull() && ($parameterType instanceof ReflectionNamedType)) {
							return null;
						}

						// don't lose the error we got while trying to query by type
						throw new QueryException($e->getMessage(), (int)$e->getCode(), $e);
					}
				}

				throw $e;
			}
		}, $constructor->getParameters());
	}

	public function resolve($name) {
		$baseMsg = 'Could not resolve ' . $name . '!';
		try {
			$class = new ReflectionClass($name);
			if ($class->isInstantiable()) {
				return $this->buildClass($class);
			} else {
				throw new QueryException($baseMsg .
					' Class can not be instantiated');
			}
		} catch (ReflectionException $e) {
			// Class does not exist
			throw new QueryNotFoundException($baseMsg . ' ' . $e->getMessage());
		}
	}

	public function query(string $name, bool $autoload = true) {
		$name = $this->sanitizeName($name);
		if (isset($this->container[$name])) {
			return $this->container[$name];
		}

		if ($autoload) {
			$object = $this->resolve($name);
			$this->registerService($name, function () use ($object) {
				return $object;
			});
			return $object;
		}

		throw new QueryNotFoundException('Could not resolve ' . $name . '!');
	}

	/**
	 * @param string $name
	 * @param mixed $value
	 */
	public function registerParameter($name, $value) {
		$this[$name] = $value;
	}

	/**
	 * The given closure is call the first time the given service is queried.
	 * The closure has to return the instance for the given service.
	 * Created instance will be cached in case $shared is true.
	 *
	 * @param string $name name of the service to register another backend for
	 * @param Closure $closure the closure to be called on service creation
	 * @param bool $shared
	 */
	public function registerService($name, Closure $closure, $shared = true) {
		$wrapped = function () use ($closure) {
			return $closure($this);
		};
		$name = $this->sanitizeName($name);
		if (isset($this->container[$name])) {
			unset($this->container[$name]);
		}
		if ($shared) {
			$this->container[$name] = $wrapped;
		} else {
			$this->container[$name] = $this->container->factory($wrapped);
		}
	}

	/**
	 * Shortcut for returning a service from a service under a different key,
	 * e.g. to tell the container to return a class when queried for an
	 * interface
	 * @param string $alias the alias that should be registered
	 * @param string $target the target that should be resolved instead
	 */
	public function registerAlias($alias, $target) {
		$this->registerService($alias, function (ContainerInterface $container) use ($target) {
			return $container->get($target);
		}, false);
	}

	/**
	 * @param string $name
	 * @return string
	 */
	protected function sanitizeName($name) {
		if (isset($name[0]) && $name[0] === '\\') {
			return ltrim($name, '\\');
		}
		return $name;
	}

	/**
	 * @deprecated 20.0.0 use \Psr\Container\ContainerInterface::has
	 */
	public function offsetExists($id): bool {
		return $this->container->offsetExists($id);
	}

	/**
	 * @deprecated 20.0.0 use \Psr\Container\ContainerInterface::get
	 * @return mixed
	 */
	#[\ReturnTypeWillChange]
	public function offsetGet($id) {
		return $this->container->offsetGet($id);
	}

	/**
	 * @deprecated 20.0.0 use \OCP\IContainer::registerService
	 */
	public function offsetSet($offset, $value): void {
		$this->container->offsetSet($offset, $value);
	}

	/**
	 * @deprecated 20.0.0
	 */
	public function offsetUnset($offset): void {
		$this->container->offsetUnset($offset);
	}
}
