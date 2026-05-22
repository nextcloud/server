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
	/**
	 * Session keys and provider identifiers used during the 2FA login flow.
	 *
	 * The string values are persisted in session/config state and should therefore
	 * remain stable.
	 */
	public const SESSION_UID_KEY = 'two_factor_auth_uid';
	public const SESSION_UID_DONE = 'two_factor_auth_passed';
	public const REMEMBER_LOGIN = 'two_factor_remember_login';
	public const BACKUP_CODES_PROVIDER_ID = 'backup_codes';

	/** @psalm-var array<string, bool> */
	private $userHasUsableSecondFactor = [];

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
	 * Determine whether the user currently has 2FA effectively enabled.
	 *
	 * This returns true when 2FA is mandatory for the user or when the user has at
	 * least one enabled non-backup-code provider. Backup codes alone do not count
	 * as 2FA being enabled for this check.
	 *
	 * The result is cached per user for the lifetime of this manager instance.
	 */
	public function isTwoFactorAuthenticated(IUser $user): bool {
		$uid = $user->getUID();

		if (isset($this->userHasUsableSecondFactor[$uid])) {
			return $this->userHasUsableSecondFactor[$uid];
		}

		if ($this->mandatoryTwoFactor->isEnforcedFor($user)) {
			// TODO: cache too?
			return true;
		}

		$providerStates = $this->providerRegistry->getProviderStates($user);
		$providers = $this->providerLoader->getProviders($user);
		$fixedStates = $this->fixMissingProviderStates($providerStates, $providers, $user);

		$enabledProviderIds = array_keys(array_filter($fixedStates));
		$hasNonBackupProvider = !empty(array_diff($enabledProviderIds, [self::BACKUP_CODES_PROVIDER_ID]));

		$this->userHasUsableSecondFactor[$uid] = $hasNonBackupProvider;

		return $hasNonBackupProvider;
	}

	/**
	 * Return the enabled 2FA provider with the given ID for the user.
	 *
	 * Returns null if the provider is not available in the user's enabled provider
	 * set.
	 *
	 * @throws Exception
	 */
	public function getProvider(IUser $user, string $providerId): ?IProvider {
		$providers = $this->getProviderSet($user)->getProviders();
		return $providers[$providerId] ?? null;
	}

	/**
	 * Return the user's 2FA providers that can be activated during login.
	 *
	 * @return IActivatableAtLogin[]
	 * @throws Exception
	 */
	public function getLoginSetupProviders(IUser $user): array {
		$providers = $this->providerLoader->getProviders($user);

		return array_filter(
			$providers,
			static fn (IProvider $provider): bool => $provider instanceof IActivatableAtLogin,
		);
	}

	/**
	 * Ensure that every loaded provider has a persisted enabled/disabled state.
	 *
	 * For providers missing from the registry state map, this queries the provider
	 * directly and writes the derived state back to the registry.
	 *
	 * @todo Remove this compatibility path once provider state entries are guaranteed
	 *       to exist for every loaded provider for all supported upgrade paths.
	 *
	 * @param array<string, bool> $providerStates Persisted provider state map,
	 *                                            indexed by provider ID
	 * @param IProvider[] $providers Loaded providers for the user
	 * @return array<string, bool> Complete provider state map indexed by provider ID
	 */
	private function fixMissingProviderStates(
		array $providerStates,
		array $providers,
		IUser $user,
	): array {
		foreach ($providers as $provider) {
			$providerId = $provider->getId();

			if (isset($providerStates[$providerId])) {
				continue;
			}

			$enabled = $provider->isTwoFactorAuthEnabledForUser($user);

			if ($enabled) {
				$this->providerRegistry->enableProviderFor($provider, $user);
			} else {
				$this->providerRegistry->disableProviderFor($provider, $user);
			}

			$providerStates[$providerId] = $enabled;
		}

		return $providerStates;
	}

	/**
	 * Check whether any enabled provider state refers to a provider that failed to load.
	 *
	 * Disabled provider states are ignored. Missing enabled providers are logged.
	 *
	 * @param array<string, bool> $states Provider state map indexed by provider ID
	 * @param IProvider[] $providers Loaded providers for the user
	 */
	private function isProviderMissing(array $states, array $providers): bool {
		$providersById = [];
		foreach ($providers as $provider) {
			$providersById[$provider->getId()] = $provider;
		}

		$missingCount = 0;
		foreach ($states as $providerId => $enabled) {
			if (!$enabled) {
				continue;
			}

			if (!isset($providersById[$providerId])) {
				$missingCount++;
				$this->logger->alert("two-factor auth provider '$providerId' failed to load", ['app' => 'core']);
			}
		}

		if ($missingCount > 0) {
			$this->logger->alert($missingCount . ' two-factor auth providers failed to load', ['app' => 'core']);
			return true;
		}

		return false;
	}

	/**
	 * Build the user's enabled 2FA provider set.
	 *
	 * Missing persisted provider states are repaired before filtering providers.
	 * The returned ProviderSet also indicates whether an enabled provider failed
	 * to load.
	 *
	 * @throws Exception
	 */
	public function getProviderSet(IUser $user): ProviderSet {
		$providerStates = $this->providerRegistry->getProviderStates($user);
		$providers = $this->providerLoader->getProviders($user);

		$completeStates = $this->fixMissingProviderStates($providerStates, $providers, $user);
		$isProviderMissing = $this->isProviderMissing($completeStates, $providers);

		$enabledProviders = array_filter(
			$providers,
			static fn (IProvider $provider): bool => $completeStates[$provider->getId()],
		);
		
		return new ProviderSet($enabledProviders, $isProviderMissing);
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
