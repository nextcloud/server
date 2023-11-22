<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Christopher Schäpers <kondou@ts.unde.re>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Johannes Leuker <j.leuker@hosting.de>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Vinicius Cubas Brand <vinicius@eita.org.br>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\User_LDAP;

use OC\ServerNotAvailableException;
use OCP\Group\Backend\IBatchMethodsBackend;
use OCP\Group\Backend\IDeleteGroupBackend;
use OCP\Group\Backend\IGetDisplayNameBackend;
use OCP\Group\Backend\IGroupDetailsBackend;
use OCP\Group\Backend\INamedBackend;
use OCP\GroupInterface;
use OCP\IConfig;
use OCP\IUserManager;

class Group_Proxy extends Proxy implements \OCP\GroupInterface, IGroupLDAP, IGetDisplayNameBackend, INamedBackend, IDeleteGroupBackend, IBatchMethodsBackend {
	private $backends = [];
	private ?Group_LDAP $refBackend = null;
	private Helper $helper;
	private GroupPluginManager $groupPluginManager;
	private bool $isSetUp = false;
	private IConfig $config;
	private IUserManager $ncUserManager;

	public function __construct(
		Helper $helper,
		ILDAPWrapper $ldap,
		AccessFactory $accessFactory,
		GroupPluginManager $groupPluginManager,
		IConfig $config,
		IUserManager $ncUserManager,
	) {
		parent::__construct($ldap, $accessFactory);
		$this->helper = $helper;
		$this->groupPluginManager = $groupPluginManager;
		$this->config = $config;
		$this->ncUserManager = $ncUserManager;
	}

	protected function setup(): void {
		if ($this->isSetUp) {
			return;
		}

		$serverConfigPrefixes = $this->helper->getServerConfigurationPrefixes(true);
		foreach ($serverConfigPrefixes as $configPrefix) {
			$this->backends[$configPrefix] =
				new Group_LDAP($this->getAccess($configPrefix), $this->groupPluginManager, $this->config, $this->ncUserManager);
			if (is_null($this->refBackend)) {
				$this->refBackend = &$this->backends[$configPrefix];
			}
		}

		$this->isSetUp = true;
	}

