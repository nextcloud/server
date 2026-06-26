<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Authentication\TwoFactorAuth;

use BadMethodCallException;
use Exception;
use OC\Authentication\Token\IProvider as TokenProvider;
use OC\User\Session;
use OCP\Activity\IManager;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Authentication\Exceptions\InvalidTokenException;
use OCP\Authentication\TwoFactorAuth\IActivatableAtLogin;
use OCP\Authentication\TwoFactorAuth\IProvider;
use OCP\Authentication\TwoFactorAuth\IRegistry;
use OCP\Authentication\TwoFactorAuth\TwoFactorProviderChallengeFailed;
use OCP\Authentication\TwoFactorAuth\TwoFactorProviderChallengePassed;
use OCP\Authentication\TwoFactorAuth\TwoFactorProviderForUserDisabled;
use OCP\Authentication\TwoFactorAuth\TwoFactorProviderForUserEnabled;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IConfig;
use OCP\ISession;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Server;
use OCP\Session\Exceptions\SessionNotAvailableException;
use Psr\Log\LoggerInterface;
use function array_diff;
use function array_filter;

class Manager {
	public const SESSION_UID_KEY = 'two_factor_auth_uid';
	public const SESSION_UID_DONE = 'two_factor_auth_passed';
	public const REMEMBER_LOGIN = 'two_factor_remember_login';
	public const BACKUP_CODES_PROVIDER_ID = 'backup_codes';

	/** @psalm-var array<string, bool> */
	private $userIsTwoFactorAuthenticated = [];

	public function __construct(
		private ProviderLoader $providerLoader,
		private IRegistry $providerRegistry,
		private MandatoryTwoFactor $mandatoryTwoFactor,
		private ISession $session,
		private IConfig $config,
		private IManager $activityManager,
		private LoggerInterface $logger,
		private TokenProvider $tokenProvider,
		private ITimeFactory $timeFactory,
		private IEventDispatcher $dispatcher,
	) {
	}

	/**
	 * Determine whether the user must provide a second factor challenge
	 */
	public function isTwoFactorAuthenticated(IUser $user): bool {
		if (isset($this->userIsTwoFactorAuthenticated[$user->getUID()])) {
			return $this->userIsTwoFactorAuthenticated[$user->getUID()];
		}

		if ($this->mandatoryTwoFactor->isEnforcedFor($user)) {
			return true;
		}

		$providerStates = $this->providerRegistry->getProviderStates($user);
		$providers = $this->providerLoader->getProviders($user);
		$fixedStates = $this->fixMissingProviderStates($providerStates, $providers, $user);
		$enabled = array_filter($fixedStates);
		$providerIds = array_keys($enabled);
		$providerIdsWithoutBackupCodes = array_diff($providerIds, [self::BACKUP_CODES_PROVIDER_ID]);

		$this->userIsTwoFactorAuthenticated[$user->getUID()] = !empty($providerIdsWithoutBackupCodes);
		return $this->userIsTwoFactorAuthenticated[$user->getUID()];
	}

	/**
	 * Get a 2FA provider by its ID
	 */
	public function getProvider(IUser $user, string $challengeProviderId): ?IProvider {
		$providers = $this->getProviderSet($user)->getProviders();
		return $providers[$challengeProviderId] ?? null;
	}

	/**
	 * @return IActivatableAtLogin[]
	 * @throws Exception
	 */
	public function getLoginSetupProviders(IUser $user): array {
		$providers = $this->providerLoader->getProviders($user);
		return array_filter($providers, function (IProvider $provider) {
			return ($provider instanceof IActivatableAtLogin);
		});
	}

	/**
	 * Check if the persistant mapping of enabled/disabled state of each available
	 * provider is missing an entry and add it to the registry in that case.
	 *
	 * @todo remove in Nextcloud 17 as by then all providers should have been updated
	 *
	 * @param array<string, bool> $providerStates
	 * @param IProvider[] $providers
	 * @param IUser $user
	 * @return array<string, bool> the updated $providerStates variable
	 */
	private function fixMissingProviderStates(array $providerStates,
		array $providers, IUser $user): array {
		foreach ($providers as $provider) {
			if (isset($providerStates[$provider->getId()])) {
				// All good
				continue;
			}

			$enabled = $provider->isTwoFactorAuthEnabledForUser($user);
			if ($enabled) {
				$this->providerRegistry->enableProviderFor($provider, $user);
			} else {
				$this->providerRegistry->disableProviderFor($provider, $user);
			}
			$providerStates[$provider->getId()] = $enabled;
		}

		return $providerStates;
	}

	/**
	 * @param array $states
	 * @param IProvider[] $providers
	 */
	private function isProviderMissing(array $states, array $providers): bool {
		$indexed = [];
		foreach ($providers as $provider) {
			$indexed[$provider->getId()] = $provider;
		}

		$missing = [];
		foreach ($states as $providerId => $enabled) {
			if (!$enabled) {
				// Don't care
				continue;
			}

			if (!isset($indexed[$providerId])) {
				$missing[] = $providerId;
				$this->logger->alert("two-factor auth provider '$providerId' failed to load",
					[
						'app' => 'core',
					]);
			}
		}

		if (!empty($missing)) {
			// There was at least one provider missing
			$this->logger->alert(count($missing) . ' two-factor auth providers failed to load', ['app' => 'core']);

			return true;
		}

		// If we reach this, there was not a single provider missing
		return false;
	}

