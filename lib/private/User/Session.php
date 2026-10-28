<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\User;

use OC;
use OC\Authentication\Events\LoginFailed;
use OC\Authentication\Exceptions\PasswordlessTokenException;
use OC\Authentication\Exceptions\PasswordLoginForbiddenException;
use OC\Authentication\Token\IProvider;
use OC\Authentication\Token\IToken;
use OC\Authentication\Token\PublicKeyToken;
use OC\Authentication\Token\RememberMeTokenGrace;
use OC\Authentication\TwoFactorAuth\Manager as TwoFactorAuthManager;
use OC\Hooks\Emitter;
use OC\Hooks\PublicEmitter;
use OC\Http\CookieHelper;
use OC\Security\CSRF\CsrfTokenManager;
use OCA\DAV\Connector\Sabre\Auth;
use OCP\AppFramework\Db\TTransactional;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Authentication\Exceptions\ExpiredTokenException;
use OCP\Authentication\Exceptions\InvalidTokenException;
use OCP\Authentication\Token\IToken as OCPIToken;
use OCP\Config\IUserConfig;
use OCP\EventDispatcher\GenericEvent;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IRequest;
use OCP\ISession;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Lockdown\ILockdownManager;
use OCP\Security\Bruteforce\IThrottler;
use OCP\Security\ISecureRandom;
use OCP\Server;
use OCP\Session\Exceptions\SessionNotAvailableException;
use OCP\User\Events\BeforeUserLoggedInEvent;
use OCP\User\Events\BeforeUserLoggedInWithCookieEvent;
use OCP\User\Events\BeforeUserLoggedOutEvent;
use OCP\User\Events\PostLoginEvent;
use OCP\User\Events\UserFirstTimeLoggedInEvent;
use OCP\User\Events\UserLoggedInEvent;
use OCP\User\Events\UserLoggedInWithCookieEvent;
use OCP\User\Events\UserLoggedOutEvent;
use OCP\Util;
use Psr\Log\LoggerInterface;

/**
 * Class Session
 *
 * Hooks available in scope \OC\User:
 * - assignedUserId(string $uid)
 * - preUnassignedUserId(string $uid)
 * - postUnassignedUserId(string $uid)
 * - postLogin(\OC\User\User $user, string $loginName, string $password, boolean $isTokenLogin)
 * - postRememberedLogin(\OC\User\User $user)
 * - logout()
 * - postLogout()
 *
 * @package OC\User
 */
class Session implements IUserSession, Emitter {
	use TTransactional;

	protected ?IUser $activeUser = null;

	// bool, stores if a user want to access a resource anonymously, e.g if they open a public link
	private bool $incognitoMode = false;

	public function __construct(
		private Manager $manager,
		private ISession $session,
		private ITimeFactory $timeFactory,
		private ?IProvider $tokenProvider,
		private IConfig $config,
		private ISecureRandom $random,
		private ILockdownManager $lockdownManager,
		private LoggerInterface $logger,
		private IEventDispatcher $dispatcher,
		private RememberMeTokenGrace $rememberMeTokenGrace,
		private IUserConfig $userConfig,
	) {
	}

	/**
	 * @param IProvider $provider
	 */
	public function setTokenProvider(IProvider $provider) {
		$this->tokenProvider = $provider;
	}

	/**
	 * @param string $scope
	 * @param string $method
	 * @param callable $callback
	 */
	#[\Override]
	public function listen($scope, $method, callable $callback) {
		$this->manager->listen($scope, $method, $callback);
	}

	/**
	 * @param string $scope optional
	 * @param string $method optional
	 * @param callable $callback optional
	 */
	#[\Override]
	public function removeListener($scope = null, $method = null, ?callable $callback = null) {
		$this->manager->removeListener($scope, $method, $callback);
	}

	/**
	 * get the manager object
	 *
	 * @return Manager|PublicEmitter
	 */
	public function getManager() {
		return $this->manager;
	}

	/**
	 * get the session object
	 *
	 * @return ISession
	 */
	public function getSession() {
		return $this->session;
	}

	/**
	 * set the session object
	 *
	 * @param ISession $session
	 */
	public function setSession(ISession $session) {
		if ($this->session instanceof ISession) {
			$this->session->close();
		}
		$this->session = $session;
		$this->activeUser = null;
	}

	/**
	 * set the currently active user
	 *
	 * @param IUser|null $user
	 */
	#[\Override]
	public function setUser($user) {
		if (is_null($user)) {
			$this->session->remove('user_id');
		} else {
			$this->session->set('user_id', $user->getUID());
		}
		$this->activeUser = $user;
	}

	/**
	 * Temporarily set the currently active user without persisting in the session
	 *
	 * @param IUser|null $user
	 */
	#[\Override]
	public function setVolatileActiveUser(?IUser $user): void {
		$this->activeUser = $user;
	}

	/**
	 * get the current active user
	 *
	 * @return IUser|null Current user, otherwise null
	 */
	#[\Override]
	public function getUser() {
		if ($this->isIncognitoMode()) {
			return null;
		}
		if (is_null($this->activeUser)) {
			$uid = $this->session->get('user_id');
			if (is_null($uid)) {
				return null;
			}
			$this->activeUser = $this->manager->get($uid);
			if (is_null($this->activeUser)) {
				return null;
			}
			$this->validateSession();
		}
		return $this->activeUser;
	}

	#[\Override]
	public function isIncognitoMode(): bool {
		return $this->incognitoMode;
	}

	#[\Override]
	public function setIncognitoMode(bool $mode): void {
		$this->incognitoMode = $mode;
	}

