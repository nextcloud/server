<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\User_LDAP;

use OC\ServerNotAvailableException;
use OC\User\Backend;
use OC\User\NoUserException;
use OCA\User_LDAP\Exceptions\NotOnLDAP;
use OCA\User_LDAP\User\DeletedUsersIndex;
use OCA\User_LDAP\User\OfflineUser;
use OCA\User_LDAP\User\User;
use OCP\IConfig;
use OCP\IUserBackend;
use OCP\IUserSession;
use OCP\Notification\IManager as INotificationManager;
use OCP\User\Backend\ICountMappedUsersBackend;
use OCP\User\Backend\ICountUsersBackend;
use OCP\User\Backend\IProvideEnabledStateBackend;
use OCP\UserInterface;
use Psr\Log\LoggerInterface;

class User_LDAP extends BackendUtility implements IUserBackend, UserInterface, IUserLDAP, ICountUsersBackend, ICountMappedUsersBackend, IProvideEnabledStateBackend {
	public function __construct(
		protected Access $access,
		protected IConfig $ocConfig,
		protected INotificationManager $notificationManager,
		protected IUserSession $userSession,
		protected UserPluginManager $userPluginManager,
		protected LoggerInterface $logger,
		protected DeletedUsersIndex $deletedUsersIndex,
	) {
		parent::__construct($access);
	}

	/**
	 * checks whether the user is allowed to change his avatar in Nextcloud
	 *
	 * @throws \Exception
	 */
	public function canChangeAvatar(string $uid): bool {
		if ($this->userPluginManager->implementsActions(Backend::PROVIDE_AVATAR)) {
			return $this->userPluginManager->canChangeAvatar($uid);
		}

		if (!$this->implementsActions(Backend::PROVIDE_AVATAR)) {
			return true;
		}

		$user = $this->access->userManager->get($uid);
		if (!$user instanceof User) {
			return false;
		}
		$imageData = $user->getAvatarImage();
		if ($imageData === false) {
			return true;
		}
		return !$user->updateAvatar(true);
	}

	/**
	 * Return the username for the given login name, if available
	 *
	 * @throws \Exception
	 */
	public function loginName2UserName(string $loginName): string|false {
		$cacheKey = 'loginName2UserName-' . $loginName;
		$username = $this->access->connection->getFromCache($cacheKey);

		if ($username !== null) {
			return $username;
		}

		try {
			$ldapRecord = $this->getLDAPUserByLoginName($loginName);
			$user = $this->access->userManager->get($ldapRecord['dn'][0]);
			if ($user === null || $user instanceof OfflineUser) {
				// this path is not really possible, however get() is documented
				// to return User, OfflineUser or null so we are very defensive here.
				$this->access->connection->writeToCache($cacheKey, false);
				return false;
			}
			$username = $user->getUsername();
			$this->access->connection->writeToCache($cacheKey, $username);
			return $username;
		} catch (NotOnLDAP $e) {
			$this->access->connection->writeToCache($cacheKey, false);
			return false;
		}
	}

	/**
	 * returns the username for the given LDAP DN, if available
	 *
	 */
	public function dn2UserName(string $dn): string|false {
		return $this->access->dn2username($dn);
	}

	/**
	 * returns an LDAP record based on a given login name
	 *
	 * @throws NotOnLDAP
	 */
	public function getLDAPUserByLoginName(string $loginName): array {
		//find out dn of the user name
		$attrs = $this->access->userManager->getAttributes();
		$users = $this->access->fetchUsersByLoginName($loginName, $attrs);
		if (count($users) < 1) {
			throw new NotOnLDAP('No user available for the given login name on ' .
				$this->access->connection->ldapHost . ':' . $this->access->connection->ldapPort);
		}
		return $users[0];
	}

	/**
	 * Check if the password is correct without logging in the user
	 *
	 */
	public function checkPassword(string $uid, string $password): false|string {
		try {
			$ldapRecord = $this->getLDAPUserByLoginName($uid);
		} catch (NotOnLDAP $e) {
			$this->logger->debug(
				$e->getMessage(),
				['app' => 'user_ldap', 'exception' => $e]
			);
			return false;
		}
		$dn = $ldapRecord['dn'][0];
		$user = $this->access->userManager->get($dn);

		if (!$user instanceof User) {
			$this->logger->warning(
				'LDAP Login: Could not get user object for DN ' . $dn .
				'. Maybe the LDAP entry has no set display name attribute?',
				['app' => 'user_ldap']
			);
			return false;
		}
		if ($user->getUsername() !== false) {
			//are the credentials OK?
			if (!$this->access->areCredentialsValid($dn, $password)) {
				return false;
			}

			$this->access->cacheUserExists($user->getUsername());
			$user->processAttributes($ldapRecord);
			$user->markLogin();

			return $user->getUsername();
		}

		return false;
	}

