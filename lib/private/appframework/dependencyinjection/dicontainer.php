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

use OC;
use OC\AppFramework\Http;
use OC\AppFramework\Http\Request;
use OC\AppFramework\Http\Dispatcher;
use OC\AppFramework\Core\API;
use OC\AppFramework\Middleware\MiddlewareDispatcher;
use OC\AppFramework\Middleware\Security\SecurityMiddleware;
use OC\AppFramework\Middleware\Security\CORSMiddleware;
use OC\AppFramework\Middleware\SessionMiddleware;
use OC\AppFramework\Utility\SimpleContainer;
use OC\AppFramework\Utility\TimeFactory;
use OC\AppFramework\Utility\ControllerMethodReflector;
use OCP\AppFramework\IApi;
use OCP\AppFramework\QueryException;
use OCP\AppFramework\IAppContainer;
use OCP\AppFramework\Middleware;
use OCP\IServerContainer;


class DIContainer extends SimpleContainer implements IAppContainer {

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

		/**
		 * Core services
		 */
		$this->registerService('OCP\\IAppConfig', function($c) {
			return $this->getServer()->getAppConfig();
		});

		$this->registerService('OCP\\App\\IAppManager', function($c) {
			return $this->getServer()->getAppManager();
		});

		$this->registerService('OCP\\IAvatarManager', function($c) {
			return $this->getServer()->getAvatarManager();
		});

		$this->registerService('OCP\\Activity\\IManager', function($c) {
			return $this->getServer()->getActivityManager();
		});

		$this->registerService('OCP\\ICache', function($c) {
			return $this->getServer()->getCache();
		});

		$this->registerService('OCP\\ICacheFactory', function($c) {
			return $this->getServer()->getMemCacheFactory();
		});

		$this->registerService('OCP\\IConfig', function($c) {
			return $this->getServer()->getConfig();
		});

		$this->registerService('OCP\\Contacts\\IManager', function($c) {
			return $this->getServer()->getContactsManager();
		});

		$this->registerService('OCP\\IDateTimeZone', function($c) {
			return $this->getServer()->getDateTimeZone();
		});

		$this->registerService('OCP\\IDb', function($c) {
			return $this->getServer()->getDb();
		});

		$this->registerService('OCP\\IDBConnection', function($c) {
			return $this->getServer()->getDatabaseConnection();
		});

		$this->registerService('OCP\\Diagnostics\\IEventLogger', function($c) {
			return $this->getServer()->getEventLogger();
		});

		$this->registerService('OCP\\Diagnostics\\IQueryLogger', function($c) {
			return $this->getServer()->getQueryLogger();
		});

		$this->registerService('OCP\\Files\\Config\\IMountProviderCollection', function($c) {
			return $this->getServer()->getMountProviderCollection();
		});

		$this->registerService('OCP\\Files\\IRootFolder', function($c) {
			return $this->getServer()->getRootFolder();
		});

		$this->registerService('OCP\\IGroupManager', function($c) {
			return $this->getServer()->getGroupManager();
		});

		$this->registerService('OCP\\IL10N', function($c) {
			return $this->getServer()->getL10N($c->query('AppName'));
		});

		$this->registerService('OCP\\ILogger', function($c) {
			return $this->getServer()->getLogger();
		});

		$this->registerService('OCP\\BackgroundJob\\IJobList', function($c) {
			return $this->getServer()->getJobList();
		});

		$this->registerService('OCP\\AppFramework\\Utility\\IControllerMethodReflector', function($c) {
			return $c->query('ControllerMethodReflector');
		});

		$this->registerService('OCP\\INavigationManager', function($c) {
			return $this->getServer()->getNavigationManager();
		});

		$this->registerService('OCP\\IPreview', function($c) {
			return $this->getServer()->getPreviewManager();
		});

		$this->registerService('OCP\\IRequest', function($c) {
			return $c->query('Request');
		});

		$this->registerService('OCP\\ITagManager', function($c) {
			return $this->getServer()->getTagManager();
		});

		$this->registerService('OCP\\ITempManager', function($c) {
			return $this->getServer()->getTempManager();
		});

		$this->registerService('OCP\\AppFramework\\Utility\\ITimeFactory', function($c) {
			return $c->query('TimeFactory');
		});

		$this->registerService('OCP\\Route\\IRouter', function($c) {
			return $this->getServer()->getRouter();
		});

