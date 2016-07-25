<?php
/**
 * @author Christoph Wurst <christoph@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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

namespace OC\Authentication\TwoFactorAuth;

use Exception;
use OC;
use OC\App\AppManager;
use OC_App;
use OCP\AppFramework\QueryException;
use OCP\Authentication\TwoFactorAuth\IProvider;
use OCP\IConfig;
use OCP\ISession;
use OCP\IUser;

class Manager {

	const SESSION_UID_KEY = 'two_factor_auth_uid';

	/** @var AppManager */
	private $appManager;

	/** @var ISession */
	private $session;

	/** @var IConfig */
	private $config;

	/**
	 * @param AppManager $appManager
	 * @param ISession $session
	 * @param IConfig $config
	 */
	public function __construct(AppManager $appManager, ISession $session, IConfig $config) {
		$this->appManager = $appManager;
		$this->session = $session;
		$this->config = $config;
	}

	/**
	 * Determine whether the user must provide a second factor challenge
	 *
	 * @param IUser $user
	 * @return boolean
	 */
	public function isTwoFactorAuthenticated(IUser $user) {
		$twoFactorEnabled = ((int) $this->config->getUserValue($user->getUID(), 'core', 'two_factor_auth_disabled', 0)) === 0;
		return $twoFactorEnabled && count($this->getProviders($user)) > 0;
	}

	/**
	 * Disable 2FA checks for the given user
	 *
	 * @param IUser $user
	 */
	public function disableTwoFactorAuthentication(IUser $user) {
		$this->config->setUserValue($user->getUID(), 'core', 'two_factor_auth_disabled', 1);
	}

	/**
	 * Enable all 2FA checks for the given user
	 *
	 * @param IUser $user
	 */
	public function enableTwoFactorAuthentication(IUser $user) {
		$this->config->deleteUserValue($user->getUID(), 'core', 'two_factor_auth_disabled');
	}

	/**
	 * Get a 2FA provider by its ID
	 *
	 * @param IUser $user
	 * @param string $challengeProviderId
	 * @return IProvider|null
	 */
	public function getProvider(IUser $user, $challengeProviderId) {
		$providers = $this->getProviders($user);
		return isset($providers[$challengeProviderId]) ? $providers[$challengeProviderId] : null;
	}

	/**
	 * Get the list of 2FA providers for the given user
	 *
	 * @param IUser $user
	 * @return IProvider[]
	 */
	public function getProviders(IUser $user) {
		$allApps = $this->appManager->getEnabledAppsForUser($user);
		$providers = [];

		foreach ($allApps as $appId) {
			$info = $this->appManager->getAppInfo($appId);
			if (isset($info['two-factor-providers'])) {
				$providerClasses = $info['two-factor-providers'];
				foreach ($providerClasses as $class) {
					try {
						$this->loadTwoFactorApp($appId);
						$provider = OC::$server->query($class);
						$providers[$provider->getId()] = $provider;
					} catch (QueryException $exc) {
						// Provider class can not be resolved
						throw new Exception("Could not load two-factor auth provider $class");
					}
				}
			}
		}

		return array_filter($providers, function ($provider) use ($user) {
			/* @var $provider IProvider */
			return $provider->isTwoFactorAuthEnabledForUser($user);
		});
	}

	/**
	 * Load an app by ID if it has not been loaded yet
	 *
	 * @param string $appId
	 */
	protected function loadTwoFactorApp($appId) {
		if (!OC_App::isAppLoaded($appId)) {
			OC_App::loadApp($appId);
		}
	}

	/**
	 * Verify the given challenge
	 *
	 * @param string $providerId
	 * @param IUser $user
	 * @param string $challenge
	 * @return boolean
	 */
	public function verifyChallenge($providerId, IUser $user, $challenge) {
		$provider = $this->getProvider($user, $providerId);
		if (is_null($provider)) {
			return false;
		}

		$result = $provider->verifyChallenge($user, $challenge);
		if ($result) {
			$this->session->remove(self::SESSION_UID_KEY);
		}
		return $result;
	}

	/**
	 * Check if the currently logged in user needs to pass 2FA
	 *
	 * @return boolean
	 */
	public function needsSecondFactor() {
		return $this->session->exists(self::SESSION_UID_KEY);
	}

	/**
	 * Prepare the 2FA login (set session value)
	 *
	 * @param IUser $user
	 */
	public function prepareTwoFactorLogin(IUser $user) {
		$this->session->set(self::SESSION_UID_KEY, $user->getUID());
	}

}
