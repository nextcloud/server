<?php

/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\User_LDAP\User;

use OCA\User_LDAP\Access;
use OCP\Cache\CappedMemoryCache;
use OCP\IAvatarManager;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Image;
use OCP\IUserManager;
use OCP\Notification\IManager as INotificationManager;
use OCP\Share\IManager;
use Psr\Log\LoggerInterface;

/**
 * Manager
 *
 * upon request, returns an LDAP user object either by creating or from run-time
 * cache
 */
class Manager {
	protected ?Access $access = null;
	protected IDBConnection $db;
	/** @var CappedMemoryCache<User> $usersByDN */
	protected CappedMemoryCache $usersByDN;
	/** @var CappedMemoryCache<User> $usersByUid */
	protected CappedMemoryCache $usersByUid;

	public function __construct(
		protected IConfig $ocConfig,
		protected LoggerInterface $logger,
		protected IAvatarManager $avatarManager,
		protected Image $image,
		protected IUserManager $userManager,
		protected INotificationManager $notificationManager,
		private IManager $shareManager,
	) {
		$this->usersByDN = new CappedMemoryCache();
		$this->usersByUid = new CappedMemoryCache();
	}

	/**
	 * Binds manager to an instance of Access.
	 * It needs to be assigned first before the manager can be used.
	 * @param Access
	 */
	public function setLdapAccess(Access $access) {
		$this->access = $access;
	}

	/**
	 * @brief creates an instance of User and caches (just runtime) it in the
	 * property array
	 * @param string $dn the DN of the user
	 * @param string $uid the internal (owncloud) username
	 * @return User
	 */
	private function createAndCache($dn, $uid) {
		$this->checkAccess();
		$user = new User($uid, $dn, $this->access, $this->ocConfig,
			clone $this->image, $this->logger,
			$this->avatarManager, $this->userManager,
			$this->notificationManager);
		$this->usersByDN[$dn] = $user;
		$this->usersByUid[$uid] = $user;
		return $user;
	}

	/**
	 * removes a user entry from the cache
	 * @param $uid
	 */
	public function invalidate($uid) {
		if (!isset($this->usersByUid[$uid])) {
			return;
		}
		$dn = $this->usersByUid[$uid]->getDN();
		unset($this->usersByUid[$uid]);
		unset($this->usersByDN[$dn]);
	}

	/**
	 * @brief checks whether the Access instance has been set
	 * @throws \Exception if Access has not been set
	 * @psalm-assert !null $this->access
	 * @return null
	 */
	private function checkAccess() {
		if (is_null($this->access)) {
			throw new \Exception('LDAP Access instance must be set first');
		}
	}

	/**
	 * returns a list of attributes that will be processed further, e.g. quota,
	 * email, displayname, or others.
	 *
	 * @param bool $minimal - optional, set to true to skip attributes with big
	 *                      payload
	 * @return string[]
	 */
	public function getAttributes($minimal = false) {
		$baseAttributes = array_merge(Access::UUID_ATTRIBUTES, ['dn', 'uid', 'samaccountname', 'memberof']);
		$attributes = [
			$this->access->getConnection()->ldapExpertUUIDUserAttr,
			$this->access->getConnection()->ldapExpertUsernameAttr,
			$this->access->getConnection()->ldapQuotaAttribute,
			$this->access->getConnection()->ldapEmailAttribute,
			$this->access->getConnection()->ldapUserDisplayName,
			$this->access->getConnection()->ldapUserDisplayName2,
			$this->access->getConnection()->ldapExtStorageHomeAttribute,
			$this->access->getConnection()->ldapAttributePhone,
			$this->access->getConnection()->ldapAttributeWebsite,
			$this->access->getConnection()->ldapAttributeAddress,
			$this->access->getConnection()->ldapAttributeTwitter,
			$this->access->getConnection()->ldapAttributeFediverse,
			$this->access->getConnection()->ldapAttributeOrganisation,
			$this->access->getConnection()->ldapAttributeRole,
			$this->access->getConnection()->ldapAttributeHeadline,
			$this->access->getConnection()->ldapAttributeBiography,
			$this->access->getConnection()->ldapAttributeBirthDate,
			$this->access->getConnection()->ldapAttributePronouns,
		];

		$homeRule = (string)$this->access->getConnection()->homeFolderNamingRule;
		if (str_starts_with($homeRule, 'attr:')) {
			$attributes[] = substr($homeRule, strlen('attr:'));
		}

		if (!$minimal) {
			// attributes that are not really important but may come with big
			// payload.
			$attributes = array_merge(
				$attributes,
				$this->access->getConnection()->resolveRule('avatar')
			);
		}

		$attributes = array_reduce($attributes,
			function ($list, $attribute) {
				$attribute = strtolower(trim((string)$attribute));
				if (!empty($attribute) && !in_array($attribute, $list)) {
					$list[] = $attribute;
				}

				return $list;
			},
			$baseAttributes // hard-coded, lower-case, non-empty attributes
		);

		return $attributes;
	}

