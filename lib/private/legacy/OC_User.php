<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
use OC\Authentication\Token\IProvider;
use OC\SystemConfig;
use OC\User\DisabledUserException;
use OC\User\Session;
use OCP\App\IAppManager;
use OCP\Authentication\Exceptions\InvalidTokenException;
use OCP\Authentication\Exceptions\WipeTokenException;
use OCP\Authentication\IApacheBackend;
use OCP\Authentication\IProvideUserSecretBackend;
use OCP\Authentication\Token\IToken;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\IRootFolder;
use OCP\Files\ISetupManager;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\L10N\IFactory;
use OCP\Server;
use OCP\Session\Exceptions\SessionNotAvailableException;
use OCP\User\Backend\ICustomLogout;
use OCP\User\Events\BeforeUserLoggedInEvent;
use OCP\User\Events\UserLoggedInEvent;
use OCP\Util;
use Psr\Log\LoggerInterface;

/**
 * This class provides wrapper methods for user management. Multiple backends are
 * supported. User management operations are delegated to the configured backend for
 * execution.
 *
 * Note that &run is deprecated and won't work anymore.
 *
 * Hooks provided:
 *   pre_createUser(&run, uid, password)
 *   post_createUser(uid, password)
 *   pre_deleteUser(&run, uid)
 *   post_deleteUser(uid)
 *   pre_setPassword(&run, uid, password, recoveryPassword)
 *   post_setPassword(uid, password, recoveryPassword)
 *   pre_login(&run, uid, password)
 *   post_login(uid)
 *   logout()
 *
 * @deprecated 34.0.0
 */
class OC_User {
	private static $_setupedBackends = [];

	/**
	 * Set up the configured backends in config.php.
	 *
	 * @suppress PhanDeprecatedFunction
	 * @deprecated 34.0.0 This is internal, not to be used by apps
	 */
	public static function setupBackends(): void {
		if (!Server::get(SystemConfig::class)->getValue('installed', false)) {
			return;
		}
		Server::get(IAppManager::class)->loadApps(['prelogin']);

		$backends = Server::get(SystemConfig::class)->getValue('user_backends', []);
		if (isset($backends['default']) && !$backends['default']) {
			// clear default backends
			Server::get(IUserManager::class)->clearBackends();
		}
		foreach ($backends as $i => $config) {
			if (!is_array($config)) {
				continue;
			}
			$class = $config['class'];
			$arguments = $config['arguments'];
			if (!class_exists($class)) {
				Server::get(LoggerInterface::class)->error('User backend ' . $class . ' not found.', ['app' => 'core']);
			}
			if (in_array($i, self::$_setupedBackends)) {
				Server::get(LoggerInterface::class)->debug('User backend ' . $class . ' already initialized.', ['app' => 'core']);
			}
			// make a reflection object
			$reflectionObj = new ReflectionClass($class);

			// use Reflection to create a new instance, using the $args
			$backend = $reflectionObj->newInstanceArgs($arguments);
			Server::get(IUserManager::class)->registerBackend($backend);
			self::$_setupedBackends[] = $i;
		}
	}

	/**
	 * Try to log in a user, assuming authentication
	 * has already happened (e.g. via Single Sign On).
	 *
	 * Log in a user and regenerate a new session.
	 *
	 * @deprecated 34.0.0 This is internal, not to be used by apps
	 */
	public static function loginWithApache(IApacheBackend $backend): bool {
		$uid = $backend->getCurrentUserId();
		$run = true;
		OC_Hook::emit('OC_User', 'pre_login', ['run' => &$run, 'uid' => $uid, 'backend' => $backend]);

		if (!$uid) {
			return false;
		}

		$userSession = Server::get(IUserSession::class);
		$userManager = Server::get(IUserManager::class);
		$dispatcher = Server::get(IEventDispatcher::class);

		$user = $userSession->getUser();
		if ($user !== null && $user->getUID() === $uid) {
			return true;
		}

		$user = $userManager->get($uid);
		if ($user !== null) {
			$userSession->setUser($user);
		} else {
			Server::get(ISession::class)->set('user_id', $uid);
		}

		if (!$user->isEnabled()) {
			$message = Server::get(IFactory::class)->get('lib')->t('Account disabled');
			throw new DisabledUserException($message);
		}

		$userSession->setLoginName($uid);
		$request = Server::get(IRequest::class);
		$password = null;
		if ($backend instanceof IProvideUserSecretBackend) {
			$password = $backend->getCurrentUserSecret();
		}

		$dispatcher->dispatchTyped(new BeforeUserLoggedInEvent($uid, $password, $backend));

		$userSession->createSessionToken($request, $uid, $uid, $password);
		$userSession->createRememberMeToken($user);

		if (empty($password)) {
			$tokenProvider = Server::get(IProvider::class);
			try {
				$token = $tokenProvider->getToken($userSession->getSession()->getId());
				$token->setScope([
					IToken::SCOPE_SKIP_PASSWORD_VALIDATION => true,
					IToken::SCOPE_FILESYSTEM => true,
				]);
				$tokenProvider->updateToken($token);
			} catch (InvalidTokenException|WipeTokenException|SessionNotAvailableException) {
				// swallow the exceptions as we do not deal with them here
				// simply skip updating the token when is it missing
			}
		}

		// Set up the filesystem
		Server::get(ISetupManager::class)->setupForUser($user);
		// first call the post_login hooks, the login-process needs to be
		// completed before we can safely create the user's folder.
		// For example encryption needs to initialize the users keys first
		// before we can create the user folder with the skeleton files
		OC_Hook::emit(
			'OC_User',
			'post_login',
			[
				'uid' => $uid,
				'password' => $password,
				'isTokenLogin' => false,
			]
		);
		$dispatcher->dispatchTyped(new UserLoggedInEvent(
			Server::get(IUserManager::class)->get($uid),
			$uid,
			null,
			false)
		);

		// trigger creation of user home and /files folder
		Server::get(IRootFolder::class)->getUserFolder($uid);
		return true;
	}

