<?php

/**
 * SPDX-FileCopyrightText: 2018-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_External\Service;

use OCA\Files_External\AppInfo\Application;
use OCA\Files_External\Config\IConfigHandler;
use OCA\Files_External\ConfigLexicon;
use OCA\Files_External\Lib\Auth\AuthMechanism;
use OCA\Files_External\Lib\Backend\Backend;
use OCA\Files_External\Lib\Config\IAuthMechanismProvider;
use OCA\Files_External\Lib\Config\IBackendProvider;
use OCA\Files_External\Lib\MissingDependency;
use OCP\EventDispatcher\GenericEvent;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IAppConfig;
use OCP\Server;

/**
 * Service class to manage backend definitions
 */
class BackendService {

	/** Visibility constants for VisibilityTrait */
	public const VISIBILITY_NONE = 0;
	public const VISIBILITY_PERSONAL = 1;
	public const VISIBILITY_ADMIN = 2;
	//const VISIBILITY_ALIENS = 4;

	public const VISIBILITY_DEFAULT = 3; // PERSONAL | ADMIN

	/** Priority constants for PriorityTrait */
	public const PRIORITY_DEFAULT = 100;

	private ?bool $userMountingAllowed = null;
	/** @var string[] */
	private array $userMountingBackends = [];

	/** @var Backend[] */
	private $backends = [];

	/** @var IBackendProvider[] */
	private $backendProviders = [];

	/** @var AuthMechanism[] */
	private $authMechanisms = [];

	/** @var IAuthMechanismProvider[] */
	private $authMechanismProviders = [];

	/** @var callable[] */
	private $configHandlerLoaders = [];

	private $configHandlers = [];

	public function __construct(
		protected readonly IAppConfig $appConfig,
	) {
	}

	/**
	 * Register a backend provider
	 *
	 * @since 9.1.0
	 * @param IBackendProvider $provider
	 */
	public function registerBackendProvider(IBackendProvider $provider) {
		$this->backendProviders[] = $provider;
	}

	private function callForRegistrations() {
		static $eventSent = false;
		if (!$eventSent) {
			Server::get(IEventDispatcher::class)->dispatch(
				'OCA\\Files_External::loadAdditionalBackends',
				new GenericEvent()
			);
			$eventSent = true;
		}
	}

	private function loadBackendProviders() {
		$this->callForRegistrations();
		foreach ($this->backendProviders as $provider) {
			$this->registerBackends($provider->getBackends());
		}
		$this->backendProviders = [];
	}

	/**
	 * Register an auth mechanism provider
	 *
	 * @since 9.1.0
	 * @param IAuthMechanismProvider $provider
	 */
	public function registerAuthMechanismProvider(IAuthMechanismProvider $provider) {
		$this->authMechanismProviders[] = $provider;
	}

	private function loadAuthMechanismProviders() {
		$this->callForRegistrations();
		foreach ($this->authMechanismProviders as $provider) {
			$this->registerAuthMechanisms($provider->getAuthMechanisms());
		}
		$this->authMechanismProviders = [];
	}

	/**
	 * Register a backend
	 *
	 * @deprecated 9.1.0 use registerBackendProvider()
	 * @param Backend $backend
	 */
	public function registerBackend(Backend $backend) {
		if (!$this->isAllowedUserBackend($backend)) {
			$backend->removeVisibility(BackendService::VISIBILITY_PERSONAL);
		}
		foreach ($backend->getIdentifierAliases() as $alias) {
			$this->backends[$alias] = $backend;
		}
	}

	/**
	 * @deprecated 9.1.0 use registerBackendProvider()
	 * @param Backend[] $backends
	 */
	public function registerBackends(array $backends) {
		foreach ($backends as $backend) {
			$this->registerBackend($backend);
		}
	}
	/**
	 * Register an authentication mechanism
	 *
	 * @deprecated 9.1.0 use registerAuthMechanismProvider()
	 * @param AuthMechanism $authMech
	 */
	public function registerAuthMechanism(AuthMechanism $authMech) {
		if (!$this->isAllowedAuthMechanism($authMech)) {
			$authMech->removeVisibility(BackendService::VISIBILITY_PERSONAL);
		}
		foreach ($authMech->getIdentifierAliases() as $alias) {
			$this->authMechanisms[$alias] = $authMech;
		}
	}

	/**
	 * @deprecated 9.1.0 use registerAuthMechanismProvider()
	 * @param AuthMechanism[] $mechanisms
	 */
	public function registerAuthMechanisms(array $mechanisms) {
		foreach ($mechanisms as $mechanism) {
			$this->registerAuthMechanism($mechanism);
		}
	}

	/**
	 * Get all backends
	 *
	 * @return Backend[]
	 */
	public function getBackends() {
		$this->loadBackendProviders();
		// only return real identifiers, no aliases
		$backends = [];
		foreach ($this->backends as $backend) {
			$backends[$backend->getIdentifier()] = $backend;
		}
		return $backends;
	}

	/**
	 * Get all available backends
	 *
	 * @return Backend[]
	 */
	public function getAvailableBackends() {
		return array_filter($this->getBackends(), function ($backend) {
			$missing = array_filter($backend->checkDependencies(), fn (MissingDependency $dependency) => !$dependency->isOptional());
			return count($missing) === 0;
		});
	}