	/**
	 * Set password
  	 *
	 */
	public function setPassword(string $uid, string $password): bool {
		if ($this->userPluginManager->implementsActions(Backend::SET_PASSWORD)) {
			return $this->userPluginManager->setPassword($uid, $password);
		}

		$user = $this->access->userManager->get($uid);

		if (!$user instanceof User) {
			throw new \Exception('LDAP setPassword: Could not get user object for uid ' . $uid .
				'. Maybe the LDAP entry has no set display name attribute?');
		}
		if ($user->getUsername() !== false && $this->access->setPassword($user->getDN(), $password)) {
			$ldapDefaultPPolicyDN = $this->access->connection->ldapDefaultPPolicyDN;
			$turnOnPasswordChange = $this->access->connection->turnOnPasswordChange;
			if (!empty($ldapDefaultPPolicyDN) && ((int)$turnOnPasswordChange === 1)) {
				//remove last password expiry warning if any
				$notification = $this->notificationManager->createNotification();
				$notification->setApp('user_ldap')
					->setUser($uid)
					->setObject('pwd_exp_warn', $uid)
				;
				$this->notificationManager->markProcessed($notification);
			}
			return true;
		}

		return false;
	}

	/**
	 * Get a list of all users
	 *
	 */
	public function getUsers(string $search = '', int $limit = 10, int $offset = 0): array {
		$search = $this->access->escapeFilterPart($search, true);
		$cachekey = 'getUsers-'.$search.'-'.$limit.'-'.$offset;

		//check if users are cached, if so return
		$ldap_users = $this->access->connection->getFromCache($cachekey);
		if (!is_null($ldap_users)) {
			return $ldap_users;
		}

		// if we'd pass -1 to LDAP search, we'd end up in a Protocol
		// error. With a limit of 0, we get 0 results. So we pass null.
		if ($limit <= 0) {
			$limit = null;
		}
		$filter = $this->access->combineFilterWithAnd([
			$this->access->connection->ldapUserFilter,
			$this->access->connection->ldapUserDisplayName . '=*',
			$this->access->getFilterPartForUserSearch($search)
		]);

		$this->logger->debug(
			'getUsers: Options: search '.$search.' limit '.$limit.' offset '.$offset.' Filter: '.$filter,
			['app' => 'user_ldap']
		);
		//do the search and translate results to Nextcloud names
		$ldap_users = $this->access->fetchListOfUsers(
			$filter,
			$this->access->userManager->getAttributes(true),
			$limit, $offset);
		$ldap_users = $this->access->nextcloudUserNames($ldap_users);
		$this->logger->debug(
			'getUsers: '.count($ldap_users). ' Users found',
			['app' => 'user_ldap']
		);

		$this->access->connection->writeToCache($cachekey, $ldap_users);
		return $ldap_users;
	}

	/**
	 * checks whether a user is still available on LDAP
	 *
	 * @throws \Exception
	 * @throws \OC\ServerNotAvailableException
	 */
	public function userExistsOnLDAP(string|\OCA\User_LDAP\User\User $user, bool $ignoreCache = false): bool {
		if (is_string($user)) {
			$user = $this->access->userManager->get($user);
		}
		if (is_null($user)) {
			return false;
		}
		$uid = ($user instanceof User) ? $user->getUsername() : $user->getOCName();
		$cacheKey = 'userExistsOnLDAP' . $uid;
		if (!$ignoreCache) {
			$userExists = $this->access->connection->getFromCache($cacheKey);
			if (!is_null($userExists)) {
				return (bool)$userExists;
			}
		}

		$dn = $user->getDN();
		//check if user really still exists by reading its entry
		if (!is_array($this->access->readAttribute($dn, '', $this->access->connection->ldapUserFilter))) {
			try {
				$uuid = $this->access->getUserMapper()->getUUIDByDN($dn);
				if (!$uuid) {
					$this->access->connection->writeToCache($cacheKey, false);
					return false;
				}
				$newDn = $this->access->getUserDnByUuid($uuid);
				//check if renamed user is still valid by reapplying the ldap filter
				if ($newDn === $dn || !is_array($this->access->readAttribute($newDn, '', $this->access->connection->ldapUserFilter))) {
					$this->access->connection->writeToCache($cacheKey, false);
					return false;
				}
				$this->access->getUserMapper()->setDNbyUUID($newDn, $uuid);
			} catch (ServerNotAvailableException $e) {
				throw $e;
			} catch (\Exception $e) {
				$this->access->connection->writeToCache($cacheKey, false);
				return false;
			}
		}

		if ($user instanceof OfflineUser) {
			$user->unmark();
		}

		$this->access->connection->writeToCache($cacheKey, true);
		return true;
	}