	/**
	 * Verify with Apache whether user is authenticated.
	 *
	 * @return bool|null
	 *                   true: authenticated
	 *                   false: not authenticated
	 *                   null: not handled / no backend available
	 *
	 * @deprecated 34.0.0 This is internal, not to be used by apps
	 */
	public static function handleApacheAuth(): ?bool {
		$backend = self::findFirstActiveUsedBackend();
		if ($backend) {
			Server::get(IAppManager::class)->loadApps();

			// set up extra user backends
			self::setupBackends();
			/** @var Session $session */
			$session = Server::get(IUserSession::class);
			$session->unsetMagicInCookie();

			return self::loginWithApache($backend);
		}

		return null;
	}


	/**
	 * Sets user id for session and triggers emit
	 * @deprecated 34.0.0 Use TestCase::setUserId in your test instead
	 */
	public static function setUserId(?string $uid): void {
		$userSession = Server::get(IUserSession::class);
		$userManager = Server::get(IUserManager::class);
		if ($user = $userManager->get($uid)) {
			$userSession->setUser($user);
		} else {
			Server::get(ISession::class)->set('user_id', $uid);
		}
	}

	/**
	 * Set incognito mode, e.g. if a user wants to open a public link
	 * @deprecated 34.0.0 Use IUserSession::setIncognitoMode
	 */
	public static function setIncognitoMode(bool $status): void {
		Server::get(IUserSession::class)->setIncognitoMode($status);
	}

	/**
	 * Get incognito mode status
	 * @deprecated 34.0.0 Use IUserSession::isIncognitoMode
	 */
	public static function isIncognitoMode(): bool {
		return Server::get(IUserSession::class)->isIncognitoMode();
	}

	/**
	 * Returns the current logout URL valid for the currently logged-in user
	 * @deprecated 34.0.0
	 */
	public static function getLogoutUrl(IURLGenerator $urlGenerator): string {
		$backend = self::findFirstActiveUsedBackend();
		if ($backend) {
			return $backend->getLogoutUrl();
		}

		$user = Server::get(IUserSession::class)->getUser();
		if ($user instanceof IUser) {
			$backend = $user->getBackend();
			if ($backend instanceof ICustomLogout) {
				return $backend->getLogoutUrl();
			}
		}

		$logoutUrl = $urlGenerator->linkToRoute('core.login.logout');
		$logoutUrl .= '?requesttoken=' . urlencode(Util::callRegister());

		return $logoutUrl;
	}

	/**
	 * Check if the user is an admin user
	 *
	 * @param string $uid uid of the admin
	 * @deprecated 34.0.0 Use IGroupManager::isAdmin instead
	 */
	public static function isAdminUser(string $uid): bool {
		$user = Server::get(IUserManager::class)->get($uid);
		$isAdmin = $user && Server::get(IGroupManager::class)->isAdmin($user->getUID());
		return $isAdmin && !Server::get(IUserSession::class)->isIncognitoMode();
	}


	/**
	 * get the user id of the user currently logged in.
	 *
	 * @return string|false uid or false
	 * @deprecated 34.0.0 Use IUserSession::getUser instead
	 */
	public static function getUser(): string|false {
		$uid = Server::get(ISession::class)?->get('user_id');
		if (!is_null($uid) && !Server::get(IUserSession::class)->isIncognitoMode()) {
			return $uid;
		} else {
			return false;
		}
	}

	/**
	 * Set password
	 *
	 * @param string $uid The username
	 * @param string $password The new password
	 * @param string $recoveryPassword for the encryption app to reset encryption keys
	 *
	 * Change the password of a user
	 * @deprecated 34.0.0 Use IUserManager::setPassword instead
	 */
	public static function setPassword(string $uid, string $password, ?string $recoveryPassword = null): bool {
		$user = Server::get(IUserManager::class)->get($uid);
		if ($user) {
			return $user->setPassword($password, $recoveryPassword);
		} else {
			return false;
		}
	}

	/**
	 * Returns the first active backend from self::$_usedBackends.
	 *
	 * @return IApacheBackend|null if no backend active, otherwise OCP\Authentication\IApacheBackend
	 */
	private static function findFirstActiveUsedBackend(): ?IApacheBackend {
		foreach (Server::get(IUserManager::class)->getBackends() as $backend) {
			if ($backend instanceof IApacheBackend) {
				if ($backend->isSessionActive()) {
					return $backend;
				}
			}
		}

		return null;
	}
}