	/**
	 * Get the list of 2FA providers for the given user
	 *
	 * @param IUser $user
	 * @throws Exception
	 */
	public function getProviderSet(IUser $user): ProviderSet {
		$providerStates = $this->providerRegistry->getProviderStates($user);
		$providers = $this->providerLoader->getProviders($user);

		$fixedStates = $this->fixMissingProviderStates($providerStates, $providers, $user);
		$isProviderMissing = $this->isProviderMissing($fixedStates, $providers);

		$enabled = array_filter($providers, function (IProvider $provider) use ($fixedStates) {
			return $fixedStates[$provider->getId()];
		});
		return new ProviderSet($enabled, $isProviderMissing);
	}

	/**
	 * Verify the given challenge
	 *
	 * @param string $providerId
	 * @param IUser $user
	 * @param string $challenge
	 * @return boolean
	 */
	public function verifyChallenge(string $providerId, IUser $user, string $challenge): bool {
		$provider = $this->getProvider($user, $providerId);
		if ($provider === null) {
			return false;
		}

		$passed = $provider->verifyChallenge($user, $challenge);
		if ($passed) {
			if ($this->session->get(self::REMEMBER_LOGIN) === true) {
				// TODO: resolve cyclic dependency and use DI
				/** @var Session $session */
				$session = Server::get(IUserSession::class);
				$session->createRememberMeToken($user);
			}
			$this->session->remove(self::SESSION_UID_KEY);
			$this->session->remove(self::REMEMBER_LOGIN);
			$this->session->set(self::SESSION_UID_DONE, $user->getUID());

			// Clear token from db
			$sessionId = $this->session->getId();
			$token = $this->tokenProvider->getToken($sessionId);
			$tokenId = $token->getId();
			$this->config->deleteUserValue($user->getUID(), 'login_token_2fa', (string)$tokenId);

			$this->dispatcher->dispatchTyped(new TwoFactorProviderForUserEnabled($user, $provider));
			$this->dispatcher->dispatchTyped(new TwoFactorProviderChallengePassed($user, $provider));

			$this->publishEvent($user, 'twofactor_success', [
				'provider' => $provider->getDisplayName(),
			]);
		} else {
			$this->dispatcher->dispatchTyped(new TwoFactorProviderForUserDisabled($user, $provider));
			$this->dispatcher->dispatchTyped(new TwoFactorProviderChallengeFailed($user, $provider));

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
	 * @param array $params
	 */
	private function publishEvent(IUser $user, string $event, array $params) {
		$activity = $this->activityManager->generateEvent();
		$activity->setApp('core')
			->setType('security')
			->setAuthor($user->getUID())
			->setAffectedUser($user->getUID())
			->setSubject($event, $params);
		try {
			$this->activityManager->publish($activity);
		} catch (BadMethodCallException $e) {
			$this->logger->warning('could not publish activity', ['app' => 'core', 'exception' => $e]);
		}
	}

	/**
	 * Check if the currently logged in user needs to pass 2FA
	 *
	 * @param IUser $user the currently logged in user
	 * @return boolean
	 */
	public function needsSecondFactor(?IUser $user = null): bool {
		if ($user === null) {
			return false;
		}

		// If we are authenticated using an app password or AppAPI Auth, skip all this
		if ($this->session->exists('app_password') || $this->session->get('app_api') === true) {
			return false;
		}

		// First check if the session tells us we should do 2FA (99% case)
		if (!$this->session->exists(self::SESSION_UID_KEY)) {
			// Check if the session tells us it is 2FA authenticated already
			if ($this->session->exists(self::SESSION_UID_DONE)
				&& $this->session->get(self::SESSION_UID_DONE) === $user->getUID()) {
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

				if (!\in_array((string)$tokenId, $tokensNeeding2FA, true)) {
					$this->session->set(self::SESSION_UID_DONE, $user->getUID());
					return false;
				}
			} catch (InvalidTokenException|SessionNotAvailableException $e) {
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
	public function prepareTwoFactorLogin(IUser $user, bool $rememberMe) {
		$this->session->set(self::SESSION_UID_KEY, $user->getUID());
		$this->session->set(self::REMEMBER_LOGIN, $rememberMe);

		$id = $this->session->getId();
		$token = $this->tokenProvider->getToken($id);
		$this->config->setUserValue($user->getUID(), 'login_token_2fa', (string)$token->getId(), (string)$this->timeFactory->getTime());
	}

	public function clearTwoFactorPending(string $userId) {
		$tokensNeeding2FA = $this->config->getUserKeys($userId, 'login_token_2fa');

		foreach ($tokensNeeding2FA as $tokenId) {
			$this->config->deleteUserValue($userId, 'login_token_2fa', $tokenId);

			try {
				$this->tokenProvider->invalidateTokenById($userId, (int)$tokenId);
			} catch (DoesNotExistException $e) {
			}
		}
	}
}