	/**
	 * Validate whether the current session is valid
	 *
	 * - For token-authenticated clients, the token validity is checked
	 * - For browsers, the session token validity is checked
	 */
	protected function validateSession() {
		$token = null;
		$appPassword = $this->session->get('app_password');

		if (is_null($appPassword)) {
			try {
				$token = $this->session->getId();
			} catch (SessionNotAvailableException $ex) {
				return;
			}
		} else {
			$token = $appPassword;
		}

		if (!$this->validateToken($token)) {
			// Session was invalidated
			$this->logout();
		}

		// Update last seen timestamp
		if ($this->isLoggedIn()) {
			$this->getUser()->updateLastLoginTimestamp();
		}
	}

	/**
	 * Checks whether the user is logged in
	 *
	 * @return bool if logged in
	 */
	#[\Override]
	public function isLoggedIn() {
		$user = $this->getUser();
		if (is_null($user)) {
			return false;
		}

		return $user->isEnabled();
	}

	/**
	 * set the login name
	 *
	 * @param string|null $loginName for the logged in user
	 */
	public function setLoginName($loginName) {
		if (is_null($loginName)) {
			$this->session->remove('loginname');
		} else {
			$this->session->set('loginname', $loginName);
		}
	}

	/**
	 * Get the login name of the current user
	 *
	 * @return ?string
	 */
	public function getLoginName() {
		if ($this->activeUser) {
			return $this->session->get('loginname');
		}

		$uid = $this->session->get('user_id');
		if ($uid) {
			$this->activeUser = $this->manager->get($uid);
			return $this->session->get('loginname');
		}

		return null;
	}

	/**
	 * @return null|string
	 */
	#[\Override]
	public function getImpersonatingUserID(): ?string {
		return $this->session->get('oldUserId');
	}

	#[\Override]
	public function setImpersonatingUserID(bool $useCurrentUser = true): void {
		if ($useCurrentUser === false) {
			$this->session->remove('oldUserId');
			return;
		}

		$currentUser = $this->getUser();

		if ($currentUser === null) {
			throw new NoUserException();
		}
		$this->session->set('oldUserId', $currentUser->getUID());
	}
	/**
	 * set the token id
	 *
	 * @param int|null $token that was used to log in
	 */
	protected function setToken($token) {
		if ($token === null) {
			$this->session->remove('token-id');
		} else {
			$this->session->set('token-id', $token);
		}
	}

	/**
	 * try to log in with the provided credentials
	 *
	 * @param string $uid
	 * @param string $password
	 * @return boolean|null
	 * @throws LoginException
	 */
	#[\Override]
	public function login($uid, $password) {
		$this->session->regenerateId();
		if ($this->validateToken($password, $uid)) {
			return $this->loginWithToken($password);
		}
		return $this->loginWithPassword($uid, $password);
	}

	/**
	 * @param IUser $user
	 * @param array{loginName:string,password:string,token?:IToken} $loginDetails
	 * @param bool $regenerateSessionId
	 * @return true returns true if login successful or an exception otherwise
	 * @throws LoginException
	 */
	public function completeLogin(IUser $user, array $loginDetails, $regenerateSessionId = true) {
		if (!$user->isEnabled()) {
			// disabled users can not log in
			// injecting l10n does not work - there is a circular dependency between session and \OCP\L10N\IFactory
			$message = Util::getL10N('lib')->t('Account disabled');
			throw new DisabledUserException($message);
		}

		if ($regenerateSessionId) {
			$this->session->regenerateId();
			$this->session->remove(Auth::DAV_AUTHENTICATED);
		}

		$this->setUser($user);
		$this->setLoginName($loginDetails['loginName']);

		$isToken = isset($loginDetails['token']) && $loginDetails['token'] instanceof IToken;
		if ($isToken) {
			$this->setToken($loginDetails['token']->getId());
			$this->lockdownManager->setToken($loginDetails['token']);
			$user->updateLastLoginTimestamp();
			$firstTimeLogin = false;
		} else {
			$this->setToken(null);
			$firstTimeLogin = $user->updateLastLoginTimestamp();
		}

		$this->dispatcher->dispatchTyped(new PostLoginEvent(
			$user,
			$loginDetails['loginName'],
			$loginDetails['password'],
			$isToken
		));
		$this->dispatcher->dispatchTyped(new UserLoggedInEvent(
			$user,
			$loginDetails['loginName'],
			$loginDetails['password'],
			$isToken
		));
		$this->manager->emit('\OC\User', 'postLogin', [
			$user,
			$loginDetails['loginName'],
			$loginDetails['password'],
			$isToken,
		]);
		if ($this->isLoggedIn()) {
			$this->prepareUserLogin($firstTimeLogin, $regenerateSessionId);
			return true;
		}

		$message = Util::getL10N('lib')->t('Login canceled by app');
		throw new LoginException($message);
	}