	/**
	 * check if a user exists
	 *
	 * @throws \Exception when connection could not be established
	 */
	public function userExists(string $uid): bool {
		$userExists = $this->access->connection->getFromCache('userExists'.$uid);
		if (!is_null($userExists)) {
			return (bool)$userExists;
		}
		//getting dn, if false the user does not exist. If dn, he may be mapped only, requires more checking.
		$user = $this->access->userManager->get($uid);

		if (is_null($user)) {
			$this->logger->debug(
				'No DN found for '.$uid.' on '.$this->access->connection->ldapHost,
				['app' => 'user_ldap']
			);
			$this->access->connection->writeToCache('userExists'.$uid, false);
			return false;
		}

		$this->access->connection->writeToCache('userExists'.$uid, true);
		return true;
	}

	/**
	 * returns whether a user was deleted in LDAP
	 *
	 */
	public function deleteUser(string $uid): bool {
		if ($this->userPluginManager->canDeleteUser()) {
			$status = $this->userPluginManager->deleteUser($uid);
			if ($status === false) {
				return false;
			}
		}

		$marked = $this->deletedUsersIndex->isUserMarked($uid);
		if (!$marked) {
			try {
				$user = $this->access->userManager->get($uid);
				if (($user instanceof User) && !$this->userExistsOnLDAP($uid, true)) {
					$user->markUser();
					$marked = true;
				}
			} catch (\Exception $e) {
				$this->logger->debug(
					$e->getMessage(),
					['app' => 'user_ldap', 'exception' => $e]
				);
			}
			if (!$marked) {
				$this->logger->notice(
					'User '.$uid . ' is not marked as deleted, not cleaning up.',
					['app' => 'user_ldap']
				);
				return false;
			}
		}
		$this->logger->info('Cleaning up after user ' . $uid,
			['app' => 'user_ldap']);

		$this->access->getUserMapper()->unmap($uid); // we don't emit unassign signals here, since it is implicit to delete signals fired from core
		$this->access->userManager->invalidate($uid);
		$this->access->connection->clearCache();
		return true;
	}

	/**
	 * get the user's home directory
	 *
	 * @throws NoUserException
	 * @throws \Exception
	 */
	public function getHome(string $uid): bool|string {
		// user Exists check required as it is not done in user proxy!
		if (!$this->userExists($uid)) {
			return false;
		}

		if ($this->userPluginManager->implementsActions(Backend::GET_HOME)) {
			return $this->userPluginManager->getHome($uid);
		}

		$cacheKey = 'getHome'.$uid;
		$path = $this->access->connection->getFromCache($cacheKey);
		if (!is_null($path)) {
			return $path;
		}

		// early return path if it is a deleted user
		$user = $this->access->userManager->get($uid);
		if ($user instanceof User || $user instanceof OfflineUser) {
			$path = $user->getHomePath() ?: false;
		} else {
			throw new NoUserException($uid . ' is not a valid user anymore');
		}

		$this->access->cacheUserHome($uid, $path);
		return $path;
	}

	/**
	 * get display name of the user
  	 *
	 */
	public function getDisplayName(string $uid): string|false {
		if ($this->userPluginManager->implementsActions(Backend::GET_DISPLAYNAME)) {
			return $this->userPluginManager->getDisplayName($uid);
		}

		if (!$this->userExists($uid)) {
			return false;
		}

		$cacheKey = 'getDisplayName'.$uid;
		if (!is_null($displayName = $this->access->connection->getFromCache($cacheKey))) {
			return $displayName;
		}

		//Check whether the display name is configured to have a 2nd feature
		$additionalAttribute = $this->access->connection->ldapUserDisplayName2;
		$displayName2 = '';
		if ($additionalAttribute !== '') {
			$displayName2 = $this->access->readAttribute(
				$this->access->username2dn($uid),
				$additionalAttribute);
		}

		$displayName = $this->access->readAttribute(
			$this->access->username2dn($uid),
			$this->access->connection->ldapUserDisplayName);

		if ($displayName && (count($displayName) > 0)) {
			$displayName = $displayName[0];

			if (is_array($displayName2)) {
				$displayName2 = count($displayName2) > 0 ? $displayName2[0] : '';
			}

			$user = $this->access->userManager->get($uid);
			if ($user instanceof User) {
				$displayName = $user->composeAndStoreDisplayName($displayName, $displayName2);
				$this->access->connection->writeToCache($cacheKey, $displayName);
			}
			if ($user instanceof OfflineUser) {
				/** @var OfflineUser $user*/
				$displayName = $user->getDisplayName();
			}
			return $displayName;
		}

		return null;
	}

	/**
	 * set display name of the user
	 *
	 */
	public function setDisplayName(string $uid, string $displayName): string|false {
		if ($this->userPluginManager->implementsActions(Backend::SET_DISPLAYNAME)) {
			$this->userPluginManager->setDisplayName($uid, $displayName);
			$this->access->cacheUserDisplayName($uid, $displayName);
			return $displayName;
		}
		return false;
	}

