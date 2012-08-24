<?php

/**
 * ownCloud
 *
 * @author Dominik Schmidt
 * @author Artuhr Schiwon
 * @copyright 2011 Dominik Schmidt dev@dominik-schmidt.de
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

class USER_LDAP extends lib\Access implements \OCP\UserInterface {

	private function updateQuota($dn) {
		$quota = null;
		if(!empty($this->connection->ldapQuotaDefault)) {
			$quota = $this->connection->ldapQuotaDefault;
		}
		if(!empty($this->connection->ldapQuotaAttribute)) {
			$aQuota = $this->readAttribute($dn, $this->connection->ldapQuotaAttribute);

			if($aQuota && (count($aQuota) > 0)) {
				$quota = $aQuota[0];
			}
		}
		if(!is_null($quota)) {
			\OCP\Config::setUserValue($this->dn2username($dn), 'files', 'quota', \OCP\Util::computerFileSize($quota));
		}
	}

	private function updateEmail($dn) {
		$email = null;
		if(!empty($this->connection->ldapEmailAttribute)) {
			$aEmail = $this->readAttribute($dn, $this->connection->ldapEmailAttribute);
			if($aEmail && (count($aEmail) > 0)) {
				$email = $aEmail[0];
			}
			if(!is_null($email)) {
				\OCP\Config::setUserValue($this->dn2username($dn), 'settings', 'email', $email);
			}
		}
	}

	/**
	 * @brief Check if the password is correct
	 * @param $uid The username
	 * @param $password The password
	 * @returns true/false
	 *
	 * Check if the password is correct without logging in the user
	 */
	public function checkPassword($uid, $password){
		//find out dn of the user name
		$filter = \OCP\Util::mb_str_replace('%uid', $uid, $this->connection->ldapLoginFilter, 'UTF-8');
		$ldap_users = $this->fetchListOfUsers($filter, 'dn');
		if(count($ldap_users) < 1) {
			return false;
		}
		$dn = $ldap_users[0];

		//are the credentials OK?
		if(!$this->areCredentialsValid($dn, $password)) {
			return false;
		}

		//do we have a username for him/her?
		$ocname = $this->dn2username($dn);

		if($ocname){
			//update some settings, if necessary
			$this->updateQuota($dn);
			$this->updateEmail($dn);

			//give back the display name
			return $ocname;
		}

		return false;
	}

	/**
	 * @brief Get a list of all users
	 * @returns array with all uids
	 *
	 * Get a list of all users.
	 */
	public function getUsers($search = '', $limit = 10, $offset = 0){
		$ldap_users = $this->connection->getFromCache('getUsers');
		if(is_null($ldap_users)) {
			$ldap_users = $this->fetchListOfUsers($this->connection->ldapUserFilter, array($this->connection->ldapUserDisplayName, 'dn'));
			$ldap_users = $this->ownCloudUserNames($ldap_users);
			$this->connection->writeToCache('getUsers', $ldap_users);
		}
		$this->userSearch = $search;
		if(!empty($this->userSearch)) {
			$ldap_users = array_filter($ldap_users, array($this, 'userMatchesFilter'));
		}
		if($limit = -1) {
			$limit = null;
		}
		return array_slice($ldap_users, $offset, $limit);
	}

	public function userMatchesFilter($user) {
		return (strripos($user, $this->userSearch) !== false);
	}

	/**
	 * @brief check if a user exists
	 * @param string $uid the username
	 * @return boolean
	 */
	public function userExists($uid){
		if($this->connection->isCached('userExists'.$uid)) {
			return $this->connection->getFromCache('userExists'.$uid);
		}

		//getting dn, if false the user does not exist. If dn, he may be mapped only, requires more checking.
		$dn = $this->username2dn($uid);
		if(!$dn) {
			$this->connection->writeToCache('userExists'.$uid, false);
			return false;
		}

		//if user really still exists, we will be able to read his objectclass
		$objcs = $this->readAttribute($dn, 'objectclass');
		if(!$objcs || empty($objcs)) {
			$this->connection->writeToCache('userExists'.$uid, false);
			return false;
		}

		$this->connection->writeToCache('userExists'.$uid, true);
		return true;
	}

	/**
	* @brief delete a user
	* @param $uid The username of the user to delete
	* @returns true/false
	*
	* Deletes a user
	*/
	public function deleteUser($uid) {
		return false;
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
		return (bool)(OC_USER_BACKEND_CHECK_PASSWORD & $actions);
	}

}