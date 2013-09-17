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


namespace OC\AppFramework\DependencyInjection;

use OC\AppFramework\Http\Http;
use OC\AppFramework\Http\Request;
use OC\AppFramework\Http\Dispatcher;
use OC\AppFramework\Core\API;
use OC\AppFramework\Middleware\MiddlewareDispatcher;
use OC\AppFramework\Middleware\Security\SecurityMiddleware;
use OC\AppFramework\Utility\SimpleContainer;
use OC\AppFramework\Utility\TimeFactory;
use OCP\AppFramework\IApi;
use OCP\AppFramework\IAppContainer;


class DIContainer extends SimpleContainer implements IAppContainer{


	/**
	 * Put your class dependencies in here
	 * @param string $appName the name of the app
	 */
	public function __construct($appName){

		$this['AppName'] = $appName;

		$this->registerParameter('ServerContainer', \OC::$server);

		$this['API'] = $this->share(function($c){
			return new API($c['AppName']);
		});

		/**
		 * Http
		 */
		$this['Request'] = $this->share(function($c) {

			$params = array();

			// we json decode the body only in case of content type json
			if (isset($_SERVER['CONTENT_TYPE']) && stripos($_SERVER['CONTENT_TYPE'],'json') === true ) {
				$params = json_decode(file_get_contents('php://input'), true);
				$params = is_array($params) ? $params: array();
			}

			return new Request(
				array(
					'get' => $_GET,
					'post' => $_POST,
					'files' => $_FILES,
					'server' => $_SERVER,
					'env' => $_ENV,
					'session' => $_SESSION,
					'cookies' => $_COOKIE,
					'method' => (isset($_SERVER) && isset($_SERVER['REQUEST_METHOD']))
							? $_SERVER['REQUEST_METHOD']
							: null,
					'params' => $params,
					'urlParams' => $c['urlParams']
				)
			);
		});

		$this['Protocol'] = $this->share(function($c){
			if(isset($_SERVER['SERVER_PROTOCOL'])) {
				return new Http($_SERVER, $_SERVER['SERVER_PROTOCOL']);
			} else {
				return new Http($_SERVER);
			}
		});

		$this['Dispatcher'] = $this->share(function($c) {
			return new Dispatcher($c['Protocol'], $c['MiddlewareDispatcher']);
		});


		/**
		 * Middleware
		 */
		$this['SecurityMiddleware'] = $this->share(function($c){
			return new SecurityMiddleware($c['API'], $c['Request']);
		});

		$this['MiddlewareDispatcher'] = $this->share(function($c){
			$dispatcher = new MiddlewareDispatcher();
			$dispatcher->registerMiddleware($c['SecurityMiddleware']);

			return $dispatcher;
		});


		/**
		 * Utilities
		 */
		$this['TimeFactory'] = $this->share(function($c){
			return new TimeFactory();
		});


	}


	/**
	 * @return IApi
	 */
	function getCoreApi()
	{
		return $this->query('API');
	}

	/**
	 * @return \OCP\IServerContainer
	 */
	function getServer()
	{
		return $this->query('ServerContainer');
	}
}
