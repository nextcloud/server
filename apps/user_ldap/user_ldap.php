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
		$quotaDefault = $this->connection->ldapQuotaDefault;
		$quotaAttribute = $this->connection->ldapQuotaAttribute;
		if(!empty($quotaDefault)) {
			$quota = $quotaDefault;
		}
		if(!empty($quotaAttribute)) {
			$aQuota = $this->readAttribute($dn, $quotaAttribute);

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
		$emailAttribute = $this->connection->ldapEmailAttribute;
		if(!empty($emailAttribute)) {
			$aEmail = $this->readAttribute($dn, $emailAttribute);
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
	public function checkPassword($uid, $password) {
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

		if($ocname) {
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
	public function getUsers($search = '', $limit = 10, $offset = 0) {
		$cachekey = 'getUsers-'.$search.'-'.$limit.'-'.$offset;

		//check if users are cached, if so return
		$ldap_users = $this->connection->getFromCache($cachekey);
		if(!is_null($ldap_users)) {
			return $ldap_users;
		}

		// if we'd pass -1 to LDAP search, we'd end up in a Protocol
		// error. With a limit of 0, we get 0 results. So we pass null.
		if($limit <= 0) {
			$limit = null;
		}
		$filter = $this->combineFilterWithAnd(array(
			$this->connection->ldapUserFilter,
			$this->getFilterPartForUserSearch($search)
		));

		\OCP\Util::writeLog('user_ldap',
			'getUsers: Options: search '.$search.' limit '.$limit.' offset '.$offset.' Filter: '.$filter,
			\OCP\Util::DEBUG);
		//do the search and translate results to owncloud names
		$ldap_users = $this->fetchListOfUsers($filter, array($this->connection->ldapUserDisplayName, 'dn'),
			$limit, $offset);
		$ldap_users = $this->ownCloudUserNames($ldap_users);
		\OCP\Util::writeLog('user_ldap', 'getUsers: '.count($ldap_users). ' Users found', \OCP\Util::DEBUG);

		$this->connection->writeToCache($cachekey, $ldap_users);
		return $ldap_users;
	}

	/**
	 * @brief check if a user exists
	 * @param string $uid the username
	 * @return boolean
	 */
	public function userExists($uid) {
		if($this->connection->isCached('userExists'.$uid)) {
			return $this->connection->getFromCache('userExists'.$uid);
		}

		//getting dn, if false the user does not exist. If dn, he may be mapped only, requires more checking.
		$dn = $this->username2dn($uid);
		if(!$dn) {
			$this->connection->writeToCache('userExists'.$uid, false);
			return false;
		}

		//check if user really still exists by reading its entry
		if(!is_array($this->readAttribute($dn, ''))) {
			$this->connection->writeToCache('userExists'.$uid, false);
			return false;
		}

		$this->connection->writeToCache('userExists'.$uid, true);
		$this->updateQuota($dn);
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
	* @brief get the user's home directory
	* @param string $uid the username
	* @return boolean
	*/
	public function getHome($uid) {
		// user Exists check required as it is not done in user proxy!
		if(!$this->userExists($uid)) {
			return false;
		}

		$cacheKey = 'getHome'.$uid;
		if($this->connection->isCached($cacheKey)) {
			return $this->connection->getFromCache($cacheKey);
		}
		if(strpos($this->connection->homeFolderNamingRule, 'attr:') === 0) {
			$attr = substr($this->connection->homeFolderNamingRule, strlen('attr:'));
			$homedir = $this->readAttribute($this->username2dn($uid), $attr);
			if($homedir && isset($homedir[0])) {
				$path = $homedir[0];
				//if attribute's value is an absolute path take this, otherwise append it to data dir
				//check for / at the beginning or pattern c:\ resp. c:/
				if(
					'/' == $path[0]
					|| (3 < strlen($path) && ctype_alpha($path[0])
						&& $path[1] == ':' && ('\\' == $path[2] || '/' == $path[2]))
				) {
					$homedir = $path;
				} else {
					$homedir = \OCP\Config::getSystemValue('datadirectory',
						\OC::$SERVERROOT.'/data' ) . '/' . $homedir[0];
				}
				$this->connection->writeToCache($cacheKey, $homedir);
				return $homedir;
			}
		}

		//false will apply default behaviour as defined and done by OC_User
		$this->connection->writeToCache($cacheKey, false);
		return false;
	}

	/**
	 * @brief get display name of the user
	 * @param $uid user ID of the user
	 * @return display name
	 */
	public function getDisplayName($uid) {
		if(!$this->userExists($uid)) {
			return false;
		}

		$cacheKey = 'getDisplayName'.$uid;
		if(!is_null($displayName = $this->connection->getFromCache($cacheKey))) {
			return $displayName;
		}

		$displayName = $this->readAttribute(
			$this->username2dn($uid),
			$this->connection->ldapUserDisplayName);

		if($displayName && (count($displayName) > 0)) {
			$this->connection->writeToCache($cacheKey, $displayName[0]);
			return $displayName[0];
		}

		return null;
	}

	/**
	 * @brief Get a list of all display names
	 * @returns array with  all displayNames (value) and the correspondig uids (key)
	 *
	 * Get a list of all display names and user ids.
	 */
	public function getDisplayNames($search = '', $limit = null, $offset = null) {
		$cacheKey = 'getDisplayNames-'.$search.'-'.$limit.'-'.$offset;
		if(!is_null($displayNames = $this->connection->getFromCache($cacheKey))) {
			return $displayNames;
		}

		$displayNames = array();
		$users = $this->getUsers($search, $limit, $offset);
		foreach ($users as $user) {
			$displayNames[$user] = $this->getDisplayName($user);
		}
		$this->connection->writeToCache($cacheKey, $displayNames);
		return $displayNames;
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
		return (bool)((OC_USER_BACKEND_CHECK_PASSWORD
			| OC_USER_BACKEND_GET_HOME
			| OC_USER_BACKEND_GET_DISPLAYNAME)
			& $actions);
	}

	/**
	 * @return bool
	 */
	public function hasUserListings() {
		return true;
	}
}