	/**
	 * Tries the backends one after the other until a positive result is returned from the specified method
	 *
	 * @param string $id the gid connected to the request
	 * @param string $method the method of the group backend that shall be called
	 * @param array $parameters an array of parameters to be passed
	 * @return mixed the result of the method or false
	 */
	protected function walkBackends($id, $method, $parameters) {
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

	/**
	 * Asks the backend connected to the server that supposely takes care of the gid from the request.
	 *
	 * @param string $id the gid connected to the request
	 * @param string $method the method of the group backend that shall be called
	 * @param array $parameters an array of parameters to be passed
	 * @param mixed $passOnWhen the result matches this variable
	 * @return mixed the result of the method or false
	 */
	protected function callOnLastSeenOn($id, $method, $parameters, $passOnWhen) {
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

	protected function activeBackends(): int {
		$this->setup();
		return count($this->backends);
	}

	/**
	 * is user in group?
	 *
	 * @param string $uid uid of the user
	 * @param string $gid gid of the group
	 * @return bool
	 *
	 * Checks whether the user is member of a group or not.
	 */
	public function inGroup($uid, $gid) {
		return $this->handleRequest($gid, 'inGroup', [$uid, $gid]);
	}

	/**
	 * Get all groups a user belongs to
	 *
	 * @param string $uid Name of the user
	 * @return string[] with group names
	 *
	 * This function fetches all groups a user belongs to. It does not check
	 * if the user exists at all.
	 */
	public function getUserGroups($uid) {
		$this->setup();

		$groups = [];
		foreach ($this->backends as $backend) {
			$backendGroups = $backend->getUserGroups($uid);
			if (is_array($backendGroups)) {
				$groups = array_merge($groups, $backendGroups);
			}
		}

		return $groups;
	}

	/**
	 * get a list of all users in a group
	 *
	 * @return array<int,string> user ids
	 */
	public function usersInGroup($gid, $search = '', $limit = -1, $offset = 0) {
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
	 * @param string $gid
	 * @return bool
	 */
	public function createGroup($gid) {
		return $this->handleRequest(
			$gid, 'createGroup', [$gid]);
	}

	/**
	 * delete a group
	 */
	public function deleteGroup(string $gid): bool {
		return $this->handleRequest(
			$gid, 'deleteGroup', [$gid]);
	}

	/**
	 * Add a user to a group
	 *
	 * @param string $uid Name of the user to add to group
	 * @param string $gid Name of the group in which add the user
	 * @return bool
	 *
	 * Adds a user to a group.
	 */
	public function addToGroup($uid, $gid) {
		return $this->handleRequest(
			$gid, 'addToGroup', [$uid, $gid]);
	}

	/**
	 * Removes a user from a group
	 *
	 * @param string $uid Name of the user to remove from group
	 * @param string $gid Name of the group from which remove the user
	 * @return bool
	 *
	 * removes the user from a group.
	 */
	public function removeFromGroup($uid, $gid) {
		return $this->handleRequest(
			$gid, 'removeFromGroup', [$uid, $gid]);
	}

	/**
	 * returns the number of users in a group, who match the search term
	 *
	 * @param string $gid the internal group name
	 * @param string $search optional, a search string
	 * @return int|bool
	 */
	public function countUsersInGroup($gid, $search = '') {
		return $this->handleRequest(
			$gid, 'countUsersInGroup', [$gid, $search]);
	}

	/**
	 * get an array with group details
	 *
	 * @param string $gid
	 * @return array|false
	 */
	public function getGroupDetails($gid) {
		return $this->handleRequest(
			$gid, 'getGroupDetails', [$gid]);
	}

	/**
	 * {@inheritdoc}
	 */
	public function getGroupsDetails(array $gids): array {
		if (!($this instanceof IGroupDetailsBackend || $this->implementsActions(GroupInterface::GROUP_DETAILS))) {
			throw new \Exception("Should not have been called");
		}

		$groupData = [];
		foreach ($gids as $gid) {
			$groupData[$gid] = $this->handleRequest($gid, 'getGroupDetails', [$gid]);
		}
		return $groupData;
	}

	/**
	 * get a list of all groups
	 *
	 * @return string[] with group names
	 *
	 * Returns a list with all groups
	 */
	public function getGroups($search = '', $limit = -1, $offset = 0) {
		$this->setup();

		$groups = [];
		foreach ($this->backends as $backend) {
			$backendGroups = $backend->getGroups($search, $limit, $offset);
			if (is_array($backendGroups)) {
				$groups = array_merge($groups, $backendGroups);
			}
		}

		return $groups;
	}

	/**
	 * check if a group exists
	 *
	 * @param string $gid
	 * @return bool
	 */
	public function groupExists($gid) {
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
	public function implementsActions($actions) {
		$this->setup();
		//it's the same across all our user backends obviously
		return $this->refBackend->implementsActions($actions);
	}

	/**
	 * Return access for LDAP interaction.
	 *
	 * @param string $gid
	 * @return Access instance of Access for LDAP interaction
	 */
	public function getLDAPAccess($gid) {
		return $this->handleRequest($gid, 'getLDAPAccess', [$gid]);
	}

	/**
	 * Return a new LDAP connection for the specified group.
	 * The connection needs to be closed manually.
	 *
	 * @param string $gid
	 * @return resource|\LDAP\Connection The LDAP connection
	 */
	public function getNewLDAPConnection($gid) {
		return $this->handleRequest($gid, 'getNewLDAPConnection', [$gid]);
	}

	public function getDisplayName(string $gid): string {
		return $this->handleRequest($gid, 'getDisplayName', [$gid]);
	}

	/**
	 * Backend name to be shown in group management
	 * @return string the name of the backend to be shown
	 * @since 22.0.0
	 */
	public function getBackendName(): string {
		return 'LDAP';
	}

	public function searchInGroup(string $gid, string $search = '', int $limit = -1, int $offset = 0): array {
		return $this->handleRequest($gid, 'searchInGroup', [$gid, $search, $limit, $offset]);
	}

	public function addRelationshipToCaches(string $uid, ?string $dnUser, string $gid): void {
		$this->handleRequest($gid, 'addRelationshipToCaches', [$uid, $dnUser, $gid]);
	}
}
