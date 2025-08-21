<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\AppFramework\DependencyInjection;

use OC\AppFramework\Http;
use OC\AppFramework\Http\Dispatcher;
use OC\AppFramework\Http\Output;
use OC\AppFramework\Middleware\AdditionalScriptsMiddleware;
use OC\AppFramework\Middleware\CompressionMiddleware;
use OC\AppFramework\Middleware\FlowV2EphemeralSessionsMiddleware;
use OC\AppFramework\Middleware\MiddlewareDispatcher;
use OC\AppFramework\Middleware\NotModifiedMiddleware;
use OC\AppFramework\Middleware\OCSMiddleware;
use OC\AppFramework\Middleware\PublicShare\PublicShareMiddleware;
use OC\AppFramework\Middleware\Security\BruteForceMiddleware;
use OC\AppFramework\Middleware\Security\CORSMiddleware;
use OC\AppFramework\Middleware\Security\CSPMiddleware;
use OC\AppFramework\Middleware\Security\FeaturePolicyMiddleware;
use OC\AppFramework\Middleware\Security\PasswordConfirmationMiddleware;
use OC\AppFramework\Middleware\Security\RateLimitingMiddleware;
use OC\AppFramework\Middleware\Security\ReloadExecutionMiddleware;
use OC\AppFramework\Middleware\Security\SameSiteCookieMiddleware;
use OC\AppFramework\Middleware\Security\SecurityMiddleware;
use OC\AppFramework\Middleware\SessionMiddleware;
use OC\AppFramework\ScopedPsrLogger;
use OC\AppFramework\Utility\SimpleContainer;
use OC\Core\Middleware\TwoFactorMiddleware;
use OC\Diagnostics\EventLogger;
use OC\Log\PsrLoggerAdapter;
use OC\ServerContainer;
use OC\Settings\AuthorizedGroupMapper;
use OCA\WorkflowEngine\Manager;
use OCP\App\IAppManager;
use OCP\AppFramework\Http\IOutput;
use OCP\AppFramework\IAppContainer;
use OCP\AppFramework\QueryException;
use OCP\AppFramework\Services\IAppConfig;
use OCP\AppFramework\Services\IInitialState;
use OCP\AppFramework\Utility\IControllerMethodReflector;
use OCP\Files\AppData\IAppDataFactory;
use OCP\Files\Folder;
use OCP\Files\IAppData;
use OCP\Group\ISubAdmin;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\INavigationManager;
use OCP\IRequest;
use OCP\IServerContainer;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCP\Security\Ip\IRemoteAddress;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class DIContainer extends SimpleContainer implements IAppContainer {
	protected string $appName;
	private array $middleWares = [];
	private ServerContainer $server;

	public function __construct(string $appName, array $urlParams = [], ?ServerContainer $server = null) {
		parent::__construct();
		$this->appName = $appName;
		$this->registerParameter('appName', $appName);
		$this->registerParameter('urlParams', $urlParams);

		/** @deprecated 32.0.0 */
		$this->registerDeprecatedAlias('Request', IRequest::class);

		if ($server === null) {
			$server = \OC::$server;
		}
		$this->server = $server;
		$this->server->registerAppContainer($appName, $this);

		// aliases
		/** @deprecated 26.0.0 inject $appName */
		$this->registerDeprecatedAlias('AppName', 'appName');
		/** @deprecated 26.0.0 inject $webRoot*/
		$this->registerDeprecatedAlias('WebRoot', 'webRoot');
		/** @deprecated 26.0.0 inject $userId */
		$this->registerDeprecatedAlias('UserId', 'userId');

		/**
		 * Core services
		 */
		/* Cannot be an alias because Output is not in OCA */
		$this->registerService(IOutput::class, fn (ContainerInterface $c): IOutput => new Output($c->get('webRoot')));

		$this->registerService(Folder::class, function () {
			return $this->getServer()->getUserFolder();
		});

		$this->registerService(IAppData::class, function (ContainerInterface $c): IAppData {
			return $c->get(IAppDataFactory::class)->get($c->get('appName'));
		});

		$this->registerService(IL10N::class, function (ContainerInterface $c) {
			return $this->getServer()->getL10N($c->get('appName'));
		});

		// Log wrappers
		$this->registerService(LoggerInterface::class, function (ContainerInterface $c) {
			/* Cannot be an alias because it uses LoggerInterface so it would infinite loop */
			return new ScopedPsrLogger(
				$c->get(PsrLoggerAdapter::class),
				$c->get('appName')
			);
		});

		$this->registerService(IServerContainer::class, function () {
			return $this->getServer();
		});
		/** @deprecated 32.0.0 */
		$this->registerDeprecatedAlias('ServerContainer', IServerContainer::class);

		$this->registerAlias(\OCP\WorkflowEngine\IManager::class, Manager::class);

		$this->registerService(ContainerInterface::class, fn (ContainerInterface $c) => $c);
		$this->registerDeprecatedAlias(IAppContainer::class, ContainerInterface::class);

		// commonly used attributes
		$this->registerService('userId', function (ContainerInterface $c): ?string {
			return $c->get(ISession::class)->get('user_id');
		});

		$this->registerService('webRoot', function (ContainerInterface $c): string {
			return $c->get(IServerContainer::class)->getWebRoot();
		});

		$this->registerService('OC_Defaults', function (ContainerInterface $c): object {
			return $c->get(IServerContainer::class)->get('ThemingDefaults');
		});

		/** @deprecated 32.0.0 */
		$this->registerDeprecatedAlias('Protocol', Http::class);
		$this->registerService(Http::class, function (ContainerInterface $c) {
			$protocol = $c->get(IRequest::class)->getHttpProtocol();
			return new Http($_SERVER, $protocol);
		});

		/** @deprecated 32.0.0 */
		$this->registerDeprecatedAlias('Dispatcher', Dispatcher::class);
		$this->registerService(Dispatcher::class, function (ContainerInterface $c) {
			return new Dispatcher(
				$c->get(Http::class),
				$c->get(MiddlewareDispatcher::class),
				$c->get(IControllerMethodReflector::class),
				$c->get(IRequest::class),
				$c->get(IConfig::class),
				$c->get(IDBConnection::class),
				$c->get(LoggerInterface::class),
				$c->get(EventLogger::class),
				$c,
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
		/** @deprecated 32.0.0 */
		$this->registerDeprecatedAlias('MiddlewareDispatcher', MiddlewareDispatcher::class);
		$this->registerService(MiddlewareDispatcher::class, function (ContainerInterface $c) {
			$server = $this->getServer();

			$dispatcher = new MiddlewareDispatcher();

			$dispatcher->registerMiddleware($c->get(CompressionMiddleware::class));
			$dispatcher->registerMiddleware($c->get(NotModifiedMiddleware::class));
			$dispatcher->registerMiddleware($c->get(ReloadExecutionMiddleware::class));
			$dispatcher->registerMiddleware($c->get(SameSiteCookieMiddleware::class));
			$dispatcher->registerMiddleware($c->get(CORSMiddleware::class));
			$dispatcher->registerMiddleware($c->get(OCSMiddleware::class));

			$dispatcher->registerMiddleware($c->get(FlowV2EphemeralSessionsMiddleware::class));

			$securityMiddleware = new SecurityMiddleware(
				$c->get(IRequest::class),
				$c->get(IControllerMethodReflector::class),
				$c->get(INavigationManager::class),
				$c->get(IURLGenerator::class),
				$c->get(LoggerInterface::class),
				$c->get('appName'),
				$server->getUserSession()->isLoggedIn(),
				$c->get(IGroupManager::class),
				$c->get(ISubAdmin::class),
				$c->get(IAppManager::class),
				$server->getL10N('lib'),
				$c->get(AuthorizedGroupMapper::class),
				$c->get(IUserSession::class),
				$c->get(IRemoteAddress::class),
			);
			$dispatcher->registerMiddleware($securityMiddleware);
			$dispatcher->registerMiddleware($c->get(CSPMiddleware::class));
			$dispatcher->registerMiddleware($c->get(FeaturePolicyMiddleware::class));
			$dispatcher->registerMiddleware($c->get(PasswordConfirmationMiddleware::class));
			$dispatcher->registerMiddleware($c->get(TwoFactorMiddleware::class));
			$dispatcher->registerMiddleware($c->get(BruteForceMiddleware::class));
			$dispatcher->registerMiddleware($c->get(RateLimitingMiddleware::class));
			$dispatcher->registerMiddleware($c->get(PublicShareMiddleware::class));
			$dispatcher->registerMiddleware($c->get(AdditionalScriptsMiddleware::class));

			$coordinator = $c->get(\OC\AppFramework\Bootstrap\Coordinator::class);
			$registrationContext = $coordinator->getRegistrationContext();
			if ($registrationContext !== null) {
				$appId = $this->get('appName');
				foreach ($registrationContext->getMiddlewareRegistrations() as $middlewareRegistration) {
					if ($middlewareRegistration->getAppId() === $appId
						|| $middlewareRegistration->isGlobal()) {
						$dispatcher->registerMiddleware($c->get($middlewareRegistration->getService()));
					}
				}
			}
			foreach ($this->middleWares as $middleWare) {
				$dispatcher->registerMiddleware($c->get($middleWare));
			}

			$dispatcher->registerMiddleware($c->get(SessionMiddleware::class));
			return $dispatcher;
		});

		$this->registerAlias(IAppConfig::class, \OC\AppFramework\Services\AppConfig::class);
		$this->registerAlias(IInitialState::class, \OC\AppFramework\Services\InitialState::class);
	}

	/**
	 * @return \OCP\IServerContainer
	 */
	public function getServer() {
		return $this->server;
	}

	/**
	 * @param string $middleWare
	 */
	public function registerMiddleWare($middleWare): bool {
		if (in_array($middleWare, $this->middleWares, true) !== false) {
			return false;
		}
		$this->middleWares[] = $middleWare;
		return true;
	}

	/**
	 * used to return the appname of the set application
	 * @return string the name of your application
	 */
	public function getAppName() {
		return $this->query('appName');
	}

	/**
	 * @deprecated 12.0.0 use IUserSession->isLoggedIn()
	 * @return boolean
	 */
	public function isLoggedIn() {
		return \OC::$server->getUserSession()->isLoggedIn();
	}

	/**
	 * @deprecated 12.0.0 use IGroupManager->isAdmin($userId)
	 * @return boolean
	 */
	public function isAdminUser() {
		$uid = $this->getUserId();
		return \OC_User::isAdminUser($uid);
	}

	private function getUserId(): string {
		return $this->getServer()->getSession()->get('user_id');
	}

	/**
	 * Register a capability
	 *
	 * @param string $serviceName e.g. 'OCA\Files\Capabilities'
	 */
	public function registerCapability($serviceName) {
		$this->query('OC\CapabilitiesManager')->registerCapability(function () use ($serviceName) {
			return $this->query($serviceName);
		});
	}

	public function has($id): bool {
		if (parent::has($id)) {
			return true;
		}

		if ($this->server->has($id, true)) {
			return true;
		}

		return false;
	}

	public function query(string $name, bool $autoload = true) {
		if ($name === 'AppName' || $name === 'appName') {
			return $this->appName;
		}

		$isServerClass = str_starts_with($name, 'OCP\\') || str_starts_with($name, 'OC\\');
		if ($isServerClass && !$this->has($name)) {
			return $this->getServer()->query($name, $autoload);
		}

		try {
			return $this->queryNoFallback($name);
		} catch (QueryException $firstException) {
			try {
				return $this->getServer()->query($name, $autoload);
			} catch (QueryException $secondException) {
				if ($firstException->getCode() === 1) {
					throw $secondException;
				}
				throw $firstException;
			}
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
		} elseif ($this->appName === 'settings' && str_starts_with($name, 'OC\\Settings\\')) {
			return parent::query($name);
		} elseif ($this->appName === 'core' && str_starts_with($name, 'OC\\Core\\')) {
			return parent::query($name);
		} elseif (str_starts_with($name, \OC\AppFramework\App::buildAppNamespace($this->appName) . '\\')) {
			return parent::query($name);
		} elseif (
			str_starts_with($name, 'OC\\AppFramework\\Services\\')
			|| str_starts_with($name, 'OC\\AppFramework\\Middleware\\')
		) {
			/* AppFramework services are scoped to the application */
			return parent::query($name);
		}

		throw new QueryException('Could not resolve ' . $name . '!'
			. ' Class can not be instantiated', 1);
	}
}