	/**
	 * Checks whether the specified user is marked as deleted
	 * @param string $id the Nextcloud user name
	 * @return bool
	 */
	public function isDeletedUser($id) {
		$isDeleted = $this->ocConfig->getUserValue(
			$id, 'user_ldap', 'isDeleted', 0);
		return (int)$isDeleted === 1;
	}

	/**
	 * creates and returns an instance of OfflineUser for the specified user
	 * @param string $id
	 * @return OfflineUser
	 */
	public function getDeletedUser($id) {
		return new OfflineUser(
			$id,
			$this->ocConfig,
			$this->access->getUserMapper(),
			$this->shareManager
		);
	}

	/**
	 * @brief returns a User object by its Nextcloud username
	 * @param string $id the DN or username of the user
	 * @return User|OfflineUser|null
	 */
	protected function createInstancyByUserName($id) {
		//most likely a uid. Check whether it is a deleted user
		if ($this->isDeletedUser($id)) {
			return $this->getDeletedUser($id);
		}
		$dn = $this->access->username2dn($id);
		if ($dn !== false) {
			return $this->createAndCache($dn, $id);
		}
		return null;
	}

	/**
	 * @brief returns a User object by its DN or Nextcloud username
	 * @param string $id the DN or username of the user
	 * @return User|OfflineUser|null
	 * @throws \Exception when connection could not be established
	 */
	public function get($id) {
		$this->checkAccess();
		if (isset($this->usersByDN[$id])) {
			return $this->usersByDN[$id];
		} elseif (isset($this->usersByUid[$id])) {
			return $this->usersByUid[$id];
		}

		if ($this->access->stringResemblesDN($id)) {
			$uid = $this->access->dn2username($id);
			if ($uid !== false) {
				return $this->createAndCache($id, $uid);
			}
		}

		return $this->createInstancyByUserName($id);
	}

	/**
	 * @brief Checks whether a User object by its DN or Nextcloud username exists
	 * @param string $id the DN or username of the user
	 * @throws \Exception when connection could not be established
	 */
	public function exists($id): bool {
		$this->checkAccess();
		$this->logger->debug('Checking if {id} exists', ['id' => $id]);
		if (isset($this->usersByDN[$id])) {
			return true;
		} elseif (isset($this->usersByUid[$id])) {
			return true;
		}

		if ($this->access->stringResemblesDN($id)) {
			$this->logger->debug('{id} looks like a dn', ['id' => $id]);
			$uid = $this->access->dn2username($id);
			if ($uid !== false) {
				return true;
			}
		}

		// Most likely a uid. Check whether it is a deleted user
		if ($this->isDeletedUser($id)) {
			return true;
		}
		$dn = $this->access->username2dn($id);
		if ($dn !== false) {
			return true;
		}
		return false;
	}
}
