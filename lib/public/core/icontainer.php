<?php

namespace OCP\Core;

/**
 * Class IContainer
 *
 * IContainer is the basic interface to be used for any internal dependency injection mechanism
 *
 * @package OCP\Core
 */
interface IContainer {

	function query($name);

	function registerParameter($name, $value);

	function registerService($name, \Closure $closure, $shared = true);
}