	/**
	 * Tries to log in a client
	 *
	 * Checks token auth enforced
	 * Checks 2FA enabled
	 *
	 * @param string $user
	 * @param string $password
	 * @param IRequest $request
	 * @param IThrottler $throttler
	 * @throws LoginException
	 * @throws PasswordLoginForbiddenException
	 * @return boolean
	 */
	public function logClientIn($user,
		$password,
		IRequest $request,
		IThrottler $throttler) {
		$remoteAddress = $request->getRemoteAddress();
		$currentDelay = $throttler->sleepDelayOrThrowOnMax($remoteAddress, 'login');

		$this->dispatcher->dispatchTyped(new BeforeUserLoggedInEvent($user, $password));

		try {
			$dbToken = $this->getTokenFromPassword($password);
			$isTokenPassword = $dbToken !== null;
			if (($dbToken instanceof PublicKeyToken)
				&& !in_array($dbToken->getType(), [IToken::PERMANENT_TOKEN,IToken::ONETIME_TOKEN], true)
			) {
				// Refuse session tokens here, only app tokens and onetime tokens are handled
				return false;
			}
		} catch (ExpiredTokenException) {
			// Just return on an expired token no need to check further or record a failed login
			return false;
		}

		if (!$isTokenPassword && ($this->isTokenAuthEnforced() || $this->isTwoFactorEnforced($user))) {
			$this->handleLoginFailed($throttler, $currentDelay, $remoteAddress, $user, $password);
			throw new PasswordLoginForbiddenException();
		}

		// Try to login with this username and password
		if (!$this->login($user, $password)) {
			// Failed, maybe the user used their email address
			if (!filter_var($user, FILTER_VALIDATE_EMAIL)) {
				$this->handleLoginFailed($throttler, $currentDelay, $remoteAddress, $user, $password);
				return false;
			}

			if ($isTokenPassword) {
				$dbToken = $this->tokenProvider->getToken($password);
				$userFromToken = $this->manager->get($dbToken->getUID());
				$isValidEmailLogin = $userFromToken->getEMailAddress() === $user
					&& $this->validateTokenLoginName($userFromToken->getEMailAddress(), $dbToken);
			} else {
				$users = $this->manager->getByEmail($user);
				$isValidEmailLogin = (\count($users) === 1 && $this->login($users[0]->getUID(), $password));
			}

			if (!$isValidEmailLogin) {
				$this->handleLoginFailed($throttler, $currentDelay, $remoteAddress, $user, $password);
				return false;
			}
		}

		if ($isTokenPassword) {
			if ($dbToken instanceof PublicKeyToken && $dbToken->getType() === IToken::ONETIME_TOKEN) {
				$this->tokenProvider->invalidateTokenById($dbToken->getUID(), $dbToken->getId());
				if ($request->getPathInfo() !== '/core/getapppassword-onetime') {
					return false;
				}

				$this->session->set('one_time_token', $password);
			} else {
				$this->session->set('app_password', $password);
			}
		} else {
			$this->session->set('last-password-confirm', $this->timeFactory->getTime());
			if ($this->supportsCookies($request)) {
				// Password login, but cookies supported -> create (browser) session token
				$this->createSessionToken($request, $this->getUser()->getUID(), $user, $password);
			}
		}

		return true;
	}

	private function handleLoginFailed(IThrottler $throttler, int $currentDelay, string $remoteAddress, string $user, ?string $password) {
		$this->logger->debug("Login failed: '" . $user . "' (Remote IP: '" . $remoteAddress . "')", ['app' => 'core', 'exception' => new \Exception()]);

		$throttler->registerAttempt('login', $remoteAddress, ['user' => $user]);
		$this->dispatcher->dispatchTyped(new LoginFailed($user, $password));

		if ($currentDelay === 0) {
			$throttler->sleepDelayOrThrowOnMax($remoteAddress, 'login');
		}
	}

	protected function supportsCookies(IRequest $request) {
		if (!is_null($request->getCookie('cookie_test'))) {
			return true;
		}
		setcookie('cookie_test', 'test', $this->timeFactory->getTime() + 3600);
		return false;
	}

	private function isTokenAuthEnforced(): bool {
		return $this->config->getSystemValueBool('token_auth_enforced', false);
	}

	protected function isTwoFactorEnforced($username) {
		$username = $this->manager->getUserNameFromLoginName($username);
		$user = $this->manager->get($username);
		if (is_null($user)) {
			$users = $this->manager->getByEmail($username);
			if (empty($users)) {
				return false;
			}
			if (count($users) !== 1) {
				return true;
			}
			$user = $users[0];
		}
		// DI not possible due to cyclic dependencies :'-/
		return Server::get(TwoFactorAuthManager::class)->isTwoFactorAuthenticated($user);
	}

	/**
	 * Check if the given 'password' is actually a device token
	 *
	 * @throws ExpiredTokenException
	 */
	private function getTokenFromPassword(string $password): ?\OCP\Authentication\Token\IToken {
		try {
			return $this->tokenProvider->getToken($password);
		} catch (ExpiredTokenException $e) {
			throw $e;
		} catch (InvalidTokenException $ex) {
			$this->logger->debug('Token is not valid: ' . $ex->getMessage(), [
				'exception' => $ex,
			]);
			return null;
		}
	}

	protected function prepareUserLogin($firstTimeLogin, $refreshCsrfToken = true) {
		if ($refreshCsrfToken) {
			// TODO: mock/inject/use non-static
			// Refresh the token
			Server::get(CsrfTokenManager::class)->refreshToken();
		}

		if ($firstTimeLogin) {
			// trigger any other initialization
			$this->dispatcher->dispatch(IUser::class . '::firstLogin', new GenericEvent($this->getUser()));
			$this->dispatcher->dispatchTyped(new UserFirstTimeLoggedInEvent($this->getUser()));
		}
	}

