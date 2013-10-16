<?php

/**
 * ownCloud - App Framework
 *
 * @author Bernhard Posselt
 * @copyright 2012 Bernhard Posselt nukeawhale@gmail.com
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


namespace OC\AppFramework;

use OC\AppFramework\DependencyInjection\DIContainer;
use OCP\AppFramework\IAppContainer;


/**
 * Entry point for every request in your app. You can consider this as your
 * public static void main() method
 *
 * Handles all the dependency injection, controllers and output flow
 */
class App {


	/**
	 * Shortcut for calling a controller method and printing the result
	 * @param string $controllerName the name of the controller under which it is
	 *                               stored in the DI container
	 * @param string $methodName the method that you want to call
	 * @param DIContainer $container an instance of a pimple container.
	 */
	public static function main($controllerName, $methodName, IAppContainer $container) {
		$controller = $container[$controllerName];

		// initialize the dispatcher and run all the middleware before the controller
		$dispatcher = $container['Dispatcher'];

		list($httpHeaders, $responseHeaders, $output) =
			$dispatcher->dispatch($controller, $methodName);

		if(!is_null($httpHeaders)) {
			header($httpHeaders);
		}

		foreach($responseHeaders as $name => $value) {
			header($name . ': ' . $value);
		}

		if(!is_null($output)) {
			header('Content-Length: ' . strlen($output));
			print($output);
		}

	}

	/**
	 * Shortcut for calling a controller method and printing the result.
	 * Similar to App:main except that no headers will be sent.
	 * This should be used for example when registering sections via
	 * \OC\AppFramework\Core\API::registerAdmin()
	 *
	 * @param string $controllerName the name of the controller under which it is
	 *                               stored in the DI container
	 * @param string $methodName the method that you want to call
	 * @param array $urlParams an array with variables extracted from the routes
	 * @param DIContainer $container an instance of a pimple container.
	 */
	public static function part($controllerName, $methodName, array $urlParams,
								DIContainer $container){

		$container['urlParams'] = $urlParams;
		$controller = $container[$controllerName];

		$dispatcher = $container['Dispatcher'];

		list(, , $output) =  $dispatcher->dispatch($controller, $methodName);
		return $output;
	}

}
