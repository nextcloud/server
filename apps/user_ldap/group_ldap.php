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

class OC_GROUP_LDAP extends OC_Group_Backend {
// 	//group specific settings
	protected $ldapGroupFilter;
	protected $ldapGroupMemberAssocAttr;

	public function __construct() {
		$this->ldapGroupFilter          = OCP\Config::getAppValue('user_ldap', 'ldap_group_filter', '(objectClass=posixGroup)');
		$this->ldapGroupMemberAssocAttr = OCP\Config::getAppValue('user_ldap', 'ldap_group_member_assoc_attribute', 'uniqueMember');
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
		$dn_user = OC_LDAP::username2dn($uid);
		$dn_group = OC_LDAP::groupname2dn($gid);
		// just in case
		if(!$dn_group || !$dn_user) {
			return false;
		}
		//usually, LDAP attributes are said to be case insensitive. But there are exceptions of course.
		$members = OC_LDAP::readAttribute($dn_group, $this->ldapGroupMemberAssocAttr);
		if(!$members) {
			return false;
		}

		//extra work if we don't get back user DNs
		//TODO: this can be done with one LDAP query
		if(strtolower($this->ldapGroupMemberAssocAttr) == 'memberuid') {
			$dns = array();
			foreach($members as $uid) {
				$filter = str_replace('%uid', $uid, OC_LDAP::conf('ldapLoginFilter'));
				$ldap_users = OC_LDAP::fetchListOfUsers($filter, 'dn');
				if(count($ldap_users) < 1) {
					continue;
				}
				$dns[] = $ldap_users[0];
			}
			$members = $dns;
		}

		return in_array($dn_user, $members);
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
		$userDN = OC_LDAP::username2dn($uid);
		if(!$userDN) {
			return array();
		}

		//uniqueMember takes DN, memberuid the uid, so we need to distinguish
		if(strtolower($this->ldapGroupMemberAssocAttr) == 'uniquemember') {
			$uid = $userDN;
		} else if(strtolower($this->ldapGroupMemberAssocAttr) == 'memberuid') {
			$result = OC_LDAP::readAttribute($userDN, 'uid');
			$uid = $result[0];
		} else {
			// just in case
			$uid = $userDN;
		}

		$filter = OC_LDAP::combineFilterWithAnd(array(
			$this->ldapGroupFilter,
			$this->ldapGroupMemberAssocAttr.'='.$uid
		));
		$groups = OC_LDAP::fetchListOfGroups($filter, array(OC_LDAP::conf('ldapGroupDisplayName'),'dn'));
		$userGroups = OC_LDAP::ownCloudGroupNames($groups);

		return array_unique($userGroups, SORT_LOCALE_STRING);
	}

	/**
	 * @brief get a list of all users in a group
	 * @returns array with user ids
	 */
	public function usersInGroup($gid) {
		$groupDN = OC_LDAP::groupname2dn($gid);
		if(!$groupDN) {
			return array();
		}

		$members = OC_LDAP::readAttribute($groupDN, $this->ldapGroupMemberAssocAttr);
		if(!$members) {
			return array();
		}

		$result = array();
		foreach($members as $member) {
			if(strtolower($this->ldapGroupMemberAssocAttr) == 'memberuid') {
				$filter = str_replace('%uid', $member, OC_LDAP::conf('ldapLoginFilter'));
				$ldap_users = OC_LDAP::fetchListOfUsers($filter, 'dn');
				if(count($ldap_users) < 1) {
					continue;
				}
				$result[] = OC_LDAP::dn2username($ldap_users[0]);
				continue;
			}
			//de-facto else
		    $result[] = OC_LDAP::dn2username($member);
		}
		return array_unique($result, SORT_LOCALE_STRING);
	}

	/**
	 * @brief get a list of all groups
	 * @returns array with group names
	 *
	 * Returns a list with all groups
	 */
	public function getGroups() {
		$ldap_groups = OC_LDAP::fetchListOfGroups($this->ldapGroupFilter, array(OC_LDAP::conf('ldapGroupDisplayName'), 'dn'));
		$groups = OC_LDAP::ownCloudGroupNames($ldap_groups);
		return $groups;
	}

	/**
	 * check if a group exists
	 * @param string $gid
	 * @return bool
	 */
	public function groupExists($gid){
		return in_array($gid, $this->getGroups());
	}
}