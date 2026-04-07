<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\User_LDAP;

use LDAP\Connection;
use OCA\User_LDAP\User\DeletedUsersIndex;
use OCP\GroupInterface;
use OCP\IGroupManager;
use OCP\IUserManager;
use OCP\LDAP\IDeletionFlagSupport;
use OCP\LDAP\ILDAPProvider;
use OCP\UserInterface;
use Psr\Log\LoggerInterface;

/**
 * LDAP provider for public access to the LDAP backend.
 */
class LDAPProvider implements ILDAPProvider, IDeletionFlagSupport {
	private IUserLDAP&UserInterface $userBackend;
	private IGroupLDAP&GroupInterface $groupBackend;

	/**
	 * @throws \Exception if user_ldap app was not enabled
	 */
	public function __construct(
		IUserManager $userManager,
		IGroupManager $groupManager,
		private Helper $helper,
		private DeletedUsersIndex $deletedUsersIndex,
		private LoggerInterface $logger,
	) {
		$userBackendFound = false;
		$groupBackendFound = false;
		foreach ($userManager->getBackends() as $backend) {
			$this->logger->debug('instance ' . get_class($backend) . ' user backend.', ['app' => 'user_ldap']);
			if ($backend instanceof IUserLDAP) {
				$this->userBackend = $backend;
				$userBackendFound = true;
				break;
			}
		}
		foreach ($groupManager->getBackends() as $backend) {
			$this->logger->debug('instance ' . get_class($backend) . ' group backend.', ['app' => 'user_ldap']);
			if ($backend instanceof IGroupLDAP) {
				$this->groupBackend = $backend;
				$groupBackendFound = true;
				break;
			}
		}

		if (!$userBackendFound || !$groupBackendFound) {
			throw new \Exception('To use the LDAPProvider, user_ldap app must be enabled');
		}
	}

	/**
	 * Translate an user id to LDAP DN
	 * @param string $uid user id
	 * @return string with the LDAP DN
	 * @throws \Exception if translation was unsuccessful
	 */
	public function getUserDN(string $uid): string {
		if (!$this->userBackend->userExists($uid)) {
			throw new \Exception('User id not found in LDAP');
		}
		$result = $this->userBackend->getLDAPAccess($uid)->username2dn($uid);
		if (!$result) {
			throw new \Exception('Translation to LDAP DN unsuccessful');
		}
		return $result;
	}

	/**
	 * Translate a group id to LDAP DN.
	 * @throws \Exception
	 */
	public function getGroupDN(string $gid): string {
		if (!$this->groupBackend->groupExists($gid)) {
			throw new \Exception('Group id not found in LDAP');
		}
		$result = $this->groupBackend->getLDAPAccess($gid)->groupname2dn($gid);
		if (!$result) {
			throw new \Exception('Translation to LDAP DN unsuccessful');
		}
		return $result;
	}

	/**
	 * Translate a LDAP DN to an internal user name. If there is no mapping between
	 * the DN and the user name, a new one will be created.
	 * @return string the internal user name
	 * @throws \Exception if translation was unsuccessful
	 */
	public function getUserName(string $dn): string {
		$result = $this->userBackend->dn2UserName($dn);
		if (!$result) {
			throw new \Exception('Translation to internal user name unsuccessful');
		}
		return $result;
	}

	/**
	 * Convert a stored DN so it can be used as base parameter for LDAP queries.
	 */
	public function DNasBaseParameter(string $dn): string {
		return $this->helper->DNasBaseParameter($dn);
	}

	/**
	 * Sanitize a DN received from the LDAP server.
	 */
	public function sanitizeDN(array|string $dn): array|string {
		return $this->helper->sanitizeDN($dn);
	}

	/**
	 * Return a new LDAP connection resource for the specified user.
	 * The connection must be closed manually.
	 * @throws \Exception if user id was not found in LDAP
	 */
	public function getLDAPConnection(string $uid): Connection {
		if (!$this->userBackend->userExists($uid)) {
			throw new \Exception('User id not found in LDAP');
		}
		return $this->userBackend->getNewLDAPConnection($uid);
	}

	/**
	 * Return a new LDAP connection resource for the specified user.
	 * The connection must be closed manually.
	 * @throws \Exception if group id was not found in LDAP
	 */
	public function getGroupLDAPConnection(string $gid): Connection {
		if (!$this->groupBackend->groupExists($gid)) {
			throw new \Exception('Group id not found in LDAP');
		}
		return $this->groupBackend->getNewLDAPConnection($gid);
	}

