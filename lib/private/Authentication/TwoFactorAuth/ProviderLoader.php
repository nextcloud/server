<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Authentication\TwoFactorAuth;

use Exception;
use OC;
use OC_App;
use OCP\App\IAppManager;
use OCP\AppFramework\QueryException;
use OCP\Authentication\TwoFactorAuth\IProvider;
use OCP\IUser;

class ProviderLoader {
	public const BACKUP_CODES_APP_ID = 'twofactor_backupcodes';

	/** @var IAppManager */
	private $appManager;

	/** @var OC\AppFramework\Bootstrap\Coordinator */
	private $coordinator;

	public function __construct(IAppManager $appManager, OC\AppFramework\Bootstrap\Coordinator $coordinator) {
		$this->appManager = $appManager;
		$this->coordinator = $coordinator;
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
						$provider = \OCP\Server::get($class);
						$providers[$provider->getId()] = $provider;
					} catch (QueryException $exc) {
						// Provider class can not be resolved
						throw new Exception("Could not load two-factor auth provider $class");
					}
				}
			}
		}

		$registeredProviders = $this->coordinator->getRegistrationContext()->getTwoFactorProviders();
		foreach ($registeredProviders as $provider) {
			try {
				$this->loadTwoFactorApp($provider->getAppId());
				$provider = \OCP\Server::get($provider->getService());
				$providers[$provider->getId()] = $provider;
			} catch (QueryException $exc) {
				// Provider class can not be resolved
				throw new Exception('Could not load two-factor auth provider ' . $provider->getService());
			}
		}

		return $providers;
	}

	/**
	 * Load an app by ID if it has not been loaded yet
	 *
	 * @param string $appId
	 */
	protected function loadTwoFactorApp(string $appId) {
		if (!OC_App::isAppLoaded($appId)) {
			OC_App::loadApp($appId);
		}
	}
}