	/**
	 * Tries to login the user with HTTP Basic Authentication
	 *
	 * @todo do not allow basic auth if the user is 2FA enforced
	 * @param IRequest $request
	 * @param IThrottler $throttler
	 * @return boolean if the login was successful
	 */
	public function tryBasicAuthLogin(IRequest $request,
		IThrottler $throttler) {
		if (!empty($request->server['PHP_AUTH_USER']) && !empty($request->server['PHP_AUTH_PW'])) {
			try {
				if ($this->logClientIn($request->server['PHP_AUTH_USER'], $request->server['PHP_AUTH_PW'], $request, $throttler)) {
					/**
					 * Add DAV authenticated. This should in an ideal world not be
					 * necessary but the iOS App reads cookies from anywhere instead
					 * only the DAV endpoint.
					 * This makes sure that the cookies will be valid for the whole scope
					 * @see https://github.com/owncloud/core/issues/22893
					 */
					$this->session->set(
						Auth::DAV_AUTHENTICATED, $this->getUser()->getUID()
					);

					return true;
				}
				// If credentials were provided, they need to be valid, otherwise we do boom
				throw new LoginException();
			} catch (PasswordLoginForbiddenException $ex) {
				// If credentials were provided, they need to be valid, otherwise we do boom
				throw new LoginException(previous: $ex);
			}
		}
		return false;
	}

	/**
	 * Log an user in via login name and password
	 *
	 * @param string $uid
	 * @param string $password
	 * @return boolean
	 * @throws LoginException if an app canceld the login process or the user is not enabled
	 */
	private function loginWithPassword($uid, $password) {
		$user = $this->manager->checkPasswordNoLogging($uid, $password);
		if ($user === false) {
			// Password check failed
			return false;
		}

		return $this->completeLogin($user, ['loginName' => $uid, 'password' => $password], false);
	}

	/**
	 * Log an user in with a given token (id)
	 *
	 * @param string $token
	 * @return boolean
	 * @throws LoginException if an app canceled the login process or the user is not enabled
	 */
	private function loginWithToken($token) {
		try {
			$dbToken = $this->tokenProvider->getToken($token);
		} catch (InvalidTokenException $ex) {
			return false;
		}
		$uid = $dbToken->getUID();

		// When logging in with token, the password must be decrypted first before passing to login hook
		$password = '';
		try {
			$password = $this->tokenProvider->getPassword($dbToken, $token);
		} catch (PasswordlessTokenException $ex) {
			// Ignore and use empty string instead
		}

		$this->dispatcher->dispatchTyped(new BeforeUserLoggedInEvent($dbToken->getLoginName(), $password));

		$user = $this->manager->get($uid);
		if (is_null($user)) {
			// user does not exist
			return false;
		}

		return $this->completeLogin(
			$user,
			[
				'loginName' => $dbToken->getLoginName(),
				'password' => $password,
				'token' => $dbToken
			],
			false);
	}

	/**
	 * Create a new session token for the given user credentials
	 */
	public function createSessionToken(
		IRequest $request,
		string $uid,
		string $loginName,
		?string $password = null,
		int $remember = IToken::DO_NOT_REMEMBER,
		?int $expires = null,
	): bool {
		if (is_null($this->manager->get($uid))) {
			// User does not exist
			return false;
		}
		$name = isset($request->server['HTTP_USER_AGENT']) ? mb_convert_encoding($request->server['HTTP_USER_AGENT'], 'UTF-8', 'ISO-8859-1') : 'unknown browser';
		try {
			$sessionId = $this->session->getId();
			$pwd = $this->getPassword($password);
			// Make sure the current sessionId has no leftover tokens
			$this->atomic(function () use ($sessionId, $uid, $loginName, $pwd, $name, $remember, $expires): void {
				$this->tokenProvider->invalidateToken($sessionId);
				$this->tokenProvider->generateToken($sessionId, $uid, $loginName, $pwd, $name, IToken::TEMPORARY_TOKEN, $remember, expires:$expires);
			}, Server::get(IDBConnection::class));
			return true;
		} catch (SessionNotAvailableException $ex) {
			// This can happen with OCC, where a memory session is used
			// if a memory session is used, we shouldn't create a session token anyway
			return false;
		}
	}

	/**
	 * Checks if the given password is a token.
	 * If yes, the password is extracted from the token.
	 * If no, the same password is returned.
	 *
	 * @param string $password either the login password or a device token
	 * @return string|null the password or null if none was set in the token
	 */
	private function getPassword($password) {
		if (is_null($password)) {
			// This is surely no token ;-)
			return null;
		}
		try {
			$token = $this->tokenProvider->getToken($password);
			try {
				return $this->tokenProvider->getPassword($token, $password);
			} catch (PasswordlessTokenException $ex) {
				return null;
			}
		} catch (InvalidTokenException $ex) {
			return $password;
		}
	}

	/**
	 * @param IToken $dbToken
	 * @param string $token
	 * @return boolean
	 */
	private function checkTokenCredentials(IToken $dbToken, $token, array &$reason) {
		// Check whether login credentials are still valid and the user was not disabled
		// This check is performed each 5 minutes
		$lastCheck = $dbToken->getLastCheck() ? : 0;
		$now = $this->timeFactory->getTime();
		if ($lastCheck > ($now - 60 * 5)) {
			// Checked performed recently, nothing to do now
			return true;
		}

		try {
			$pwd = $this->tokenProvider->getPassword($dbToken, $token);
		} catch (InvalidTokenException $ex) {
			$reason = [
				'exception' => $ex,
			];

			// An invalid token password was used -> log user out
			return false;
		} catch (PasswordlessTokenException $ex) {
			// Token has no password
			if (!is_null($this->activeUser) && !$this->activeUser->isEnabled()) {
				$reason = [
					'exception' => $ex,
					'additional_message' => 'Passwordless token exception with no active or disabled user',
				];
				$this->tokenProvider->invalidateToken($token);
				return false;
			}

			return true;
		}

		// Invalidate token if the user is no longer active
		if (!is_null($this->activeUser) && !$this->activeUser->isEnabled()) {
			$this->tokenProvider->invalidateToken($token);
			$reason = [
				'additional_message' => 'Invalidate token as the user is no longer active',
			];
			return false;
		}

		// If the token password is no longer valid mark it as such
		if ($this->manager->checkPassword($dbToken->getLoginName(), $pwd) === false) {
			$this->tokenProvider->markPasswordInvalid($dbToken, $token);
			$reason = [
				'additional_message' => 'The token password is no longer valid and is ' . (empty($pwd) ? 'empty' : 'not empty'),
			];
			// User is logged out
			return false;
		}

		$dbToken->setLastCheck($now);
		if ($dbToken instanceof PublicKeyToken) {
			$dbToken->setLastActivity($now);
		}
		$this->tokenProvider->updateToken($dbToken);
		return true;
	}

