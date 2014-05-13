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


namespace OC\AppFramework\DependencyInjection;

use OC\AppFramework\Http;
use OC\AppFramework\Http\Request;
use OC\AppFramework\Http\Dispatcher;
use OC\AppFramework\Core\API;
use OC\AppFramework\Middleware\MiddlewareDispatcher;
use OC\AppFramework\Middleware\Security\SecurityMiddleware;
use OC\AppFramework\Middleware\Security\CORSMiddleware;
use OC\AppFramework\Utility\SimpleContainer;
use OC\AppFramework\Utility\TimeFactory;
use OC\AppFramework\Utility\ControllerMethodReflector;
use OCP\AppFramework\IApi;
use OCP\AppFramework\IAppContainer;
use OCP\AppFramework\Middleware;
use OCP\IServerContainer;


class DIContainer extends SimpleContainer implements IAppContainer{

	/**
	 * @var array
	 */
	private $middleWares = array();

	/**
	 * Put your class dependencies in here
	 * @param string $appName the name of the app
	 */
	public function __construct($appName, $urlParams = array()){

		$this['AppName'] = $appName;
		$this['urlParams'] = $urlParams;

		$this->registerParameter('ServerContainer', \OC::$server);

		$this['API'] = $this->share(function($c){
			return new API($c['AppName']);
		});

		/**
		 * Http
		 */
		$this['Request'] = $this->share(function($c) {
			/** @var $c SimpleContainer */
			/** @var $server IServerContainer */
			$server = $c->query('ServerContainer');
			$server->registerParameter('urlParams', $c['urlParams']);
			return $server->getRequest();
		});

		$this['Protocol'] = $this->share(function($c){
			if(isset($_SERVER['SERVER_PROTOCOL'])) {
				return new Http($_SERVER, $_SERVER['SERVER_PROTOCOL']);
			} else {
				return new Http($_SERVER);
			}
		});

		$this['Dispatcher'] = $this->share(function($c) {
			return new Dispatcher(
				$c['Protocol'], 
				$c['MiddlewareDispatcher'], 
				$c['ControllerMethodReflector'],
				$c['Request']
			);
		});


		/**
		 * Middleware
		 */
		$app = $this;
		$this['SecurityMiddleware'] = $this->share(function($c) use ($app){
			return new SecurityMiddleware(
				$app, 
				$c['Request'], 
				$c['ControllerMethodReflector']
			);
		});

		$this['CORSMiddleware'] = $this->share(function($c) {
			return new CORSMiddleware(
				$c['Request'],
				$c['ControllerMethodReflector']
			);
		});

		$middleWares = &$this->middleWares;
		$this['MiddlewareDispatcher'] = $this->share(function($c) use (&$middleWares) {
			$dispatcher = new MiddlewareDispatcher();
			$dispatcher->registerMiddleware($c['SecurityMiddleware']);
			$dispatcher->registerMiddleware($c['CORSMiddleware']);

			foreach($middleWares as $middleWare) {
				$dispatcher->registerMiddleware($c[$middleWare]);
			}

			return $dispatcher;
		});


		/**
		 * Utilities
		 */
		$this['TimeFactory'] = $this->share(function($c){
			return new TimeFactory();
		});

		$this['ControllerMethodReflector'] = $this->share(function($c) {
			return new ControllerMethodReflector();
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

	/**
	 * @param string $middleWare
	 * @return boolean|null
	 */
	function registerMiddleWare($middleWare) {
		array_push($this->middleWares, $middleWare);
	}

	/**
	 * used to return the appname of the set application
	 * @return string the name of your application
	 */
	function getAppName() {
		return $this->query('AppName');
	}

	/**
	 * @return boolean
	 */
	function isLoggedIn() {
		return \OC_User::isLoggedIn();
	}

	/**
	 * @return boolean
	 */
	function isAdminUser() {
		$uid = $this->getUserId();
		return \OC_User::isAdminUser($uid);
	}

	private function getUserId() {
		return \OC::$session->get('user_id');
	}

	/**
	 * @param string $message
	 * @param string $level
	 * @return mixed
	 */
	function log($message, $level) {
		switch($level){
			case 'debug':
				$level = \OCP\Util::DEBUG;
				break;
			case 'info':
				$level = \OCP\Util::INFO;
				break;
			case 'warn':
				$level = \OCP\Util::WARN;
				break;
			case 'fatal':
				$level = \OCP\Util::FATAL;
				break;
			default:
				$level = \OCP\Util::ERROR;
				break;
		}
		\OCP\Util::writeLog($this->getAppName(), $message, $level);
	}
}
