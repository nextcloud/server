<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bernhard Posselt <dev@bernhard-posselt.com>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OC\AppFramework\Utility;

use ArrayAccess;
use Closure;
use OCP\AppFramework\QueryException;
use OCP\IContainer;
use Pimple\Container;
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
	/** @var Container */
	private $container;

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
	public function get(string $id) {
		return $this->query($id);
	}

	public function has(string $id): bool {
		// If a service is no registered but is an existing class, we can probably load it
		return isset($this->container[$id]) || class_exists($id);
	}

	/**
	 * @param ReflectionClass $class the class to instantiate
	 * @return \stdClass the created class
	 * @suppress PhanUndeclaredClassInstanceof
	 */
	private function buildClass(ReflectionClass $class) {
		$constructor = $class->getConstructor();
		if ($constructor === null) {
			return $class->newInstance();
		}

		return $class->newInstanceArgs(array_map(function (ReflectionParameter $parameter) {
			$parameterType = $parameter->getType();

			$resolveName = $parameter->getName();

			// try to find out if it is a class or a simple parameter
			if ($parameterType !== null && ($parameterType instanceof ReflectionNamedType) && !$parameterType->isBuiltin()) {
				$resolveName = $parameterType->getName();
			}

			try {
				$builtIn = $parameter->hasType() && ($parameter->getType() instanceof ReflectionNamedType)
					&& $parameter->getType()->isBuiltin();
				return $this->query($resolveName, !$builtIn);
			} catch (QueryException $e) {
				// Service not found, use the default value when available
				if ($parameter->isDefaultValueAvailable()) {
					return $parameter->getDefaultValue();
				}

				if ($parameterType !== null && ($parameterType instanceof ReflectionNamedType) && !$parameterType->isBuiltin()) {
					$resolveName = $parameter->getName();
					try {
						return $this->query($resolveName);
					} catch (QueryException $e2) {
						// Pass null if typed and nullable
						if ($parameter->allowsNull() && ($parameterType instanceof ReflectionNamedType)) {
							return null;
						}

						// don't lose the error we got while trying to query by type
						throw new QueryException($e->getMessage(), (int) $e->getCode(), $e);
					}
				}

				throw $e;
			}
		}, $constructor->getParameters()));
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
		if (isset($this[$name])) {
			unset($this[$name]);
		}
		if ($shared) {
			$this[$name] = $wrapped;
		} else {
			$this[$name] = $this->container->factory($wrapped);
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

	/*
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
	public function offsetSet($id, $service): void {
		$this->container->offsetSet($id, $service);
	}

	/**
	 * @deprecated 20.0.0
	 */
	public function offsetUnset($offset): void {
		$this->container->offsetUnset($offset);
	}
}