	/**
	 * Check if the given token exists and performs password/user-enabled checks
	 *
	 * Invalidates the token if checks fail
	 *
	 * @param ?string $user The login name
	 */
	private function validateToken(string $token, ?string $user = null): bool {
		try {
			$dbToken = $this->tokenProvider->getToken($token);
		} catch (InvalidTokenException $ex) {
			$this->logger->debug('Session token is invalid because it does not exist', [
				'app' => 'core',
				'user' => $user,
				'exception' => $ex,
			]);
			return false;
		}

		if (!is_null($user) && !$this->validateTokenLoginName($user, $dbToken)) {
			return false;
		}

		$reason = [];
		if (!$this->checkTokenCredentials($dbToken, $token, $reason)) {
			$this->logger->warning('Session token credentials are invalid', [
				'app' => 'core',
				'user' => $user,
				'token name' => $dbToken->getName(),
				...$reason,
			]);
			return false;
		}

		// Update token scope
		$this->lockdownManager->setToken($dbToken);

		$this->tokenProvider->updateTokenActivity($dbToken);

		return true;
	}

	/**
	 * Check if login names match
	 */
	private function validateTokenLoginName(?string $loginName, IToken $token): bool {
		if (mb_strtolower($token->getLoginName()) !== mb_strtolower($loginName ?? '')) {
			// TODO: this makes it impossible to use different login names on browser and client
			// e.g. login by e-mail 'user@example.com' on browser for generating the token will not
			//      allow to use the client token with the login name 'user'.
			$this->logger->error('App token login name does not match', [
				'tokenLoginName' => $token->getLoginName(),
				'sessionLoginName' => $loginName,
				'app' => 'core',
				'user' => $token->getUID(),
			]);

			return false;
		}

		return true;
	}

	/**
	 * Tries to login the user with auth token header
	 *
	 * @param IRequest $request
	 * @param bool $allowOcmAccessToken Whether an OCM access token may log in
	 *                                  from a Bearer header. Only the masked
	 *                                  public share endpoints may set this.
	 * @todo check remember me cookie
	 * @return boolean
	 */
	public function tryTokenLogin(IRequest $request, bool $allowOcmAccessToken = false) {
		$authHeader = $request->getHeader('Authorization');
		$tokenFromCookie = false;
		if (str_starts_with($authHeader, 'Bearer ')) {
			$token = substr($authHeader, 7);
		} elseif ($request->getCookie($this->config->getSystemValueString('instanceid')) !== null) {
			// No auth header, let's try session id, but only if this is an existing
			// session and the request has a session cookie
			try {
				$token = $this->session->getId();
				$tokenFromCookie = true;
			} catch (SessionNotAvailableException $ex) {
				return false;
			}
		} else {
			return false;
		}

		try {
			$dbToken = $this->tokenProvider->getToken($token);
		} catch (InvalidTokenException $e) {
			// Can't really happen but better safe than sorry
			return false;
		}

		if ($dbToken instanceof PublicKeyToken
			&& $dbToken->getType() === IToken::TEMPORARY_TOKEN
			&& !$tokenFromCookie
			&& !($allowOcmAccessToken && $dbToken->getName() === IToken::OCM_ACCESS_TOKEN_NAME)) {
			// Session token but from Bearer header, not allowed
			return false;
		}

		if (!$this->loginWithToken($token)) {
			return false;
		}
		if (!$this->validateToken($token)) {
			return false;
		}

		// Set the session variable so we know this is an app password
		if ($dbToken instanceof PublicKeyToken && $dbToken->getType() === IToken::PERMANENT_TOKEN) {
			$this->session->set('app_password', $token);
		} elseif ($dbToken instanceof PublicKeyToken && $dbToken->getType() === IToken::ONETIME_TOKEN) {
			$this->tokenProvider->invalidateTokenById($dbToken->getUID(), $dbToken->getId());
			if ($request->getPathInfo() !== '/core/getapppassword-onetime') {
				return false;
			}

			$this->session->set('one_time_token', $token);
		}

		return true;
	}

