<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
use OC\Authentication\Token\IProvider;
use OC\SystemConfig;
use OC\User\Database;
use OC\User\DisabledUserException;
use OCP\Authentication\Exceptions\InvalidTokenException;
use OCP\Authentication\Exceptions\WipeTokenException;
use OCP\Authentication\IApacheBackend;
use OCP\Authentication\IProvideUserSecretBackend;
use OCP\Authentication\Token\IToken;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Server;
use OCP\Session\Exceptions\SessionNotAvailableException;
use OCP\User\Backend\ICustomLogout;
use OCP\User\Events\BeforeUserLoggedInEvent;
use OCP\User\Events\UserLoggedInEvent;
use OCP\UserInterface;
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
 */
class OC_User {
	private static $_setupedBackends = [];

	// bool, stores if a user want to access a resource anonymously, e.g if they open a public link
	private static $incognitoMode = false;

	/**
	 * Adds the backend to the list of used backends
	 *
	 * @param string|UserInterface $backend default: database The backend to use for user management
	 * @return bool
	 * @deprecated 32.0.0 Use IUserManager::registerBackend instead
	 *
	 * Set the User Authentication Module
	 */
	public static function useBackend($backend = 'database') {
		if ($backend instanceof UserInterface) {
			Server::get(IUserManager::class)->registerBackend($backend);
		} else {
			// You'll never know what happens
			if ($backend === null || !is_string($backend)) {
				$backend = 'database';
			}

			// Load backend
			switch ($backend) {
				case 'database':
				case 'mysql':
				case 'sqlite':
					Server::get(LoggerInterface::class)->debug('Adding user backend ' . $backend . '.', ['app' => 'core']);
					Server::get(IUserManager::class)->registerBackend(new Database());
					break;
				case 'dummy':
					Server::get(IUserManager::class)->registerBackend(new \Test\Util\User\Dummy());
					break;
				default:
					Server::get(LoggerInterface::class)->debug('Adding default user backend ' . $backend . '.', ['app' => 'core']);
					$className = 'OC_USER_' . strtoupper($backend);
					Server::get(IUserManager::class)->registerBackend(new $className());
					break;
			}
		}
		return true;
	}

	/**
	 * remove all used backends
	 * @deprecated 32.0.0 Use IUserManager::clearBackends instead
	 */
	public static function clearBackends() {
		Server::get(IUserManager::class)->clearBackends();
	}

	/**
	 * setup the configured backends in config.php
	 * @suppress PhanDeprecatedFunction
	 */
	public static function setupBackends() {
		OC_App::loadApps(['prelogin']);
		$backends = Server::get(SystemConfig::class)->getValue('user_backends', []);
		if (isset($backends['default']) && !$backends['default']) {
			// clear default backends
			self::clearBackends();
		}
		foreach ($backends as $i => $config) {
			if (!is_array($config)) {
				continue;
			}
			$class = $config['class'];
			$arguments = $config['arguments'];
			if (class_exists($class)) {
				if (!in_array($i, self::$_setupedBackends)) {
					// make a reflection object
					$reflectionObj = new ReflectionClass($class);

					// use Reflection to create a new instance, using the $args
					$backend = $reflectionObj->newInstanceArgs($arguments);
					self::useBackend($backend);
					self::$_setupedBackends[] = $i;
				} else {
					Server::get(LoggerInterface::class)->debug('User backend ' . $class . ' already initialized.', ['app' => 'core']);
				}
			} else {
				Server::get(LoggerInterface::class)->error('User backend ' . $class . ' not found.', ['app' => 'core']);
			}
		}
	}

	/**
	 * Try to login a user, assuming authentication
	 * has already happened (e.g. via Single Sign On).
	 *
	 * Log in a user and regenerate a new session.
	 */
	public static function loginWithApache(IApacheBackend $backend): bool {
		$uid = $backend->getCurrentUserId();
		$run = true;
		OC_Hook::emit('OC_User', 'pre_login', ['run' => &$run, 'uid' => $uid, 'backend' => $backend]);

		if ($uid) {
			if (self::getUser() !== $uid) {
				self::setUserId($uid);
				/** @var \OC\User\Session $userSession */
				$userSession = Server::get(IUserSession::class);

				/** @var IEventDispatcher $dispatcher */
				$dispatcher = Server::get(IEventDispatcher::class);

				if ($userSession->getUser() && !$userSession->getUser()->isEnabled()) {
					$message = \OC::$server->getL10N('lib')->t('Account disabled');
					throw new DisabledUserException($message);
				}
				$userSession->setLoginName($uid);
				$request = Server::get(IRequest::class);
				$password = null;
				if ($backend instanceof IProvideUserSecretBackend) {
					$password = $backend->getCurrentUserSecret();
				}

				/** @var IEventDispatcher $dispatcher */
				$dispatcher->dispatchTyped(new BeforeUserLoggedInEvent($uid, $password, $backend));

				$userSession->createSessionToken($request, $uid, $uid, $password);
				$userSession->createRememberMeToken($userSession->getUser());

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

				// setup the filesystem
				OC_Util::setupFS($uid);
				// first call the post_login hooks, the login-process needs to be
				// completed before we can safely create the users folder.
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

				//trigger creation of user home and /files folder
				\OC::$server->getUserFolder($uid);
			}
			return true;
		}
		return false;
	}

	/**
	 * Verify with Apache whether user is authenticated.
	 *
	 * @return bool|null
	 *                   true: authenticated
	 *                   false: not authenticated
	 *                   null: not handled / no backend available
	 */
	public static function handleApacheAuth(): ?bool {
		$backend = self::findFirstActiveUsedBackend();
		if ($backend) {
			OC_App::loadApps();

			//setup extra user backends
			self::setupBackends();
			/** @var \OC\User\Session $session */
			$session = Server::get(IUserSession::class);
			$session->unsetMagicInCookie();

			return self::loginWithApache($backend);
		}

		return null;
	}


	/**
	 * Sets user id for session and triggers emit
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
	 */
	public static function setIncognitoMode(bool $status): void {
		self::$incognitoMode = $status;
	}

	/**
	 * Get incognito mode status
	 */
	public static function isIncognitoMode(): bool {
		return self::$incognitoMode;
	}

	/**
	 * Returns the current logout URL valid for the currently logged-in user
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
	 */
	public static function isAdminUser(string $uid): bool {
		$user = Server::get(IUserManager::class)->get($uid);
		$isAdmin = $user && Server::get(IGroupManager::class)->isAdmin($user->getUID());
		return $isAdmin && self::$incognitoMode === false;
	}


	/**
	 * get the user id of the user currently logged in.
	 *
	 * @return string|false uid or false
	 */
	public static function getUser(): string|false {
		$uid = Server::get(ISession::class)?->get('user_id');
		if (!is_null($uid) && self::$incognitoMode === false) {
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
