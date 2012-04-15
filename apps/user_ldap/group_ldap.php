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
	protected $ldapGroupDisplayName;

	public function __construct() {
		$this->ldapGroupFilter      = OC_Appconfig::getValue('user_ldap', 'ldap_group_filter', '(objectClass=posixGroup)');
		$this->ldapGroupDisplayName = OC_Appconfig::getValue('user_ldap', 'ldap_group_display_name', 'cn');
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
		$filter = OC_LDAP::combineFilterWithAnd(array(
			$this->ldapGroupFilter,
			LDAP_GROUP_MEMBER_ASSOC_ATTR.'='.$uid,
			$this->ldapGroupDisplayName.'='.$gid
		));
		$groups = OC_LDAP::search($filter, $this->ldapGroupDisplayName);

		if(count($groups) == 1) {
			return true;
		} else if(count($groups) < 1) {
			return false;
		} else {
			throw new Exception('Too many groups of the same name!? – this exception should never been thrown :)');
		}
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
		return array();
	}

	/**
	 * @brief get a list of all users in a group
	 * @returns array with user ids
	 */
	public function getUsersInGroup($gid) {
		return array();
	}

	/**
	 * @brief get a list of all groups
	 * @returns array with group names
	 *
	 * Returns a list with all groups
	 */
	public function getGroups() {
		$groups = OC_LDAP::search($this->ldapGroupFilter, $this->ldapGroupDisplayName);

		if(count($groups) == 0 )
			return array();
		else {
			return array_unique($groups, SORT_LOCALE_STRING);
		}
	}

}