		$this->registerService('OCP\\ISearch', function($c) {
			return $this->getServer()->getSearch();
		});

		$this->registerService('OCP\\ISearch', function($c) {
			return $this->getServer()->getSearch();
		});

		$this->registerService('OCP\\Security\\ICrypto', function($c) {
			return $this->getServer()->getCrypto();
		});

		$this->registerService('OCP\\Security\\IHasher', function($c) {
			return $this->getServer()->getHasher();
		});

		$this->registerService('OCP\\Security\\ISecureRandom', function($c) {
			return $this->getServer()->getSecureRandom();
		});

		$this->registerService('OCP\\IURLGenerator', function($c) {
			return $this->getServer()->getURLGenerator();
		});

		$this->registerService('OCP\\IUserManager', function($c) {
			return $this->getServer()->getUserManager();
		});

		$this->registerService('OCP\\IUserSession', function($c) {
			return $this->getServer()->getUserSession();
		});

		$this->registerService('ServerContainer', function ($c) {
			return $this->getServer();
		});

		// commonly used attributes
		$this->registerService('UserId', function ($c) {
			return $c->query('OCP\\IUserSession')->getSession()->get('user_id');
		});

		$this->registerService('WebRoot', function ($c) {
			return $c->query('ServerContainer')->getWebRoot();
		});


		/**
		 * App Framework APIs
		 */
		$this->registerService('API', function($c){
			$c->query('OCP\\ILogger')->debug(
				'Accessing the API class is deprecated! Use the appropriate ' .
				'services instead!'
			);
			return new API($c['AppName']);
		});

		$this->registerService('Request', function($c) {
			/** @var $c SimpleContainer */
			/** @var $server SimpleContainer */
			$server = $c->query('ServerContainer');
			/** @var $server IServerContainer */
			return $server->getRequest();
		});

		$this->registerService('Protocol', function($c){
			if(isset($_SERVER['SERVER_PROTOCOL'])) {
				return new Http($_SERVER, $_SERVER['SERVER_PROTOCOL']);
			} else {
				return new Http($_SERVER);
			}
		});

		$this->registerService('Dispatcher', function($c) {
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
		$this->registerService('SecurityMiddleware', function($c) use ($app){
			return new SecurityMiddleware(
				$c['Request'],
				$c['ControllerMethodReflector'],
				$app->getServer()->getNavigationManager(),
				$app->getServer()->getURLGenerator(),
				$app->getServer()->getLogger(),
				$c['AppName'],
				$app->isLoggedIn(),
				$app->isAdminUser()
			);
		});

		$this->registerService('CORSMiddleware', function($c) {
			return new CORSMiddleware(
				$c['Request'],
				$c['ControllerMethodReflector']
			);
		});

		$this->registerService('SessionMiddleware', function($c) use ($app) {
			return new SessionMiddleware(
				$c['Request'],
				$c['ControllerMethodReflector'],
				$app->getServer()->getSession()
			);
		});

		$middleWares = &$this->middleWares;
		$this->registerService('MiddlewareDispatcher', function($c) use (&$middleWares) {
			$dispatcher = new MiddlewareDispatcher();
			$dispatcher->registerMiddleware($c['SecurityMiddleware']);
			$dispatcher->registerMiddleware($c['CORSMiddleware']);

			foreach($middleWares as $middleWare) {
				$dispatcher->registerMiddleware($c[$middleWare]);
			}

			$dispatcher->registerMiddleware($c['SessionMiddleware']);
			return $dispatcher;
		});


		/**
		 * Utilities
		 */
		$this->registerService('TimeFactory', function($c){
			return new TimeFactory();
		});

		$this->registerService('ControllerMethodReflector', function($c) {
			return new ControllerMethodReflector();
		});

	}


	/**
	 * @deprecated implements only deprecated methods
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
		return OC::$server;
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
	 * @deprecated use IUserSession->isLoggedIn()
	 * @return boolean
	 */
	function isLoggedIn() {
		return \OC_User::isLoggedIn();
	}

	/**
	 * @deprecated use IGroupManager->isAdmin($userId)
	 * @return boolean
	 */
	function isAdminUser() {
		$uid = $this->getUserId();
		return \OC_User::isAdminUser($uid);
	}

	private function getUserId() {
		return $this->getServer()->getSession()->get('user_id');
	}

	/**
	 * @deprecated use the ILogger instead
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
