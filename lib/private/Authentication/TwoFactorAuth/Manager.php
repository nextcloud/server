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
	 * Whether the user currently has 2FA effectively enabled.
	 *
	 * This returns true when 2FA is mandatory for the user or when the user has at
	 * least one enabled non-backup-code provider. 
	 *
	 * Backup codes alone do not count as 2FA being enabled for this check.
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
	 * Verify a 2FA challenge against the given provider for the user.
	 *
	 * On success, this finalizes the pending 2FA login state, clears the stored
	 * pending-login token marker, optionally creates a remember-me token, and
	 * dispatches success events. On failure, failure events are dispatched.
	 *
	 * Returns false if the provider is not available for the user or if the
	 * challenge verification fails.
	 *
	 * @throws Exception
	 */
	public function verifyChallenge(string $providerId, IUser $user, string $challenge): bool {
		$provider = $this->getProvider($user, $providerId);

		if ($provider === null) {
			return false;
		}

		if (!$provider->verifyChallenge($user, $challenge)) {
			$this->handleFailedChallenge($user, $provider);
			return false;
		}

		$this->handleSuccessfulChallenge($user, $provider);
		return true;
	}

	private function handleSuccessfulChallenge(IUser $user, IProvider $provider): void {
		$uid = $user->getUID();

		if ($this->session->get(self::REMEMBER_LOGIN) === true) {
			// TODO: resolve cyclic dependency and use DI
			/** @var Session $session */
			$session = Server::get(IUserSession::class);
			$session->createRememberMeToken($user);
		}

		$this->session->remove(self::SESSION_UID_KEY);
		$this->session->remove(self::REMEMBER_LOGIN);
		$this->session->set(self::SESSION_UID_DONE, $uid);

		// Clear the pending 2FA marker for the current login token.
		$sessionId = $this->session->getId();
		$token = $this->tokenProvider->getToken($sessionId);
		$this->config->deleteUserValue($uid, 'login_token_2fa', (string)$token->getId());

		$this->dispatcher->dispatchTyped(new TwoFactorProviderForUserEnabled($user, $provider));
		$this->dispatcher->dispatchTyped(new TwoFactorProviderChallengePassed($user, $provider));

		$this->publishEvent($user, 'twofactor_success', ['provider' => $provider->getDisplayName()]);
}

	private function handleFailedChallenge(IUser $user, IProvider $provider): void {
		$this->dispatcher->dispatchTyped(new TwoFactorProviderForUserDisabled($user, $provider));
		$this->dispatcher->dispatchTyped(new TwoFactorProviderChallengeFailed($user, $provider));

		$this->publishEvent($user, 'twofactor_failed', ['provider' => $provider->getDisplayName()]);
	}

	/**
	 * Publish a 2FA security activity event for the user.
	 *
	 * Failures to publish the activity are logged and otherwise ignored.
	 *
	 * @param array<string, mixed> $params
	 */
	private function publishEvent(IUser $user, string $event, array $params): void {
		$uid = $user->getUID();

		$activity = $this->activityManager->generateEvent();
		$activity->setApp('core')
			->setType('security')
			->setAuthor($uid)
			->setAffectedUser($uid)
			->setSubject($event, $params);

		try {
			$this->activityManager->publish($activity);
		} catch (BadMethodCallException $e) {
			$this->logger->warning('Could not publish activity', ['app' => 'core', 'exception' => $e]);
		}
	}

	/**
	 * Determine whether the current login session still requires a 2FA challenge.
	 *
	 * This considers pending-challenge markers, session- and token-backed satisfied
	 * states, and whether the user still has a usable second factor configured.
	 */
	public function needsSecondFactor(?IUser $user = null): bool {
		if ($user === null) {
			return false;
		}

		// App passwords and AppAPI-authenticated sessions bypass interactive 2FA.
		if ($this->session->exists('app_password') || $this->session->get('app_api') === true) {
			return false;
		}

		$uid = $user->getUID();

		// A pending challenge is authoritative. If it can still be completed, 2FA is
		// required. Otherwise clear stale pending state and let the user proceed.
		if ($this->hasPendingSecondFactorChallenge()) {
			if ($this->isTwoFactorAuthenticated($user)) {
				return true;
			}
			
			$this->clearStaleSecondFactorChallenge($user);
			return false;
		}

		// Without a pending challenge, previously-satisfied state may still satisfy 2FA.
		if ($this->isSecondFactorSatisfiedBySession($user)) {
			return false;
		}

		if ($this->isSecondFactorSatisfiedByToken($user)) {
			$this->session->set(self::SESSION_UID_DONE, $uid);
			return false;
		}

		// No pending or satisfied state was found. If the user no longer has a usable second
		// factor, clear stale pending state and let the user proceed. Otherwise, the current
		// login session still requires a completed 2FA challenge.
		if (!$this->isTwoFactorAuthenticated($user)) {
			$this->clearStaleSecondFactorChallenge($user);
			return false;
		}

		// TODO: consider clearing state here too for added robustness
		return true;
	}

	/**
	 * Clear stale pending 2FA state for the user.
	 *
	 * Intentionally limited to the "pending challenge" markers so a future, more explicit
	 * auth state model can evolve independently.
	 *
	 * @todo: merge/integrate/refactor alongside clearTwoFactorPending()
	 */
	private function clearStaleSecondFactorChallenge(IUser $user): void {
		$uid = $user->getUID();

		$this->session->remove(self::SESSION_UID_KEY);

		$keys = $this->config->getUserKeys($uid, 'login_token_2fa');
		foreach ($keys as $key) {
			$this->config->deleteUserValue($uid, 'login_token_2fa', $key);
		}
	}

	/**
	 * Whether the session currently indicates an in-progress 2FA challenge.
	 */
	private function hasPendingSecondFactorChallenge(): bool {
		// TODO: replace marker-based interpretation with an explicit auth state model.
		// Currently, SESSION_UID_KEY is the authoritative signal for an in-progress 2FA flow.
		return $this->session->exists(self::SESSION_UID_KEY);
	}

	/**
	 * Whether this session already recorded 2FA completion for the user.
	 */
	private function isSecondFactorSatisfiedBySession(IUser $user): bool {
		$uid = $user->getUID();

		return $this->session->exists(self::SESSION_UID_DONE)
			&& $this->session->get(self::SESSION_UID_DONE) === $uid;
	}

	/**
	 * Whether the current login token no longer belongs to a login pending 2FA.
	 */
	private function isSecondFactorSatisfiedByToken(IUser $user): bool {
		$uid = $user->getUID();

		try {
			$sessionId = $this->session->getId();
			$token = $this->tokenProvider->getToken($sessionId);
			$tokensNeeding2FA = $this->config->getUserKeys($uid, 'login_token_2fa');

			return !\in_array((string)$token->getId(), $tokensNeeding2FA, true);
		} catch (InvalidTokenException|SessionNotAvailableException $e) {
			return false;
		}
	}

	/**
	 * Mark the current login attempt as pending 2FA verification.
	 *
	 * Stores the pending 2FA state in the session, preserves the remember-me
	 * choice for completion after a successful challenge, and records the current
	 * login token as requiring 2FA so the flow can be resumed if the session is
	 * lost before verification completes.
	 *
	 * @param IUser $user
	 * @param bool $rememberMe Whether remember-me should be applied after successful 2FA
	 */
	public function prepareTwoFactorLogin(IUser $user, bool $rememberMe) {
		$uid = $user->getUID();

		$this->session->set(self::SESSION_UID_KEY, $uid);
		$this->session->set(self::REMEMBER_LOGIN, $rememberMe);

		$sessionId = $this->session->getId();
		$token = $this->tokenProvider->getToken($sessionId);
		$tokenId = (string)$token->getId();
		$timestamp = (string)$this->timeFactory->getTime();

		$this->config->setUserValue($uid, 'login_token_2fa', $tokenId, $timestamp);
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