	/**
	 * perform login using the magic cookie (remember login)
	 *
	 * @param string $uid the username
	 * @param string $currentToken
	 * @param string $oldSessionId
	 * @return bool
	 */
	public function loginWithCookie(string $uid, string $currentToken, string $oldSessionId) {
		$this->dispatcher->dispatchTyped(new BeforeUserLoggedInWithCookieEvent($uid));
		$user = $this->manager->get($uid);
		if (is_null($user)) {
			// user does not exist
			return false;
		}
		$userId = $user->getUID();

		$oldToken = null;
		$lookupError = null;
		if ($this->isStoredRememberMeToken($userId, $currentToken)) {
			try {
				$oldToken = $this->tokenProvider->getToken($oldSessionId);
			} catch (InvalidTokenException $ex) {
				$lookupError = $ex;
			}
		}
		if ($oldToken !== null && $oldToken->getUID() !== $userId) {
			$this->logger->warning('Tried to renew a session token belonging to a different user', [
				'app' => 'core',
				'user' => $userId,
			]);
			return false;
		}

		// A concurrent request may have rotated the cookies before this one was sent
		$successor = $oldToken === null ? $this->adoptSuccessor($userId, $currentToken, $oldSessionId) : null;
		if ($successor === null && $lookupError !== null) {
			$this->logger->error('Could not find the session token to renew', [
				'app' => 'core',
				'user' => $userId,
				'exception' => $lookupError,
			]);
			return false;
		}
		if ($successor === null && $oldToken === null) {
			$this->logger->info('Tried to log in but could not verify token', [
				'app' => 'core',
				'user' => $userId,
			]);
			return false;
		}

		// Only rotate the session once the cookie is known to be valid; failed attempts must not
		// fork the session, as concurrent requests would otherwise lose its data.
		$this->session->regenerateId();

		try {
			$sessionId = $this->session->getId();
			$login = $successor === null
				? $this->renewRememberedLogin($userId, $currentToken, $oldSessionId, $sessionId)
				: $this->continueWithSuccessor($successor, $sessionId);
		} catch (SessionNotAvailableException $ex) {
			$this->logger->critical('Could not renew session token for {uid} because the session is unavailable', [
				'app' => 'core',
				'uid' => $userId,
				'user' => $userId,
			]);
			return false;
		} catch (InvalidTokenException $ex) {
			$this->logger->error('Renewing session token failed: ' . $ex->getMessage(), [
				'app' => 'core',
				'user' => $userId,
				'exception' => $ex,
			]);
			return false;
		}
		$sessionToken = $login['sessionToken'];

		$this->setMagicInCookie($userId, $login['rememberMeToken']);

		//login
		$this->setUser($user);
		$this->setLoginName($sessionToken->getLoginName());
		$this->setToken($sessionToken->getId());
		$this->lockdownManager->setToken($sessionToken);
		$user->updateLastLoginTimestamp();
		$password = null;
		try {
			$password = $this->tokenProvider->getPassword($sessionToken, $sessionId);
		} catch (PasswordlessTokenException $ex) {
			// Ignore
		}
		$this->dispatcher->dispatchTyped(new UserLoggedInWithCookieEvent($user, $password));
		$this->manager->emit('\OC\User', 'postRememberedLogin', [$user, $password]);
		return true;
	}

	/**
	 * Renew the session token and rotate the remember-me token, or continue with the successor of a concurrent
	 * request that renewed the session token first
	 *
	 * @return array{sessionToken: OCPIToken, rememberMeToken: string}
	 * @throws InvalidTokenException
	 */
	private function renewRememberedLogin(
		string $userId,
		#[\SensitiveParameter]
		string $currentToken,
		#[\SensitiveParameter]
		string $oldSessionId,
		#[\SensitiveParameter]
		string $sessionId,
	): array {
		try {
			// A concurrent renewal of the same session token waits until this one has published the successor
			$login = $this->atomic(fn (): array => [
				'sessionToken' => $this->tokenProvider->renewSessionToken($oldSessionId, $sessionId),
				'rememberMeToken' => $this->rotateRememberMeToken($userId, $currentToken, $oldSessionId, $sessionId),
			], Server::get(IDBConnection::class));
		} catch (InvalidTokenException $ex) {
			$successor = $this->adoptSuccessor($userId, $currentToken, $oldSessionId) ?? throw $ex;
			return $this->continueWithSuccessor($successor, $sessionId);
		}
		$this->logger->debug('Session token replaced', [
			'app' => 'core',
			'user' => $userId,
		]);
		return $login;
	}

	/**
	 * Continue with the successor of a concurrent request. Its session token stays valid, as its request may still
	 * be on its way, so this session gets a copy of it.
	 *
	 * @param array{token: string, sessionId: string} $successor
	 * @return array{sessionToken: OCPIToken, rememberMeToken: string}
	 * @throws InvalidTokenException
	 */
	private function continueWithSuccessor(
		#[\SensitiveParameter]
		array $successor,
		#[\SensitiveParameter]
		string $sessionId,
	): array {
		return [
			'sessionToken' => $this->tokenProvider->copySessionToken($successor['sessionId'], $sessionId),
			'rememberMeToken' => $successor['token'],
		];
	}

	/**
	 * Replace the used remember-me token, after publishing the successor for the requests that are on their way
	 *
	 * @return string The new remember-me token
	 */
	private function rotateRememberMeToken(
		string $userId,
		#[\SensitiveParameter]
		string $currentToken,
		#[\SensitiveParameter]
		string $oldSessionId,
		#[\SensitiveParameter]
		string $sessionId,
	): string {
		$newToken = $this->random->generate(32);
		$this->userConfig->setValueString($userId, 'login_token', $newToken, (string)$this->timeFactory->getTime());
		$this->rememberMeTokenGrace->remember($currentToken, $oldSessionId, $newToken, $sessionId);
		$this->userConfig->deleteUserConfig($userId, 'login_token', $currentToken);
		$this->logger->debug('Remember-me token replaced', [
			'app' => 'core',
			'user' => $userId,
		]);

		return $newToken;
	}

