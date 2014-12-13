<?php
/**
 * ownCloud - App Framework
 *
 * @author Bernhard Posselt
 * @copyright 2014 Bernhard Posselt <dev@bernhard-posselt.com>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OC\AppFramework\Utility;

use \OCP\AppFramework\QueryException;

/**
 * Class SimpleContainer
 *
 * SimpleContainer is a simple implementation of IContainer on basis of \Pimple
 */
class SimpleContainer extends \Pimple\Container implements \OCP\IContainer {


	/**
	 * @param ReflectionClass $class the class to instantiate
	 * @return stdClass the created class
	 */
	private function buildClass(\ReflectionClass $class) {
		$constructor = $class->getConstructor();
		if ($constructor === null) {
			return $class->newInstance();
		} else {
			$parameters = [];
			foreach ($constructor->getParameters() as $parameter) {
				$parameterClass = $parameter->getClass();

				// try to find out if it is a class or a simple parameter
				if ($parameterClass === null) {
					$resolveName = $parameter->getName();
				} else {
					$resolveName = $parameterClass->name;
				}

				$parameters[] = $this->query($resolveName);
			}
			return $class->newInstanceArgs($parameters);
		}
	}


	/**
	 * If a parameter is not registered in the container try to instantiate it
	 * by using reflection to find out how to build the class
	 * @param string $name the class name to resolve
	 * @throws QueryException if the class could not be found or instantiated
	 */
	private function resolve($name) {
		$baseMsg = 'Could not resolve ' . $name . '!';
		try {
			$class = new \ReflectionClass($name);
			if ($class->isInstantiable()) {
				return $this->buildClass($class);
			} else {
				throw new QueryException($baseMsg .
					' Class can not be instantiated');
			}
		} catch(\ReflectionException $e) {
			throw new QueryException($baseMsg . ' ' . $e->getMessage());
		}
	}


	/**
	 * @param string $name name of the service to query for
	 * @return mixed registered service for the given $name
	 * @throws QueryExcpetion if the query could not be resolved
	 */
	public function query($name) {
		if ($this->offsetExists($name)) {
			return $this->offsetGet($name);
		} else {
			$object = $this->resolve($name);
			$this->registerService($name, function () use ($object) {
				return $object;
			});
			return $object;
		}
	}

	/**
	 * @param string $name
	 * @param mixed $value
	 */
	function registerParameter($name, $value) {
		$this[$name] = $value;
	}

	/**
	 * The given closure is call the first time the given service is queried.
	 * The closure has to return the instance for the given service.
	 * Created instance will be cached in case $shared is true.
	 *
	 * @param string $name name of the service to register another backend for
	 * @param \Closure $closure the closure to be called on service creation
	 * @param bool $shared
	 */
	function registerService($name, \Closure $closure, $shared = true) {
		if (isset($this[$name]))  {
			unset($this[$name]);
		}
		if ($shared) {
			$this[$name] = $closure;
		} else {
			$this[$name] = parent::factory($closure);
		}
	}


}
