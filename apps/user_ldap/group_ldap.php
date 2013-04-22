<?php

/**
 * ownCloud – LDAP group backend
 *
 * @author Arthur Schiwon
 * @copyright 2012 Arthur Schiwon blizzz@owncloud.com
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

class GROUP_LDAP extends lib\Access implements \OCP\GroupInterface {
	protected $enabled = false;

	public function setConnector(lib\Connection &$connection) {
		parent::setConnector($connection);
		$filter = $this->connection->ldapGroupFilter;
		$gassoc = $this->connection->ldapGroupMemberAssocAttr;
		if(!empty($filter) && !empty($gassoc)) {
			$this->enabled = true;
		}
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
		if(!$this->enabled) {
			return false;
		}
		if($this->connection->isCached('inGroup'.$uid.':'.$gid)) {
			return $this->connection->getFromCache('inGroup'.$uid.':'.$gid);
		}
		$dn_user = $this->username2dn($uid);
		$dn_group = $this->groupname2dn($gid);
		// just in case
		if(!$dn_group || !$dn_user) {
			$this->connection->writeToCache('inGroup'.$uid.':'.$gid, false);
			return false;
		}
		//usually, LDAP attributes are said to be case insensitive. But there are exceptions of course.
		$members = $this->readAttribute($dn_group, $this->connection->ldapGroupMemberAssocAttr);
		if(!$members) {
			$this->connection->writeToCache('inGroup'.$uid.':'.$gid, false);
			return false;
		}

		//extra work if we don't get back user DNs
		//TODO: this can be done with one LDAP query
		if(strtolower($this->connection->ldapGroupMemberAssocAttr) == 'memberuid') {
			$dns = array();
			foreach($members as $mid) {
				$filter = str_replace('%uid', $mid, $this->connection->ldapLoginFilter);
				$ldap_users = $this->fetchListOfUsers($filter, 'dn');
				if(count($ldap_users) < 1) {
					continue;
				}
				$dns[] = $ldap_users[0];
			}
			$members = $dns;
		}

		$isInGroup = in_array($dn_user, $members);
		$this->connection->writeToCache('inGroup'.$uid.':'.$gid, $isInGroup);

		return $isInGroup;
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
		if(!$this->enabled) {
			return array();
		}
		$cacheKey = 'getUserGroups'.$uid;
		if($this->connection->isCached($cacheKey)) {
			return $this->connection->getFromCache($cacheKey);
		}
		$userDN = $this->username2dn($uid);
		if(!$userDN) {
			$this->connection->writeToCache($cacheKey, array());
			return array();
		}

		//uniqueMember takes DN, memberuid the uid, so we need to distinguish
		if((strtolower($this->connection->ldapGroupMemberAssocAttr) == 'uniquemember')
			|| (strtolower($this->connection->ldapGroupMemberAssocAttr) == 'member')
		) {
			$uid = $userDN;
		} else if(strtolower($this->connection->ldapGroupMemberAssocAttr) == 'memberuid') {
			$result = $this->readAttribute($userDN, 'uid');
			$uid = $result[0];
		} else {
			// just in case
			$uid = $userDN;
		}

		$filter = $this->combineFilterWithAnd(array(
			$this->connection->ldapGroupFilter,
			$this->connection->ldapGroupMemberAssocAttr.'='.$uid
		));
		$groups = $this->fetchListOfGroups($filter, array($this->connection->ldapGroupDisplayName, 'dn'));
		$groups = array_unique($this->ownCloudGroupNames($groups), SORT_LOCALE_STRING);
		$this->connection->writeToCache($cacheKey, $groups);

		return $groups;
	}

	/**
	 * @brief get a list of all users in a group
	 * @returns array with user ids
	 */
	public function usersInGroup($gid, $search = '', $limit = -1, $offset = 0) {
		if(!$this->enabled) {
			return array();
		}
		if(!$this->groupExists($gid)) {
			return array();
		}
		$cachekey = 'usersInGroup-'.$gid.'-'.$search.'-'.$limit.'-'.$offset;
		// check for cache of the exact query
		$groupUsers = $this->connection->getFromCache($cachekey);
		if(!is_null($groupUsers)) {
			return $groupUsers;
		}

		// check for cache of the query without limit and offset
		$groupUsers = $this->connection->getFromCache('usersInGroup-'.$gid.'-'.$search);
		if(!is_null($groupUsers)) {
			$groupUsers = array_slice($groupUsers, $offset, $limit);
			$this->connection->writeToCache($cachekey, $groupUsers);
			return $groupUsers;
		}

		if($limit == -1) {
			$limit = null;
		}
		$groupDN = $this->groupname2dn($gid);
		if(!$groupDN) {
			// group couldn't be found, return empty resultset
			$this->connection->writeToCache($cachekey, array());
			return array();
		}

		$members = $this->readAttribute($groupDN, $this->connection->ldapGroupMemberAssocAttr);
		if(!$members) {
			//in case users could not be retrieved, return empty resultset
			$this->connection->writeToCache($cachekey, array());
			return array();
		}

		$groupUsers = array();
		$isMemberUid = (strtolower($this->connection->ldapGroupMemberAssocAttr) == 'memberuid');
		foreach($members as $member) {
			if($isMemberUid) {
				//we got uids, need to get their DNs to 'tranlsate' them to usernames
				$filter = $this->combineFilterWithAnd(array(
					\OCP\Util::mb_str_replace('%uid', $member,
						$this->connection->ldapLoginFilter, 'UTF-8'),
					$this->getFilterPartForUserSearch($search)
				));
				$ldap_users = $this->fetchListOfUsers($filter, 'dn');
				if(count($ldap_users) < 1) {
					continue;
				}
				$groupUsers[] = $this->dn2username($ldap_users[0]);
			} else {
				//we got DNs, check if we need to filter by search or we can give back all of them
				if(!empty($search)) {
					if(!$this->readAttribute($member,
						$this->connection->ldapUserDisplayName,
						$this->getFilterPartForUserSearch($search))) {
						continue;
					}
				}
				// dn2username will also check if the users belong to the allowed base
				if($ocname = $this->dn2username($member)) {
					$groupUsers[] = $ocname;
				}
			}
		}
		natsort($groupUsers);
		$this->connection->writeToCache('usersInGroup-'.$gid.'-'.$search, $groupUsers);
		$groupUsers = array_slice($groupUsers, $offset, $limit);
		$this->connection->writeToCache($cachekey, $groupUsers);

		return $groupUsers;
	}

	/**
	 * @brief get a list of all display names in a group
	 * @returns array with display names (value) and user ids(key)
	 */
	public function displayNamesInGroup($gid, $search, $limit, $offset) {
		if(!$this->enabled) {
			return array();
		}
		if(!$this->groupExists($gid)) {
			return array();
		}
		$users = $this->usersInGroup($gid, $search, $limit, $offset);
		$displayNames = array();
		foreach($users as $user) {
			$displayNames[$user] = \OC_User::getDisplayName($user);
		}
		return $displayNames;
	}

	/**
	 * @brief get a list of all groups
	 * @returns array with group names
	 *
	 * Returns a list with all groups
	 */
	public function getGroups($search = '', $limit = -1, $offset = 0) {
		if(!$this->enabled) {
			return array();
		}
		$cachekey = 'getGroups-'.$search.'-'.$limit.'-'.$offset;

		//Check cache before driving unnecessary searches
		\OCP\Util::writeLog('user_ldap', 'getGroups '.$cachekey, \OCP\Util::DEBUG);
		$ldap_groups = $this->connection->getFromCache($cachekey);
		if(!is_null($ldap_groups)) {
			return $ldap_groups;
		}

		// if we'd pass -1 to LDAP search, we'd end up in a Protocol
		// error. With a limit of 0, we get 0 results. So we pass null.
		if($limit <= 0) {
			$limit = null;
		}
		$filter = $this->combineFilterWithAnd(array(
			$this->connection->ldapGroupFilter,
			$this->getFilterPartForGroupSearch($search)
		));
		\OCP\Util::writeLog('user_ldap', 'getGroups Filter '.$filter, \OCP\Util::DEBUG);
		$ldap_groups = $this->fetchListOfGroups($filter, array($this->connection->ldapGroupDisplayName, 'dn'),
			$limit, $offset);
		$ldap_groups = $this->ownCloudGroupNames($ldap_groups);

		$this->connection->writeToCache($cachekey, $ldap_groups);
		return $ldap_groups;
	}

	public function groupMatchesFilter($group) {
		return (strripos($group, $this->groupSearch) !== false);
	}

	/**
	 * check if a group exists
	 * @param string $gid
	 * @return bool
	 */
	public function groupExists($gid) {
		if($this->connection->isCached('groupExists'.$gid)) {
			return $this->connection->getFromCache('groupExists'.$gid);
		}

		//getting dn, if false the group does not exist. If dn, it may be mapped only, requires more checking.
		$dn = $this->groupname2dn($gid);
		if(!$dn) {
			$this->connection->writeToCache('groupExists'.$gid, false);
			return false;
		}

		//if group really still exists, we will be able to read its objectclass
		$objcs = $this->readAttribute($dn, 'objectclass');
		if(!$objcs || empty($objcs)) {
			$this->connection->writeToCache('groupExists'.$gid, false);
			return false;
		}

		$this->connection->writeToCache('groupExists'.$gid, true);
		return true;
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
		return (bool)(OC_GROUP_BACKEND_GET_DISPLAYNAME	& $actions);
	}
}