	/**
	 * What a concurrent request that sent the same cookies replaced them with, within the grace period
	 *
	 * @return ?array{token: string, sessionId: string}
	 */
	private function adoptSuccessor(
		string $userId,
		#[\SensitiveParameter]
		string $rotatedToken,
		#[\SensitiveParameter]
		string $rotatedSessionId,
	): ?array {
		$successor = $this->rememberMeTokenGrace->resolve($rotatedToken, $rotatedSessionId);
		if ($successor === null) {
			return null;
		}
		// Revoking or wiping the login, or a password change in another session, invalidates the successor's session token
		if (!$this->isRememberedSessionTokenOf($userId, $successor['sessionId'])) {
			return null;
		}
		// A logout deletes the successor. The keys cached by this request may be older than the successor itself.
		$this->userConfig->clearCache($userId);
		if (!$this->isStoredRememberMeToken($userId, $successor['token'])) {
			return null;
		}

		$this->logger->debug('Continuing with the remember-me login of a concurrent request', [
			'app' => 'core',
			'user' => $userId,
		]);
		return $successor;
	}

	/**
	 * Whether $rememberMeToken is a stored remember-me token of $userId and the session token of $sessionId is a
	 * remembered browser session of $userId as well
	 */
	private function isRememberedSession(
		string $userId,
		#[\SensitiveParameter]
		string $rememberMeToken,
		#[\SensitiveParameter]
		string $sessionId,
	): bool {
		// Checked first, so only the id of an existing user reaches the user config
		return $this->isRememberedSessionTokenOf($userId, $sessionId)
			&& $this->isStoredRememberMeToken($userId, $rememberMeToken);
	}

	/**
	 * Whether the session token of $sessionId is a remembered browser session of $userId
	 */
	private function isRememberedSessionTokenOf(
		string $userId,
		#[\SensitiveParameter]
		string $sessionId,
	): bool {
		try {
			$sessionToken = $this->tokenProvider->getToken($sessionId);
		} catch (InvalidTokenException) {
			return false;
		}
		return $sessionToken->getUID() === $userId
			&& $sessionToken->getType() === IToken::TEMPORARY_TOKEN
			&& $sessionToken->getRemember() === IToken::REMEMBER;
	}

	/**
	 * @param IUser $user
	 */
	public function createRememberMeToken(IUser $user) {
		$token = $this->random->generate(32);
		$this->userConfig->setValueString($user->getUID(), 'login_token', $token, (string)$this->timeFactory->getTime());
		$this->setMagicInCookie($user->getUID(), $token);
	}

	/**
	 * logout the user from the session
	 */
	#[\Override]
	public function logout() {
		$user = $this->getUser();
		$this->dispatcher->dispatchTyped(new BeforeUserLoggedOutEvent($user));
		if ($user !== null) {
			try {
				$token = $this->session->getId();
				$this->tokenProvider->invalidateToken($token);
				$this->logger->debug('Session token invalidated before logout', [
					'user' => $user->getUID(),
				]);
			} catch (SessionNotAvailableException $ex) {
			}
		}
		$this->logger->debug('Logging out', [
			'user' => $user === null ? null : $user->getUID(),
		]);
		$this->setUser(null);
		$this->setLoginName(null);
		$this->setToken(null);
		$this->unsetMagicInCookie();
		$this->session->clear();
		$this->dispatcher->dispatchTyped(new UserLoggedOutEvent($user));
	}

	/**
	 * End a session that was inactive for longer than session_lifetime. A request that proves a remembered
	 * login only loses its session data: its session token moves to the new session id.
	 */
	public function endInactiveSession(): void {
		$login = $this->rememberedLogin(Server::get(IRequest::class));
		if ($login === null) {
			$this->logout();
			return;
		}

		$this->logger->debug('Ending inactive session, keeping the remembered login', [
			'app' => 'core',
			'user' => $login['username'],
		]);
		try {
			$oldSessionId = $this->session->getId();
			$this->setVolatileActiveUser(null);
			$this->session->clear();
			// Otherwise the remember-me login continues where a concurrent request moved the session token to
			if (!$login['moved']) {
				$this->moveSessionToken($oldSessionId, $this->session->getId());
			}
		} catch (InvalidTokenException|SessionNotAvailableException) {
			// Without the session token the request continues as an anonymous one
		}
	}

	/**
	 * The remembered login a request proves for the session token of the current session
	 *
	 * @return ?array{username: string, token: string, moved: bool} The remember-me cookies, and whether a concurrent
	 *                                                              request that ended the same session already moved
	 *                                                              its session token
	 */
	private function rememberedLogin(IRequest $request): ?array {
		if ($this->tokenProvider === null) {
			return null;
		}
		try {
			$sessionId = $this->session->getId();
		} catch (SessionNotAvailableException) {
			return null;
		}
		$cookies = $this->rememberMeCookiesFor($request, $sessionId);
		if ($cookies === null) {
			return null;
		}
		// No user backend is set up yet when a session times out, so the session token tells whose session it is
		if ($this->isRememberedSession($cookies['username'], $cookies['token'], $sessionId)) {
			return $cookies + ['moved' => false];
		}
		if ($this->adoptSuccessor($cookies['username'], $cookies['token'], $sessionId) !== null) {
			return $cookies + ['moved' => true];
		}
		return null;
	}

