<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Authentication\TwoFactorAuth;

use Exception;
use OC\AppFramework\Bootstrap\Coordinator;
use OCP\App\IAppManager;
use OCP\AppFramework\QueryException;
use OCP\Authentication\TwoFactorAuth\IProvider;
use OCP\IUser;
use OCP\Server;

class ProviderLoader {
	public const BACKUP_CODES_APP_ID = 'twofactor_backupcodes';

	public function __construct(
		private IAppManager $appManager,
		private Coordinator $coordinator,
	) {
	}

	/**
	 * Get the list of 2FA providers for the given user
	 *
	 * @return IProvider[]
	 * @throws Exception
	 */
	public function getProviders(IUser $user): array {
		$allApps = $this->appManager->getEnabledAppsForUser($user);
		$providers = [];

		foreach ($allApps as $appId) {
			$info = $this->appManager->getAppInfo($appId);
			if (isset($info['two-factor-providers'])) {
				/** @var string[] $providerClasses */
				$providerClasses = $info['two-factor-providers'];
				foreach ($providerClasses as $class) {
					try {
						$this->loadTwoFactorApp($appId);
						$provider = Server::get($class);
						$providers[$provider->getId()] = $provider;
					} catch (QueryException $exc) {
						// Provider class can not be resolved
						throw new Exception("Could not load two-factor auth provider $class");
					}
				}
			}
		}

		$registeredProviders = $this->coordinator->getRegistrationContext()?->getTwoFactorProviders() ?? [];
		foreach ($registeredProviders as $provider) {
			try {
				$this->loadTwoFactorApp($provider->getAppId());
				$providerInstance = Server::get($provider->getService());
				$providers[$providerInstance->getId()] = $providerInstance;
			} catch (QueryException $exc) {
				// Provider class can not be resolved
				throw new Exception('Could not load two-factor auth provider ' . $provider->getService());
			}
		}

		return $providers;
	}

	/**
	 * Load an app by ID if it has not been loaded yet
	 */
	protected function loadTwoFactorApp(string $appId): void {
		if (!$this->appManager->isAppLoaded($appId)) {
			$this->appManager->loadApp($appId);
		}
	}
}
