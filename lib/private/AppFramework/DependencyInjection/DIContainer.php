<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bernhard Posselt <dev@bernhard-posselt.com>
 * @author Christoph Wurst <christoph@owncloud.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Thomas Tanghus <thomas@tanghus.net>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */


namespace OC\AppFramework\DependencyInjection;

use OC;
use OC\AppFramework\Core\API;
use OC\AppFramework\Http;
use OC\AppFramework\Http\Dispatcher;
use OC\AppFramework\Middleware\MiddlewareDispatcher;
use OC\AppFramework\Middleware\Security\CORSMiddleware;
use OC\AppFramework\Middleware\OCSMiddleware;
use OC\AppFramework\Middleware\Security\SecurityMiddleware;
use OC\AppFramework\Middleware\SessionMiddleware;
use OC\AppFramework\Utility\SimpleContainer;
use OC\Core\Middleware\TwoFactorMiddleware;
use OCP\AppFramework\IApi;
use OCP\AppFramework\IAppContainer;

class DIContainer extends SimpleContainer implements IAppContainer {

	/**
	 * @var array
	 */
	private $middleWares = array();

	/** @var BasicContainer */
	private $basicContainer;

	/**
	 * Put your class dependencies in here
	 * @param string $appName the name of the app
	 * @param array $urlParams
	 * @param BasicContainer $basicContainer
	 */
	public function __construct($appName, $urlParams = array(), BasicContainer $basicContainer){
		parent::__construct();

		$this->basicContainer = $basicContainer;

		$this['AppName'] = $appName;
		$this['urlParams'] = $urlParams;

		/** @var \OC\ServerContainer $server */
		$server = $this->getServer();
		$server->registerAppContainer($appName, $this);

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

		$this->registerService('Protocol', function($c){
			/** @var \OC\Server $server */
			$server = $c->query('ServerContainer');
			$protocol = $server->getRequest()->getHttpProtocol();
			return new Http($_SERVER, $protocol);
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
		 * App Framework default arguments
		 */
		$this->registerParameter('corsMethods', 'PUT, POST, GET, DELETE, PATCH');
		$this->registerParameter('corsAllowedHeaders', 'Authorization, Content-Type, Accept');
		$this->registerParameter('corsMaxAge', 1728000);

		/**
		 * Middleware
		 */
		$app = $this;
		$this->registerService('SecurityMiddleware', function($c) use ($app){
			/** @var \OC\Server $server */
			$server = $app->getServer();

			return new SecurityMiddleware(
				$c['Request'],
				$c['ControllerMethodReflector'],
				$server->getNavigationManager(),
				$server->getURLGenerator(),
				$server->getLogger(),
				$server->getSession(),
				$c['AppName'],
				$app->isLoggedIn(),
				$app->isAdminUser(),
				$server->getContentSecurityPolicyManager(),
				$server->getCsrfTokenManager(),
				$server->getContentSecurityPolicyNonceManager(),
				$server->getBruteForceThrottler()
			);

		});

		$this->registerService('CORSMiddleware', function($c) {
			return new CORSMiddleware(
				$c['Request'],
				$c['ControllerMethodReflector'],
				$c['OCP\IUserSession'],
				$c->getServer()->getBruteForceThrottler()
			);
		});

		$this->registerService('SessionMiddleware', function($c) use ($app) {
			return new SessionMiddleware(
				$c['Request'],
				$c['ControllerMethodReflector'],
				$app->getServer()->getSession()
			);
		});

		$this->registerService('TwoFactorMiddleware', function (SimpleContainer $c) use ($app) {
			$twoFactorManager = $c->getServer()->getTwoFactorAuthManager();
			$userSession = $app->getServer()->getUserSession();
			$session = $app->getServer()->getSession();
			$urlGenerator = $app->getServer()->getURLGenerator();
			$reflector = $c['ControllerMethodReflector'];
			$request = $app->getServer()->getRequest();
			return new TwoFactorMiddleware($twoFactorManager, $userSession, $session, $urlGenerator, $reflector, $request);
		});

		$this->registerService('OCSMiddleware', function (SimpleContainer $c) {
			return new OCSMiddleware(
				$c['Request']
			);
		});

		$middleWares = &$this->middleWares;
		$this->registerService('MiddlewareDispatcher', function($c) use (&$middleWares) {
			$dispatcher = new MiddlewareDispatcher();
			$dispatcher->registerMiddleware($c['CORSMiddleware']);
			$dispatcher->registerMiddleware($c['OCSMiddleware']);
			$dispatcher->registerMiddleware($c['SecurityMiddleware']);
			$dispatcher->registerMiddleWare($c['TwoFactorMiddleware']);

			foreach($middleWares as $middleWare) {
				$dispatcher->registerMiddleware($c[$middleWare]);
			}

			$dispatcher->registerMiddleware($c['SessionMiddleware']);
			return $dispatcher;
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
		return \OC::$server->getUserSession()->isLoggedIn();
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

	/**
	 * Register a capability
	 *
	 * @param string $serviceName e.g. 'OCA\Files\Capabilities'
	 */
	public function registerCapability($serviceName) {
		$this->query('OC\CapabilitiesManager')->registerCapability(function() use ($serviceName) {
			return $this->query($serviceName);
		});
	}

	public function offsetExists($id) {
		return parent::offsetExists($id) || $this->basicContainer->offsetExists($id);
	}

	public function offsetGet($id) {
		if (!parent::offsetExists($id) && $this->basicContainer->offsetExists($id)) {
			$this->offsetSet($id, $this->basicContainer->raw($id));
		}
		return parent::offsetGet($id);
	}
}