	/**
	 * Set cookie value to use in next page load
	 *
	 * @param string $username username to be set
	 * @param string $token
	 */
	public function setMagicInCookie($username, $token) {
		$secureCookie = Server::get(IRequest::class)->getServerProtocol() === 'https';
		$webRoot = \OC::$WEBROOT;
		if ($webRoot === '') {
			$webRoot = '/';
		}
		$domain = $this->config->getSystemValueString('cookie_domain');

		$maxAge = $this->config->getSystemValueInt('remember_login_cookie_lifetime', 60 * 60 * 24 * 15);
		CookieHelper::setCookie(
			'nc_username',
			$username,
			$maxAge,
			$webRoot,
			$domain,
			$secureCookie,
			true,
			CookieHelper::SAMESITE_LAX
		);
		CookieHelper::setCookie(
			'nc_token',
			$token,
			$maxAge,
			$webRoot,
			$domain,
			$secureCookie,
			true,
			CookieHelper::SAMESITE_LAX
		);
		try {
			CookieHelper::setCookie(
				'nc_session_id',
				$this->session->getId(),
				$maxAge,
				$webRoot,
				$domain,
				$secureCookie,
				true,
				CookieHelper::SAMESITE_LAX
			);
		} catch (SessionNotAvailableException $ex) {
			// ignore
		}
	}

	/**
	 * Move the session token of the regenerated session $oldSessionId to $sessionId, and point the remember-me
	 * cookies of the request at it
	 *
	 * @throws InvalidTokenException
	 */
	public function moveSessionToken(
		#[\SensitiveParameter]
		string $oldSessionId,
		#[\SensitiveParameter]
		string $sessionId,
	): void {
		// A concurrent renewal of the same session token waits until this one has published where it moved to
		$this->atomic(function () use ($oldSessionId, $sessionId): void {
			$this->tokenProvider->renewSessionToken($oldSessionId, $sessionId);
			$this->renewMagicSessionId($oldSessionId, $sessionId);
		}, Server::get(IDBConnection::class));
	}

	/**
	 * Point the remember-me cookie at the regenerated session id, so cookie
	 * login can still find the token that was renewed along with it.
	 */
	private function renewMagicSessionId(
		#[\SensitiveParameter]
		string $oldSessionId,
		#[\SensitiveParameter]
		string $sessionId,
	): void {
		$cookies = $this->rememberMeCookiesFor(Server::get(IRequest::class), $oldSessionId);
		if ($cookies === null) {
			return;
		}
		$this->publishMovedSessionToken($cookies, $oldSessionId, $sessionId);
		$this->setMagicInCookie($cookies['username'], $cookies['token']);
	}

	/**
	 * Let requests that still send the old session id continue with the session token of $sessionId
	 *
	 * @param array{username: string, token: string} $cookies
	 */
	private function publishMovedSessionToken(
		#[\SensitiveParameter]
		array $cookies,
		#[\SensitiveParameter]
		string $oldSessionId,
		#[\SensitiveParameter]
		string $sessionId,
	): void {
		// Only a session token that a request with the old cookies can continue with
		if ($this->isRememberedSession($cookies['username'], $cookies['token'], $sessionId)) {
			$this->rememberMeTokenGrace->remember($cookies['token'], $oldSessionId, $cookies['token'], $sessionId);
		}
	}

	private function isStoredRememberMeToken(string $uid, string $token): bool {
		return in_array($token, $this->userConfig->getKeys($uid, 'login_token'), true);
	}

	/**
	 * The remember-me cookies of a request, if they point at the session $sessionId
	 *
	 * @return ?array{username: string, token: string}
	 */
	private function rememberMeCookiesFor(IRequest $request, string $sessionId): ?array {
		$username = $request->getCookie('nc_username');
		$token = $request->getCookie('nc_token');
		if (!is_string($username) || !is_string($token) || $request->getCookie('nc_session_id') !== $sessionId) {
			return null;
		}
		return ['username' => $username, 'token' => $token];
	}

	/**
	 * Remove cookie for "remember username"
	 */
	public function unsetMagicInCookie() {
		//TODO: DI for cookies and IRequest
		$secureCookie = Server::get(IRequest::class)->getServerProtocol() === 'https';
		$domain = $this->config->getSystemValueString('cookie_domain');

		unset($_COOKIE['nc_username']); //TODO: DI
		unset($_COOKIE['nc_token']);
		unset($_COOKIE['nc_session_id']);
		setcookie('nc_username', '', $this->timeFactory->getTime() - 3600, OC::$WEBROOT, $domain, $secureCookie, true);
		setcookie('nc_token', '', $this->timeFactory->getTime() - 3600, OC::$WEBROOT, $domain, $secureCookie, true);
		setcookie('nc_session_id', '', $this->timeFactory->getTime() - 3600, OC::$WEBROOT, $domain, $secureCookie, true);
		// old cookies might be stored under /webroot/ instead of /webroot
		// and Firefox doesn't like it!
		setcookie('nc_username', '', $this->timeFactory->getTime() - 3600, OC::$WEBROOT . '/', $domain, $secureCookie, true);
		setcookie('nc_token', '', $this->timeFactory->getTime() - 3600, OC::$WEBROOT . '/', $domain, $secureCookie, true);
		setcookie('nc_session_id', '', $this->timeFactory->getTime() - 3600, OC::$WEBROOT . '/', $domain, $secureCookie, true);
	}

	/**
	 * Update password of the browser session token if there is one
	 *
	 * @param string $password
	 */
	public function updateSessionTokenPassword($password) {
		try {
			$sessionId = $this->session->getId();
			$token = $this->tokenProvider->getToken($sessionId);
			$this->tokenProvider->setPassword($token, $sessionId, $password);
		} catch (SessionNotAvailableException $ex) {
			// Nothing to do
		} catch (InvalidTokenException $ex) {
			// Nothing to do
		}
	}

	public function updateTokens(string $uid, string $password) {
		$this->tokenProvider->updatePasswords($uid, $password);
	}
}