	/**
	 * @param string $identifier
	 * @return Backend|null
	 */
	public function getBackend($identifier) {
		$this->loadBackendProviders();
		if (isset($this->backends[$identifier])) {
			return $this->backends[$identifier];
		}
		return null;
	}

	/**
	 * Get all authentication mechanisms
	 *
	 * @return AuthMechanism[]
	 */
	public function getAuthMechanisms() {
		$this->loadAuthMechanismProviders();
		// only return real identifiers, no aliases
		$mechanisms = [];
		foreach ($this->authMechanisms as $mechanism) {
			$mechanisms[$mechanism->getIdentifier()] = $mechanism;
		}
		return $mechanisms;
	}

	/**
	 * Get all authentication mechanisms for schemes
	 *
	 * @param string[] $schemes
	 * @return AuthMechanism[]
	 */
	public function getAuthMechanismsByScheme(array $schemes) {
		return array_filter($this->getAuthMechanisms(), function ($authMech) use ($schemes) {
			return in_array($authMech->getScheme(), $schemes, true);
		});
	}

	/**
	 * @param string $identifier
	 * @return AuthMechanism|null
	 */
	public function getAuthMechanism($identifier) {
		$this->loadAuthMechanismProviders();
		if (isset($this->authMechanisms[$identifier])) {
			return $this->authMechanisms[$identifier];
		}
		return null;
	}

	/**
	 * returns if user mounting is allowed.
	 * also initiate the list of available backends.
	 *
	 * @psalm-assert bool $this->userMountingAllowed
	 */
	public function isUserMountingAllowed(): bool {
		if ($this->userMountingAllowed === null) {
			// Load config values
			$this->userMountingAllowed = $this->appConfig->getValueBool(Application::APP_ID, ConfigLexicon::ALLOW_USER_MOUNTING);
			$this->userMountingBackends = explode(',', $this->appConfig->getValueString(Application::APP_ID, ConfigLexicon::USER_MOUNTING_BACKENDS));

			// if no backend is in the list an empty string is in the array and user mounting is disabled
			if ($this->userMountingBackends === ['']) {
				$this->userMountingAllowed = false;
			}
		}

		return $this->userMountingAllowed;
	}

	/**
	 * Check a backend if a user is allowed to mount it
	 *
	 * @param Backend $backend
	 * @return bool
	 */
	protected function isAllowedUserBackend(Backend $backend): bool {
		return ($this->isUserMountingAllowed() && array_intersect($backend->getIdentifierAliases(), $this->userMountingBackends));
	}

	/**
	 * Check an authentication mechanism if a user is allowed to use it
	 *
	 * @param AuthMechanism $authMechanism
	 * @return bool
	 */
	protected function isAllowedAuthMechanism(AuthMechanism $authMechanism) {
		return true; // not implemented
	}

	/**
	 * registers a configuration handler
	 *
	 * The function of the provided $placeholder is mostly to act a sorting
	 * criteria, so longer placeholders are replaced first. This avoids
	 * "$user" overwriting parts of "$userMail" and "$userLang", for example.
	 * The provided value should not contain the $ prefix, only a-z0-9 are
	 * allowed. Upper case letters are lower cased, the replacement is case-
	 * insensitive.
	 *
	 * The configHandlerLoader should just instantiate the handler on demand.
	 * For now all handlers are instantiated when a mount is loaded, independent
	 * of whether the placeholder is present or not. This may change in future.
	 *
	 * @since 16.0.0
	 */
	public function registerConfigHandler(string $placeholder, callable $configHandlerLoader) {
		$placeholder = trim(strtolower($placeholder));
		if (!(bool)\preg_match('/^[a-z0-9]*$/', $placeholder)) {
			throw new \RuntimeException(sprintf(
				'Invalid placeholder %s, only [a-z0-9] are allowed', $placeholder
			));
		}
		if ($placeholder === '') {
			throw new \RuntimeException('Invalid empty placeholder');
		}
		if (isset($this->configHandlerLoaders[$placeholder]) || isset($this->configHandlers[$placeholder])) {
			throw new \RuntimeException(sprintf('A handler is already registered for %s', $placeholder));
		}
		$this->configHandlerLoaders[$placeholder] = $configHandlerLoader;
	}

	protected function loadConfigHandlers():void {
		$this->callForRegistrations();
		$newLoaded = false;
		foreach ($this->configHandlerLoaders as $placeholder => $loader) {
			$handler = $loader();
			if (!$handler instanceof IConfigHandler) {
				throw new \RuntimeException(sprintf(
					'Handler for %s is not an instance of IConfigHandler', $placeholder
				));
			}
			$this->configHandlers[$placeholder] = $handler;
			$newLoaded = true;
		}
		$this->configHandlerLoaders = [];
		if ($newLoaded) {
			// ensure those with longest placeholders come first,
			// to avoid substring matches
			uksort($this->configHandlers, function ($phA, $phB) {
				return strlen($phB) <=> strlen($phA);
			});
		}
	}

	/**
	 * @since 16.0.0
	 */
	public function getConfigHandlers() {
		$this->loadConfigHandlers();
		return $this->configHandlers;
	}
}
