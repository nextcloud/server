<?php

/**
 * ownCloud
 *
 * @author Artuhr Schiwon
 * @copyright 2013 Arthur Schiwon blizzz@owncloud.com
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\user_ldap;

use OCA\user_ldap\lib\ILDAPWrapper;

class Group_Proxy extends lib\Proxy implements \OCP\GroupInterface {
	private $backends = array();
	private $refBackend = null;

	/**
	 * @brief Constructor
	 * @param $serverConfigPrefixes array containing the config Prefixes
	 */
	public function __construct($serverConfigPrefixes, ILDAPWrapper $ldap) {
		parent::__construct($ldap);
		foreach($serverConfigPrefixes as $configPrefix) {
		    $this->backends[$configPrefix] =
				new \OCA\user_ldap\GROUP_LDAP($this->getAccess($configPrefix));
			if(is_null($this->refBackend)) {
				$this->refBackend = &$this->backends[$configPrefix];
			}
		}
	}

	/**
	 * @brief Tries the backends one after the other until a positive result is returned from the specified method
	 * @param $gid string, the gid connected to the request
	 * @param $method string, the method of the group backend that shall be called
	 * @param $parameters an array of parameters to be passed
	 * @return mixed, the result of the method or false
	 */
	protected function walkBackends($gid, $method, $parameters) {
		$cacheKey = $this->getGroupCacheKey($gid);
		foreach($this->backends as $configPrefix => $backend) {
		    if($result = call_user_func_array(array($backend, $method), $parameters)) {
				$this->writeToCache($cacheKey, $configPrefix);
				return $result;
		    }
		}
		return false;
	}

	/**
	 * @brief Asks the backend connected to the server that supposely takes care of the gid from the request.
	 * @param $gid string, the gid connected to the request
	 * @param $method string, the method of the group backend that shall be called
	 * @param $parameters an array of parameters to be passed
	 * @param $passOnWhen the result matches this variable
	 * @return mixed, the result of the method or false
	 */
	protected function callOnLastSeenOn($gid, $method, $parameters, $passOnWhen) {
		$cacheKey = $this->getGroupCacheKey($gid);;
		$prefix = $this->getFromCache($cacheKey);
		//in case the uid has been found in the past, try this stored connection first
		if(!is_null($prefix)) {
			if(isset($this->backends[$prefix])) {
				$result = call_user_func_array(array($this->backends[$prefix], $method), $parameters);
				if($result === $passOnWhen) {
					//not found here, reset cache to null if group vanished
					//because sometimes methods return false with a reason
					$groupExists = call_user_func_array(
						array($this->backends[$prefix], 'groupExists'),
						array($gid)
					);
					if(!$groupExists) {
						$this->writeToCache($cacheKey, null);
					}
				}
				return $result;
			}
		}
		return false;
	}

	/**
	 * @brief is user in group?
	 * @param $uid uid of the user
	 * @param $gid gid of the group
	 * @returns true/false
	 *
	 * Checks whether the user is member of a group or not.
	 */
	public function inGroup($uid, $gid) {
		return $this->handleRequest($gid, 'inGroup', array($uid, $gid));
	}

	/**
	 * @brief Get all groups a user belongs to
	 * @param $uid Name of the user
	 * @returns array with group names
	 *
	 * This function fetches all groups a user belongs to. It does not check
	 * if the user exists at all.
	 */
	public function getUserGroups($uid) {
		$groups = array();

		foreach($this->backends as $backend) {
		    $backendGroups = $backend->getUserGroups($uid);
			if (is_array($backendGroups)) {
				$groups = array_merge($groups, $backendGroups);
			}
		}

		return $groups;
	}

	/**
	 * @brief get a list of all users in a group
	 * @returns array with user ids
	 */
	public function usersInGroup($gid, $search = '', $limit = -1, $offset = 0) {
		$users = array();

		foreach($this->backends as $backend) {
		    $backendUsers = $backend->usersInGroup($gid, $search, $limit, $offset);
			if (is_array($backendUsers)) {
				$users = array_merge($users, $backendUsers);
			}
		}

		return $users;
	}

	/**
	 * @brief get a list of all groups
	 * @returns array with group names
	 *
	 * Returns a list with all groups
	 */
	public function getGroups($search = '', $limit = -1, $offset = 0) {
		$groups = array();

		foreach($this->backends as $backend) {
		    $backendGroups = $backend->getGroups($search, $limit, $offset);
			if (is_array($backendGroups)) {
				$groups = array_merge($groups, $backendGroups);
			}
		}

		return $groups;
	}

	/**
	 * check if a group exists
	 * @param string $gid
	 * @return bool
	 */
	public function groupExists($gid) {
		return $this->handleRequest($gid, 'groupExists', array($gid));
	}

	/**
	 * @brief Check if backend implements actions
	 * @param $actions bitwise-or'ed actions
	 * @returns boolean
	 *
	 * Returns the supported actions as int to be
	 * compared with OC_USER_BACKEND_CREATE_USER etc.
	 */
	public function implementsActions($actions) {
		//it's the same across all our user backends obviously
		return $this->refBackend->implementsActions($actions);
	}
}
