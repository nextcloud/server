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
use OC\AppFramework\Http\Output;
use OC\AppFramework\Middleware\MiddlewareDispatcher;
use OC\AppFramework\Middleware\Security\CORSMiddleware;
use OC\AppFramework\Middleware\OCSMiddleware;
use OC\AppFramework\Middleware\Security\RateLimitingMiddleware;
use OC\AppFramework\Middleware\Security\SecurityMiddleware;
use OC\AppFramework\Middleware\SessionMiddleware;
use OC\AppFramework\Utility\SimpleContainer;
use OC\Core\Middleware\TwoFactorMiddleware;
use OC\RichObjectStrings\Validator;
use OC\ServerContainer;
use OCP\AppFramework\Http\IOutput;
use OCP\AppFramework\IApi;
use OCP\AppFramework\IAppContainer;
use OCP\AppFramework\QueryException;
use OCP\Files\Folder;
use OCP\Files\IAppData;
use OCP\GlobalScale\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IServerContainer;
use OCP\IUserSession;
use OCP\RichObjectStrings\IValidator;
use OCP\Util;

class DIContainer extends SimpleContainer implements IAppContainer {

	/**
	 * @var array
	 */
	private $middleWares = array();

	/** @var ServerContainer */
	private $server;

	/**
	 * Put your class dependencies in here
	 * @param string $appName the name of the app
	 * @param array $urlParams
	 * @param ServerContainer|null $server
	 */
	public function __construct($appName, $urlParams = array(), ServerContainer $server = null){
		parent::__construct();
		$this['AppName'] = $appName;
		$this['urlParams'] = $urlParams;

		/** @var \OC\ServerContainer $server */
		if ($server === null) {
			$server = \OC::$server;
		}
		$this->server = $server;
		$this->server->registerAppContainer($appName, $this);

		// aliases
		$this->registerAlias('appName', 'AppName');
		$this->registerAlias('webRoot', 'WebRoot');
		$this->registerAlias('userId', 'UserId');

		/**
		 * Core services
		 */
		$this->registerService(IOutput::class, function($c){
			return new Output($this->getServer()->getWebRoot());
		});

		$this->registerService(Folder::class, function() {
			return $this->getServer()->getUserFolder();
		});

		$this->registerService(IAppData::class, function (SimpleContainer $c) {
			return $this->getServer()->getAppDataDir($c->query('AppName'));
		});

		$this->registerService(IL10N::class, function($c) {
			return $this->getServer()->getL10N($c->query('AppName'));
		});

		$this->registerAlias(\OCP\AppFramework\Utility\IControllerMethodReflector::class, \OC\AppFramework\Utility\ControllerMethodReflector::class);
		$this->registerAlias('ControllerMethodReflector', \OCP\AppFramework\Utility\IControllerMethodReflector::class);

		$this->registerService(IRequest::class, function() {
			return $this->getServer()->query(IRequest::class);
		});
		$this->registerAlias('Request', IRequest::class);

		$this->registerAlias(\OCP\AppFramework\Utility\ITimeFactory::class, \OC\AppFramework\Utility\TimeFactory::class);
		$this->registerAlias('TimeFactory', \OCP\AppFramework\Utility\ITimeFactory::class);

		$this->registerAlias(\OC\User\Session::class, \OCP\IUserSession::class);

		$this->registerService(IServerContainer::class, function ($c) {
			return $this->getServer();
		});
		$this->registerAlias('ServerContainer', IServerContainer::class);

		$this->registerService(\OCP\WorkflowEngine\IManager::class, function ($c) {
			return $c->query('OCA\WorkflowEngine\Manager');
		});

		$this->registerService(\OCP\AppFramework\IAppContainer::class, function ($c) {
			return $c;
		});

		// commonly used attributes
		$this->registerService('UserId', function ($c) {
			return $c->query('OCP\\IUserSession')->getSession()->get('user_id');
		});

		$this->registerService('WebRoot', function ($c) {
			return $c->query('ServerContainer')->getWebRoot();
		});

		$this->registerService('fromMailAddress', function() {
			return Util::getDefaultEmailAddress('no-reply');
		});

		$this->registerService('OC_Defaults', function ($c) {
			return $c->getServer()->getThemingDefaults();
		});

		$this->registerService('OCP\Encryption\IManager', function ($c) {
			return $this->getServer()->getEncryptionManager();
		});

		$this->registerService(IConfig::class, function ($c) {
			return $c->query(OC\GlobalScale\Config::class);
		});

		$this->registerService(IValidator::class, function($c) {
			return $c->query(Validator::class);
		});

		$this->registerService(\OC\Security\IdentityProof\Manager::class, function ($c) {
			return new \OC\Security\IdentityProof\Manager(
				$this->getServer()->query(\OC\Files\AppData\Factory::class),
				$this->getServer()->getCrypto(),
				$this->getServer()->getConfig()
			);
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
				$server->getContentSecurityPolicyNonceManager()
			);

		});