	/**
	 * Get the LDAP base for users.
	 * @throws \Exception if user id was not found in LDAP
	 */
	public function getLDAPBaseUsers(string $uid): string {
		if (!$this->userBackend->userExists($uid)) {
			throw new \Exception('User id not found in LDAP');
		}
		$access = $this->userBackend->getLDAPAccess($uid);
		$bases = $access->getConnection()->ldapBaseUsers;
		$dn = $this->getUserDN($uid);
		foreach ($bases as $base) {
			if ($access->isDNPartOfBase($dn, [$base])) {
				return $base;
			}
		}
		// should not occur, because the user does not qualify to use NC in this case
		$this->logger->info(
			'No matching user base found for user {dn}, available: {bases}.',
			[
				'app' => 'user_ldap',
				'dn' => $dn,
				'bases' => $bases,
			]
		);
		return array_shift($bases);
	}

	/**
	 * Get the LDAP base for groups.
	 * @throws \Exception if user id was not found in LDAP
	 */
	public function getLDAPBaseGroups(string $uid): string {
		if (!$this->userBackend->userExists($uid)) {
			throw new \Exception('User id not found in LDAP');
		}
		$bases = $this->userBackend->getLDAPAccess($uid)->getConnection()->ldapBaseGroups;
		return array_shift($bases);
	}

	/**
	 * Clear the cache if a cache is used, otherwise do nothing.
	 * @throws \Exception if user id was not found in LDAP
	 */
	public function clearCache(string $uid): void {
		if (!$this->userBackend->userExists($uid)) {
			throw new \Exception('User id not found in LDAP');
		}
		$this->userBackend->getLDAPAccess($uid)->getConnection()->clearCache();
	}

	/**
	 * Clear the cache if a cache is used, otherwise do nothing.
	 * Acts on the LDAP connection of a group
	 * @throws \Exception if user id was not found in LDAP
	 */
	public function clearGroupCache(string $gid): void {
		if (!$this->groupBackend->groupExists($gid)) {
			throw new \Exception('Group id not found in LDAP');
		}
		$this->groupBackend->getLDAPAccess($gid)->getConnection()->clearCache();
	}

	/**
	 * Check whether a LDAP DN exists
	 */
	public function dnExists(string $dn): bool {
		$result = $this->userBackend->dn2UserName($dn);
		return !$result ? false : true;
	}

	/**
	 * Flag record for deletion.
	 */
	public function flagRecord(string $uid): void {
		$this->deletedUsersIndex->markUser($uid);
	}

	/**
	 * Unflag record for deletion.
	 */
	public function unflagRecord(string $uid): void {
		//do nothing
	}

	/**
	 * Get the LDAP attribute name for the user's display name
	 * @throws \Exception if user id was not found in LDAP
	 */
	public function getLDAPDisplayNameField(string $uid): string {
		if (!$this->userBackend->userExists($uid)) {
			throw new \Exception('User id not found in LDAP');
		}
		return $this->userBackend->getLDAPAccess($uid)->getConnection()->getConfiguration()['ldap_display_name'];
	}

	/**
	 * Get the LDAP attribute name for the email
	 * @throws \Exception if user id was not found in LDAP
	 */
	public function getLDAPEmailField(string $uid): string {
		if (!$this->userBackend->userExists($uid)) {
			throw new \Exception('User id not found in LDAP');
		}
		return $this->userBackend->getLDAPAccess($uid)->getConnection()->getConfiguration()['ldap_email_attr'];
	}

	/**
	 * Get the LDAP type of association between users and groups
	 * @throws \Exception if group id was not found in LDAP
	 */
	public function getLDAPGroupMemberAssoc(string $gid): string {
		if (!$this->groupBackend->groupExists($gid)) {
			throw new \Exception('Group id not found in LDAP');
		}
		return $this->groupBackend->getLDAPAccess($gid)->getConnection()->getConfiguration()['ldap_group_member_assoc_attribute'];
	}

	/**
	 * Get an LDAP attribute for a nextcloud user
	 *
	 * @throws \Exception if user id was not found in LDAP
	 */
	public function getUserAttribute(string $uid, string $attribute): ?string {
		$values = $this->getMultiValueUserAttribute($uid, $attribute);
		if (count($values) === 0) {
			return null;
		}
		return current($values);
	}

	/**
	 * Get a multi-value LDAP attribute for a nextcloud user
	 *
	 * @throws \Exception if user id was not found in LDAP
	 */
	public function getMultiValueUserAttribute(string $uid, string $attribute): array {
		if (!$this->userBackend->userExists($uid)) {
			throw new \Exception('User id not found in LDAP');
		}

		$access = $this->userBackend->getLDAPAccess($uid);
		$connection = $access->getConnection();
		$key = $uid . '-' . $attribute;

		$cached = $connection->getFromCache($key);
		if (is_array($cached)) {
			return $cached;
		}

		$values = $access->readAttribute($access->username2dn($uid), $attribute);
		if ($values === false) {
			$values = [];
		}

		$connection->writeToCache($key, $values);
		return $values;
	}
}
