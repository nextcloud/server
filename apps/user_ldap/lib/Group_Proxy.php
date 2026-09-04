<?php

/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\User_LDAP;

use OC\ServerNotAvailableException;
use OCP\Config\IUserConfig;
use OCP\Group\Backend\IAddToGroupBackend;
use OCP\Group\Backend\IBatchMethodsBackend;
use OCP\Group\Backend\ICountUsersBackend;
use OCP\Group\Backend\ICreateNamedGroupBackend;
use OCP\Group\Backend\IDeleteGroupBackend;
use OCP\Group\Backend\IGetDisplayNameBackend;
use OCP\Group\Backend\IGroupDetailsBackend;
use OCP\Group\Backend\IIsAdminBackend;
use OCP\Group\Backend\INamedBackend;
use OCP\Group\Backend\IRemoveFromGroupBackend;
use OCP\GroupInterface;
use OCP\IUserManager;

/**
 * @template-extends Proxy<Group_LDAP>
 *
 * @note This class implements a few more interface (e.g. ICreateNamedGroupBackend) via plugins.
 */
class Group_Proxy extends Proxy implements GroupInterface, IGroupLDAP, IGetDisplayNameBackend, INamedBackend, IDeleteGroupBackend, IBatchMethodsBackend, IIsAdminBackend, IGroupDetailsBackend, ICountUsersBackend {
	public function __construct(
		Helper $helper,
		ILDAPWrapper $ldap,
		AccessFactory $accessFactory,
		private GroupPluginManager $groupPluginManager,
		private IUserConfig $userConfig,
		private IUserManager $ncUserManager,
	) {
		parent::__construct($helper, $ldap, $accessFactory);
	}

	#[\Override]
	protected function newInstance(string $configPrefix): Group_LDAP {
		return new Group_LDAP($this->getAccess($configPrefix), $this->groupPluginManager, $this->userConfig, $this->ncUserManager);
	}

	#[\Override]
	protected function walkBackends(string $id, string $method, array $parameters): mixed {
		$this->setup();

		$gid = $id;
		$cacheKey = $this->getGroupCacheKey($gid);
		foreach ($this->backends as $configPrefix => $backend) {
			if ($result = call_user_func_array([$backend, $method], $parameters)) {
				if (!$this->isSingleBackend()) {
					$this->writeToCache($cacheKey, $configPrefix);
				}
				return $result;
			}
		}
		return false;
	}

	#[\Override]
	protected function callOnLastSeenOn(string $id, string $method, array $parameters, bool $passOnWhen): mixed {
		$this->setup();

		$gid = $id;
		$cacheKey = $this->getGroupCacheKey($gid);
		$prefix = $this->getFromCache($cacheKey);
		//in case the uid has been found in the past, try this stored connection first
		if (!is_null($prefix)) {
			if (isset($this->backends[$prefix])) {
				$result = call_user_func_array([$this->backends[$prefix], $method], $parameters);
				if ($result === $passOnWhen) {
					//not found here, reset cache to null if group vanished
					//because sometimes methods return false with a reason
					$groupExists = call_user_func_array(
						[$this->backends[$prefix], 'groupExists'],
						[$gid]
					);
					if (!$groupExists) {
						$this->writeToCache($cacheKey, null);
					}
				}
				return $result;
			}
		}
		return false;
	}

	#[\Override]
	protected function activeBackends(): int {
		$this->setup();
		return count($this->backends);
	}

	#[\Override]
	public function inGroup(string $uid, string $gid): bool {
		return $this->handleRequest($gid, 'inGroup', [$uid, $gid]);
	}

	#[\Override]
	public function getUserGroups(string $uid): array {
		$this->setup();

		$groups = [];
		foreach ($this->backends as $backend) {
			$backendGroups = $backend->getUserGroups($uid);
			$groups = array_merge($groups, $backendGroups);
		}

		return array_values(array_unique($groups));
	}

	#[\Override]
	public function usersInGroup(string $gid, string $search = '', int $limit = -1, int $offset = 0): array {
		$this->setup();

		$users = [];
		foreach ($this->backends as $backend) {
			$backendUsers = $backend->usersInGroup($gid, $search, $limit, $offset);
			if (is_array($backendUsers)) {
				$users = array_merge($users, $backendUsers);
			}
		}

		return $users;
	}

	/**
	 * Doesn't overwrite because dynamically implemented via ILDAPGroupPlugin
	 *
	 * @see ICreateNamedGroupBackend
	 */
	public function createGroup(string $name): ?string {
		return $this->handleRequest(
			$name, 'createGroup', [$name]);
	}