		$this->registerService('BruteForceMiddleware', function($c) use ($app) {
			/** @var \OC\Server $server */
			$server = $app->getServer();

			return new OC\AppFramework\Middleware\Security\BruteForceMiddleware(
				$c['ControllerMethodReflector'],
				$server->getBruteForceThrottler(),
				$server->getRequest()
			);
		});

		$this->registerService('RateLimitingMiddleware', function($c) use ($app) {
			/** @var \OC\Server $server */
			$server = $app->getServer();

			return new RateLimitingMiddleware(
				$server->getRequest(),
				$server->getUserSession(),
				$c['ControllerMethodReflector'],
				$c->query(OC\Security\RateLimiting\Limiter::class)
			);
		});

		$this->registerService('CORSMiddleware', function($c) {
			return new CORSMiddleware(
				$c['Request'],
				$c['ControllerMethodReflector'],
				$c->query(IUserSession::class),
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
			$dispatcher->registerMiddleware($c['TwoFactorMiddleware']);
			$dispatcher->registerMiddleware($c['BruteForceMiddleware']);
			$dispatcher->registerMiddleware($c['RateLimitingMiddleware']);

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
	public function getCoreApi()
	{
		return $this->query('API');
	}

	/**
	 * @return \OCP\IServerContainer
	 */
	public function getServer()
	{
		return $this->server;
	}

	/**
	 * @param string $middleWare
	 * @return boolean|null
	 */
	public function registerMiddleWare($middleWare) {
		array_push($this->middleWares, $middleWare);
	}

	/**
	 * used to return the appname of the set application
	 * @return string the name of your application
	 */
	public function getAppName() {
		return $this->query('AppName');
	}

	/**
	 * @deprecated use IUserSession->isLoggedIn()
	 * @return boolean
	 */
	public function isLoggedIn() {
		return \OC::$server->getUserSession()->isLoggedIn();
	}

	/**
	 * @deprecated use IGroupManager->isAdmin($userId)
	 * @return boolean
	 */
	public function isAdminUser() {
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
	public function log($message, $level) {
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

	/**
	 * @param string $name
	 * @return mixed
	 * @throws QueryException if the query could not be resolved
	 */
	public function query($name) {
		try {
			return $this->queryNoFallback($name);
		} catch (QueryException $e) {
			return $this->getServer()->query($name);
		}
	}

	/**
	 * @param string $name
	 * @return mixed
	 * @throws QueryException if the query could not be resolved
	 */
	public function queryNoFallback($name) {
		$name = $this->sanitizeName($name);

		if ($this->offsetExists($name)) {
			return parent::query($name);
		} else {
			if ($this['AppName'] === 'settings' && strpos($name, 'OC\\Settings\\') === 0) {
				return parent::query($name);
			} else if ($this['AppName'] === 'core' && strpos($name, 'OC\\Core\\') === 0) {
				return parent::query($name);
			} else if (strpos($name, \OC\AppFramework\App::buildAppNamespace($this['AppName']) . '\\') === 0) {
				return parent::query($name);
			}
		}

		throw new QueryException('Could not resolve ' . $name . '!' .
			' Class can not be instantiated');
	}
}
