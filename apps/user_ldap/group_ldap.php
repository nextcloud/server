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

	public function __construct() {
		$this->ldapGroupFilter      = OCP\Config::getAppValue('user_ldap', 'ldap_group_filter', '(objectClass=posixGroup)');
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
		$members = OC_LDAP::readAttribute($dn_group, LDAP_GROUP_MEMBER_ASSOC_ATTR);

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

		$filter = OC_LDAP::combineFilterWithAnd(array(
			$this->ldapGroupFilter,
			LDAP_GROUP_MEMBER_ASSOC_ATTR.'='.$userDN
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
		$members = OC_LDAP::readAttribute($groupDN, LDAP_GROUP_MEMBER_ASSOC_ATTR);
		$result = array();
		foreach($members as $member) {
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