	#[\Override]
	public function deleteGroup(string $gid): bool {
		return $this->handleRequest(
			$gid, 'deleteGroup', [$gid]);
	}

	/**
	 * Doesn't overwrite because dynamically implemented via ILDAPGroupPlugin
	 *
	 * @see IAddToGroupBackend
	 */
	public function addToGroup(string $uid, string $gid): bool {
		return $this->handleRequest(
			$gid, 'addToGroup', [$uid, $gid]);
	}

	/**
	 * Doesn't overwrite because dynamically implemented via ILDAPGroupPlugin
	 *
	 * @see IRemoveFromGroupBackend
	 */
	public function removeFromGroup(string $uid, string $gid): bool {
		return $this->handleRequest(
			$gid, 'removeFromGroup', [$uid, $gid]);
	}

	#[\Override]
	public function countUsersInGroup(string $gid, string $search = ''): int {
		return $this->handleRequest(
			$gid, 'countUsersInGroup', [$gid, $search]);
	}

	#[\Override]
	public function getGroupDetails(string $gid): array {
		return $this->handleRequest(
			$gid, 'getGroupDetails', [$gid]);
	}

	/**
	 * {@inheritdoc}
	 */
	#[\Override]
	public function getGroupsDetails(array $gids): array {
		if (!($this instanceof IGroupDetailsBackend || $this->implementsActions(GroupInterface::GROUP_DETAILS))) {
			throw new \Exception('Should not have been called');
		}

		$groupData = [];
		foreach ($gids as $gid) {
			$groupData[$gid] = $this->handleRequest($gid, 'getGroupDetails', [$gid]);
		}
		return $groupData;
	}

	#[\Override]
	public function getGroups(string $search = '', int $limit = -1, int $offset = 0): array {
		$this->setup();

		$groups = [];
		foreach ($this->backends as $backend) {
			$backendGroups = $backend->getGroups($search, $limit, $offset);
			$groups = array_merge($groups, $backendGroups);
		}

		return $groups;
	}

	#[\Override]
	public function groupExists(string $gid): bool {
		return $this->handleRequest($gid, 'groupExists', [$gid]);
	}

	/**
	 * Check if a group exists
	 *
	 * @throws ServerNotAvailableException
	 */
	public function groupExistsOnLDAP(string $gid, bool $ignoreCache = false): bool {
		return $this->handleRequest($gid, 'groupExistsOnLDAP', [$gid, $ignoreCache]);
	}

	/**
	 * returns the groupname for the given LDAP DN, if available
	 */
	public function dn2GroupName(string $dn): string|false {
		$id = 'DN,' . $dn;
		return $this->handleRequest($id, 'dn2GroupName', [$dn]);
	}

	/**
	 * {@inheritdoc}
	 */
	#[\Override]
	public function groupsExists(array $gids): array {
		return array_values(array_filter(
			$gids,
			fn (string $gid): bool => $this->handleRequest($gid, 'groupExists', [$gid]),
		));
	}

	/**
	 * Check if backend implements actions
	 *
	 * @param int $actions bitwise-or'ed actions
	 * @return boolean
	 *
	 * Returns the supported actions as int to be
	 * compared with \OCP\GroupInterface::CREATE_GROUP etc.
	 */
	#[\Override]
	public function implementsActions(int $actions): bool {
		$this->setup();
		//it's the same across all our user backends obviously
		return $this->refBackend->implementsActions($actions);
	}

	#[\Override]
	public function getLDAPAccess(string $name): Access {
		return $this->handleRequest($name, 'getLDAPAccess', [$name]);
	}

	#[\Override]
	public function getNewLDAPConnection(string $name): \LDAP\Connection {
		return $this->handleRequest($name, 'getNewLDAPConnection', [$name]);
	}

	#[\Override]
	public function getDisplayName(string $gid): string {
		return $this->handleRequest($gid, 'getDisplayName', [$gid]);
	}

	/**
	 * Backend name to be shown in group management
	 * @return string the name of the backend to be shown
	 * @since 22.0.0
	 */
	#[\Override]
	public function getBackendName(): string {
		return 'LDAP';
	}

	public function searchInGroup(string $gid, string $search = '', int $limit = -1, int $offset = 0): array {
		return $this->handleRequest($gid, 'searchInGroup', [$gid, $search, $limit, $offset]);
	}

	public function addRelationshipToCaches(string $uid, ?string $dnUser, string $gid): void {
		$this->handleRequest($gid, 'addRelationshipToCaches', [$uid, $dnUser, $gid]);
	}

	#[\Override]
	public function isAdmin(string $uid): bool {
		return $this->handleRequest($uid, 'isAdmin', [$uid]);
	}
}
