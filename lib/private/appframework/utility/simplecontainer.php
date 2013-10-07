<?php

namespace OC\AppFramework\Utility;

// register 3rdparty autoloaders
require_once __DIR__ . '/../../../../3rdparty/Pimple/Pimple.php';

/**
 * Class SimpleContainer
 *
 * SimpleContainer is a simple implementation of IContainer on basis of \Pimple
 */
class SimpleContainer extends \Pimple implements \OCP\IContainer {

	/**
	 * @param string $name name of the service to query for
	 * @return object registered service for the given $name
	 */
	public function query($name) {
		return $this->offsetGet($name);
	}

	function registerParameter($name, $value)
	{
		$this[$name] = $value;
	}

	/**
	 * The given closure is call the first time the given service is queried.
	 * The closure has to return the instance for the given service.
	 * Created instance will be cached in case $shared is true.
	 *
	 * @param string $name name of the service to register another backend for
	 * @param callable $closure the closure to be called on service creation
	 */
	function registerService($name, \Closure $closure, $shared = true)
	{
		if ($shared) {
			$this[$name] = \Pimple::share($closure);
		} else {
			$this[$name] = $closure;
		}
	}
}
