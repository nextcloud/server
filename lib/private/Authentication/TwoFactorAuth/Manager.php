<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christoph Wurst <christoph@owncloud.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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

namespace OC\Authentication\TwoFactorAuth;

use BadMethodCallException;
use Exception;
use OC;
use OC\App\AppManager;
use OC_App;
use OC\Authentication\Exceptions\InvalidTokenException;
use OC\Authentication\Token\IProvider as TokenProvider;
use OCP\Activity\IManager;
use OCP\AppFramework\QueryException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Authentication\TwoFactorAuth\IProvider;
use OCP\IConfig;
use OCP\ILogger;
use OCP\ISession;
use OCP\IUser;

class Manager {

	const SESSION_UID_KEY = 'two_factor_auth_uid';
	const SESSION_UID_DONE = 'two_factor_auth_passed';
	const BACKUP_CODES_APP_ID = 'twofactor_backupcodes';
	const BACKUP_CODES_PROVIDER_ID = 'backup_codes';
	const REMEMBER_LOGIN = 'two_factor_remember_login';

	/** @var AppManager */
	private $appManager;

	/** @var ISession */
	private $session;

	/** @var IConfig */
	private $config;

	/** @var IManager */
	private $activityManager;

	/** @var ILogger */
	private $logger;

	/** @var TokenProvider */
	private $tokenProvider;

	/** @var ITimeFactory */
	private $timeFactory;

	/**
	 * @param AppManager $appManager
	 * @param ISession $session
	 * @param IConfig $config
	 * @param IManager $activityManager
	 * @param ILogger $logger
	 * @param TokenProvider $tokenProvider
	 * @param ITimeFactory $timeFactory
	 */
	public function __construct(AppManager $appManager,
								ISession $session,
								IConfig $config,
								IManager $activityManager,
								ILogger $logger,
								TokenProvider $tokenProvider,
								ITimeFactory $timeFactory) {
		$this->appManager = $appManager;
		$this->session = $session;
		$this->config = $config;
		$this->activityManager = $activityManager;
		$this->logger = $logger;
		$this->tokenProvider = $tokenProvider;
		$this->timeFactory = $timeFactory;
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
		$providers = $this->getProviders($user, true);
		return isset($providers[$challengeProviderId]) ? $providers[$challengeProviderId] : null;
	}

	/**
	 * @param IUser $user
	 * @return IProvider|null the backup provider, if enabled for the given user
	 */
	public function getBackupProvider(IUser $user) {
		$providers = $this->getProviders($user, true);
		if (!isset($providers[self::BACKUP_CODES_PROVIDER_ID])) {
			return null;
		}
		return $providers[self::BACKUP_CODES_PROVIDER_ID];
	}

	/**
	 * Get the list of 2FA providers for the given user
	 *
	 * @param IUser $user
	 * @param bool $includeBackupApp
	 * @return IProvider[]
	 * @throws Exception
	 */
	public function getProviders(IUser $user, $includeBackupApp = false) {
		$allApps = $this->appManager->getEnabledAppsForUser($user);
		$providers = [];

		foreach ($allApps as $appId) {
			if (!$includeBackupApp && $appId === self::BACKUP_CODES_APP_ID) {
				continue;
			}

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

		$passed = $provider->verifyChallenge($user, $challenge);
		if ($passed) {
			if ($this->session->get(self::REMEMBER_LOGIN) === true) {
				// TODO: resolve cyclic dependency and use DI
				\OC::$server->getUserSession()->createRememberMeToken($user);
			}
			$this->session->remove(self::SESSION_UID_KEY);
			$this->session->remove(self::REMEMBER_LOGIN);
			$this->session->set(self::SESSION_UID_DONE, $user->getUID());

			// Clear token from db
			$sessionId = $this->session->getId();
			$token = $this->tokenProvider->getToken($sessionId);
			$tokenId = $token->getId();
			$this->config->deleteUserValue($user->getUID(), 'login_token_2fa', $tokenId);

			$this->publishEvent($user, 'twofactor_success', [
				'provider' => $provider->getDisplayName(),
			]);
		} else {
			$this->publishEvent($user, 'twofactor_failed', [
				'provider' => $provider->getDisplayName(),
			]);
		}
		return $passed;
	}

	/**
	 * Push a 2fa event the user's activity stream
	 *
	 * @param IUser $user
	 * @param string $event
	 */
	private function publishEvent(IUser $user, $event, array $params) {
		$activity = $this->activityManager->generateEvent();
		$activity->setApp('core')
			->setType('security')
			->setAuthor($user->getUID())
			->setAffectedUser($user->getUID())
			->setSubject($event, $params);
		try {
			$this->activityManager->publish($activity);
		} catch (BadMethodCallException $e) {
			$this->logger->warning('could not publish backup code creation activity', ['app' => 'core']);
			$this->logger->logException($e, ['app' => 'core']);
		}
	}

	/**
	 * Check if the currently logged in user needs to pass 2FA
	 *
	 * @param IUser $user the currently logged in user
	 * @return boolean
	 */
	public function needsSecondFactor(IUser $user = null) {
		if ($user === null) {
			return false;
		}

		// If we are authenticated using an app password skip all this
		if ($this->session->exists('app_password')) {
			return false;
		}

		// First check if the session tells us we should do 2FA (99% case)
		if (!$this->session->exists(self::SESSION_UID_KEY)) {

			// Check if the session tells us it is 2FA authenticated already
			if ($this->session->exists(self::SESSION_UID_DONE) &&
				$this->session->get(self::SESSION_UID_DONE) === $user->getUID()) {
				return false;
			}

			/*
			 * If the session is expired check if we are not logged in by a token
			 * that still needs 2FA auth
			 */
			try {
				$sessionId = $this->session->getId();
				$token = $this->tokenProvider->getToken($sessionId);
				$tokenId = $token->getId();
				$tokensNeeding2FA = $this->config->getUserKeys($user->getUID(), 'login_token_2fa');

				if (!in_array($tokenId, $tokensNeeding2FA, true)) {
					$this->session->set(self::SESSION_UID_DONE, $user->getUID());
					return false;
				}
			} catch (InvalidTokenException $e) {
			}
		}

		if (!$this->isTwoFactorAuthenticated($user)) {
			// There is no second factor any more -> let the user pass
			//   This prevents infinite redirect loops when a user is about
			//   to solve the 2FA challenge, and the provider app is
			//   disabled the same time
			$this->session->remove(self::SESSION_UID_KEY);

			$keys = $this->config->getUserKeys($user->getUID(), 'login_token_2fa');
			foreach ($keys as $key) {
				$this->config->deleteUserValue($user->getUID(), 'login_token_2fa', $key);
			}
			return false;
		}

		return true;
	}

	/**
	 * Prepare the 2FA login
	 *
	 * @param IUser $user
	 * @param boolean $rememberMe
	 */
	public function prepareTwoFactorLogin(IUser $user, $rememberMe) {
		$this->session->set(self::SESSION_UID_KEY, $user->getUID());
		$this->session->set(self::REMEMBER_LOGIN, $rememberMe);

		$id = $this->session->getId();
		$token = $this->tokenProvider->getToken($id);
		$this->config->setUserValue($user->getUID(), 'login_token_2fa', $token->getId(), $this->timeFactory->getTime());
	}

}
