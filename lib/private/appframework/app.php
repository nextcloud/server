<?php

/**
 * ownCloud - App Framework
 *
 * @author Bernhard Posselt
 * @copyright 2012 Bernhard Posselt <dev@bernhard-posselt.com>
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

use \OC_App;
use \OC\AppFramework\DependencyInjection\DIContainer;
use \OCP\AppFramework\QueryException;

/**
 * Entry point for every request in your app. You can consider this as your
 * public static void main() method
 *
 * Handles all the dependency injection, controllers and output flow
 */
class App {


	/**
	 * Turns an app id into a namespace by either reading the appinfo.xml's
	 * namespace tag or uppercasing the appid's first letter
	 * @param string $appId the app id
	 * @param string $topNamespace the namespace which should be prepended to
	 * the transformed app id, defaults to OCA\
	 * @return string the starting namespace for the app
	 */
	public static function buildAppNamespace($appId, $topNamespace='OCA\\') {
		// first try to parse the app's appinfo/info.xml <namespace> tag
		$filePath = OC_App::getAppPath($appId) . '/appinfo/info.xml';
		$loadEntities = libxml_disable_entity_loader(false);
		$xml = @simplexml_load_file($filePath);
		libxml_disable_entity_loader($loadEntities);

		if ($xml) {
			$result = $xml->xpath('/info/namespace');
			if ($result && count($result) > 0) {
				// take first namespace result
				return $topNamespace . trim((string) $result[0]);
			}
		}

		// if the tag is not found, fall back to uppercasing the first letter
		return $topNamespace . ucfirst($appId);
	}


	/**
	 * Shortcut for calling a controller method and printing the result
	 * @param string $controllerName the name of the controller under which it is
	 *                               stored in the DI container
	 * @param string $methodName the method that you want to call
	 * @param DIContainer $container an instance of a pimple container.
	 * @param array $urlParams list of URL parameters (optional)
	 */
	public static function main($controllerName, $methodName, DIContainer $container, array $urlParams = null) {
		if (!is_null($urlParams)) {
			$container['OCP\\IRequest']->setUrlParameters($urlParams);
		} else if (isset($container['urlParams']) && !is_null($container['urlParams'])) {
			$container['OCP\\IRequest']->setUrlParameters($container['urlParams']);
		}
		$appName = $container['AppName'];

		// first try $controllerName then go for \OCA\AppName\Controller\$controllerName
		try {
			$controller = $container->query($controllerName);
		} catch(QueryException $e) {
			$appNameSpace = self::buildAppNamespace($appName);
			$controllerName = $appNameSpace . '\\Controller\\' . $controllerName;
			$controller = $container->query($controllerName);
		}

		// initialize the dispatcher and run all the middleware before the controller
		$dispatcher = $container['Dispatcher'];

		list($httpHeaders, $responseHeaders, $responseCookies, $output) =
			$dispatcher->dispatch($controller, $methodName);

		if(!is_null($httpHeaders)) {
			header($httpHeaders);
		}

		foreach($responseHeaders as $name => $value) {
			header($name . ': ' . $value);
		}

		foreach($responseCookies as $name => $value) {
			$expireDate = null;
			if($value['expireDate'] instanceof \DateTime) {
				$expireDate = $value['expireDate']->getTimestamp();
			}
			setcookie($name, $value['value'], $expireDate, $container->getServer()->getWebRoot(), null, $container->getServer()->getConfig()->getSystemValue('forcessl', false), true);
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
