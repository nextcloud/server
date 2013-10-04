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

use OCA\user_ldap\lib\ILDAPWrapper;
use OCA\user_ldap\lib\BackendUtility;

class USER_LDAP extends BackendUtility implements \OCP\UserInterface {

	private function updateQuota($dn) {
		$quota = null;
		$quotaDefault = $this->access->connection->ldapQuotaDefault;
		$quotaAttribute = $this->access->connection->ldapQuotaAttribute;
		if(!empty($quotaDefault)) {
			$quota = $quotaDefault;
		}
		if(!empty($quotaAttribute)) {
			$aQuota = $this->access->readAttribute($dn, $quotaAttribute);

			if($aQuota && (count($aQuota) > 0)) {
				$quota = $aQuota[0];
			}
		}
		if(!is_null($quota)) {
			\OCP\Config::setUserValue(	$this->access->dn2username($dn),
										'files',
										'quota',
										\OCP\Util::computerFileSize($quota));
		}
	}

	private function updateEmail($dn) {
		$email = null;
		$emailAttribute = $this->access->connection->ldapEmailAttribute;
		if(!empty($emailAttribute)) {
			$aEmail = $this->access->readAttribute($dn, $emailAttribute);
			if($aEmail && (count($aEmail) > 0)) {
				$email = $aEmail[0];
			}
			if(!is_null($email)) {
				\OCP\Config::setUserValue(	$this->access->dn2username($dn),
											'settings',
											'email',
											$email);
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
		$filter = \OCP\Util::mb_str_replace(
			'%uid', $uid, $this->access->connection->ldapLoginFilter, 'UTF-8');
		$ldap_users = $this->access->fetchListOfUsers($filter, 'dn');
		if(count($ldap_users) < 1) {
			return false;
		}
		$dn = $ldap_users[0];

		//do we have a username for him/her?
		$ocname = $this->access->dn2username($dn);

		if($ocname) {
			//update some settings, if necessary
			$this->updateQuota($dn);
			$this->updateEmail($dn);

			//are the credentials OK?
			if(!$this->access->areCredentialsValid($dn, $password)) {
				return false;
			}

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
		$ldap_users = $this->access->connection->getFromCache($cachekey);
		if(!is_null($ldap_users)) {
			return $ldap_users;
		}

		// if we'd pass -1 to LDAP search, we'd end up in a Protocol
		// error. With a limit of 0, we get 0 results. So we pass null.
		if($limit <= 0) {
			$limit = null;
		}
		$filter = $this->access->combineFilterWithAnd(array(
			$this->access->connection->ldapUserFilter,
			$this->access->getFilterPartForUserSearch($search)
		));

		\OCP\Util::writeLog('user_ldap',
			'getUsers: Options: search '.$search.' limit '.$limit.' offset '.$offset.' Filter: '.$filter,
			\OCP\Util::DEBUG);
		//do the search and translate results to owncloud names
		$ldap_users = $this->access->fetchListOfUsers(
			$filter,
			array($this->access->connection->ldapUserDisplayName, 'dn'),
			$limit, $offset);
		$ldap_users = $this->access->ownCloudUserNames($ldap_users);
		\OCP\Util::writeLog('user_ldap', 'getUsers: '.count($ldap_users). ' Users found', \OCP\Util::DEBUG);

		$this->access->connection->writeToCache($cachekey, $ldap_users);
		return $ldap_users;
	}

	/**
	 * @brief check if a user exists
	 * @param string $uid the username
	 * @return boolean
	 */
	public function userExists($uid) {
		if($this->access->connection->isCached('userExists'.$uid)) {
			return $this->access->connection->getFromCache('userExists'.$uid);
		}
		//getting dn, if false the user does not exist. If dn, he may be mapped only, requires more checking.
		$dn = $this->access->username2dn($uid);
		if(!$dn) {
			\OCP\Util::writeLog('user_ldap', 'No DN found for '.$uid.' on '.
				$this->access->connection->ldapHost, \OCP\Util::DEBUG);
			$this->access->connection->writeToCache('userExists'.$uid, false);
			return false;
		}
		//check if user really still exists by reading its entry
		if(!is_array($this->access->readAttribute($dn, ''))) {
			\OCP\Util::writeLog('user_ldap', 'LDAP says no user '.$dn, \OCP\Util::DEBUG);
			$this->access->connection->writeToCache('userExists'.$uid, false);
			return false;
		}

		$this->access->connection->writeToCache('userExists'.$uid, true);
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
		if($this->access->connection->isCached($cacheKey)) {
			return $this->access->connection->getFromCache($cacheKey);
		}
		if(strpos($this->access->connection->homeFolderNamingRule, 'attr:') === 0) {
			$attr = substr($this->access->connection->homeFolderNamingRule, strlen('attr:'));
			$homedir = $this->access->readAttribute(
						$this->access->username2dn($uid), $attr);
			if($homedir && isset($homedir[0])) {
				$path = $homedir[0];
				//if attribute's value is an absolute path take this, otherwise append it to data dir
				//check for / at the beginning or pattern c:\ resp. c:/
				if(
					'/' === $path[0]
					|| (3 < strlen($path) && ctype_alpha($path[0])
						&& $path[1] === ':' && ('\\' === $path[2] || '/' === $path[2]))
				) {
					$homedir = $path;
				} else {
					$homedir = \OCP\Config::getSystemValue('datadirectory',
						\OC::$SERVERROOT.'/data' ) . '/' . $homedir[0];
				}
				$this->access->connection->writeToCache($cacheKey, $homedir);
				return $homedir;
			}
		}

		//false will apply default behaviour as defined and done by OC_User
		$this->access->connection->writeToCache($cacheKey, false);
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
		if(!is_null($displayName = $this->access->connection->getFromCache($cacheKey))) {
			return $displayName;
		}

		$displayName = $this->access->readAttribute(
			$this->access->username2dn($uid),
			$this->access->connection->ldapUserDisplayName);

		if($displayName && (count($displayName) > 0)) {
			$this->access->connection->writeToCache($cacheKey, $displayName[0]);
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
		if(!is_null($displayNames = $this->access->connection->getFromCache($cacheKey))) {
			return $displayNames;
		}

		$displayNames = array();
		$users = $this->getUsers($search, $limit, $offset);
		foreach ($users as $user) {
			$displayNames[$user] = $this->getDisplayName($user);
		}
		$this->access->connection->writeToCache($cacheKey, $displayNames);
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