	/**
	 * Get a list of all display names
	 *
	 */
	public function getDisplayNames(string $search = '', ?int $limit = null, ?int $offset = null): array {
		$cacheKey = 'getDisplayNames-'.$search.'-'.$limit.'-'.$offset;
		if (!is_null($displayNames = $this->access->connection->getFromCache($cacheKey))) {
			return $displayNames;
		}

		$displayNames = [];
		$users = $this->getUsers($search, $limit, $offset);
		foreach ($users as $user) {
			$displayNames[$user] = $this->getDisplayName($user);
		}
		$this->access->connection->writeToCache($cacheKey, $displayNames);
		return $displayNames;
	}

	/**
	 * Check if backend implements actions
	 *
	 * Returns the supported actions as int to be
	 * compared with \OC\User\Backend::CREATE_USER etc.
	 */
	public function implementsActions(int $actions): bool {
		return (bool)((Backend::CHECK_PASSWORD
			| Backend::GET_HOME
			| Backend::GET_DISPLAYNAME
			| (($this->access->connection->ldapUserAvatarRule !== 'none') ? Backend::PROVIDE_AVATAR : 0)
			| Backend::COUNT_USERS
			| (((int)$this->access->connection->turnOnPasswordChange === 1)? Backend::SET_PASSWORD :0)
			| $this->userPluginManager->getImplementedActions())
			& $actions);
	}

	public function hasUserListings(): bool {
		return true;
	}

	/**
	 * counts the users in LDAP
	 *
	 */
	public function countUsers(): int|false {
		if ($this->userPluginManager->implementsActions(Backend::COUNT_USERS)) {
			return $this->userPluginManager->countUsers();
		}

		$filter = $this->access->getFilterForUserCount();
		$cacheKey = 'countUsers-'.$filter;
		if (!is_null($entries = $this->access->connection->getFromCache($cacheKey))) {
			return $entries;
		}
		$entries = $this->access->countUsers($filter);
		$this->access->connection->writeToCache($cacheKey, $entries);
		return $entries;
	}

	public function countMappedUsers(): int {
		return $this->access->getUserMapper()->count();
	}

	/**
	 * Backend name to be shown in user management
	 * @return string the name of the backend to be shown
	 */
	public function getBackendName(): string {
		return 'LDAP';
	}

	/**
	 * Return access for LDAP interaction.
	 *
	 * @return Access instance of Access for LDAP interaction
	 */
	public function getLDAPAccess(string $uid) {
		return $this->access;
	}

	/**
	 * Return LDAP connection resource from a cloned connection.
	 * The cloned connection needs to be closed manually.
	 * of the current access.
  	 *
	 * @return \LDAP\Connection The LDAP connection
	 */
	public function getNewLDAPConnection(string $uid) {
		$connection = clone $this->access->getConnection();
		return $connection->getConnectionResource();
	}

	/**
	 * create new user
	 *
	 * @throws \UnexpectedValueException
	 */
	public function createUser(string $username, string $password): bool {
		if ($this->userPluginManager->implementsActions(Backend::CREATE_USER)) {
			if ($dn = $this->userPluginManager->createUser($username, $password)) {
				if (is_string($dn)) {
					// the NC user creation work flow requires a know user id up front
					$uuid = $this->access->getUUID($dn, true);
					if (is_string($uuid)) {
						$this->access->mapAndAnnounceIfApplicable(
							$this->access->getUserMapper(),
							$dn,
							$username,
							$uuid,
							true
						);
						$this->access->cacheUserExists($username);
					} else {
						$this->logger->warning(
							'Failed to map created LDAP user with userid {userid}, because UUID could not be determined',
							[
								'app' => 'user_ldap',
								'userid' => $username,
							]
						);
					}
				} else {
					throw new \UnexpectedValueException("LDAP Plugin: Method createUser changed to return the user DN instead of boolean.");
				}
			}
			return (bool) $dn;
		}
		return false;
	}

	public function isUserEnabled(string $uid, callable $queryDatabaseValue): bool {
		if ($this->deletedUsersIndex->isUserMarked($uid) && ((int)$this->access->connection->markRemnantsAsDisabled === 1)) {
			return false;
		} else {
			return $queryDatabaseValue();
		}
	}

	public function setUserEnabled(string $uid, bool $enabled, callable $queryDatabaseValue, callable $setDatabaseValue): bool {
		$setDatabaseValue($enabled);
		return $enabled;
	}

	public function getDisabledUserList(?int $limit = null, int $offset = 0, string $search = ''): array {
		throw new \Exception('This is implemented directly in User_Proxy');
	}
}
