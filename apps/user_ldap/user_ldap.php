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
	 * reads jpegPhoto and set is as avatar if available
	 * @param string $uid ownCloud user name
	 * @param string $dn the user's LDAP DN
	 * @return void
	 */
	private function updateAvatar($uid, $dn) {
		$hasLoggedIn = \OCP\Config::getUserValue($uid, 'user_ldap',
												 'firstLoginAccomplished', 0);
		$lastChecked = \OCP\Config::getUserValue($uid, 'user_ldap',
												 'lastJpegPhotoLookup', 0);
		if(($hasLoggedIn !== '1') || (time() - intval($lastChecked)) < 86400 ) {
			//update only once a day
			return;
		}

		$avatarImage = $this->getAvatarImage($uid, $dn);
		if($avatarImage === false) {
			//not set, nothing left to do;
			return;
		}

		$image = new \OCP\Image();
		$image->loadFromBase64(base64_encode($avatarImage));

		if(!$image->valid()) {
			\OCP\Util::writeLog('user_ldap', 'jpegPhoto data invalid for '.$dn,
								\OCP\Util::ERROR);
			return;
		}
		//make sure it is a square and not bigger than 128x128
		$size = min(array($image->width(), $image->height(), 128));
		if(!$image->centerCrop($size)) {
			\OCP\Util::writeLog('user_ldap',
								'croping image for avatar failed for '.$dn,
								\OCP\Util::ERROR);
			return;
		}

		if(!\OC\Files\Filesystem::$loaded) {
			\OC_Util::setupFS($uid);
		}

		$avatarManager = \OC::$server->getAvatarManager();
		$avatar = $avatarManager->getAvatar($uid);
		$avatar->set($image);
	}

	/**
	 * checks whether the user is allowed to change his avatar in ownCloud
	 * @param string $uid the ownCloud user name
	 * @return boolean either the user can or cannot
	 */
	public function canChangeAvatar($uid) {
		$dn = $this->access->username2dn($uid);
		if(!$dn) {
			return false;
		}
		if($this->getAvatarImage($uid, $dn) === false) {
			//The user is allowed to change his avatar in ownCloud only if no
			//avatar is provided by LDAP
			return true;
		}
		return false;
	}

	/**
	 * reads the image from LDAP that shall be used as Avatar
	 * @param string $uid the ownCloud user name
	 * @param string $dn the user DN
	 * @return string data (provided by LDAP) | false
	 */
	private function getAvatarImage($uid, $dn) {
		$attributes = array('jpegPhoto', 'thumbnailPhoto');
		foreach($attributes as $attribute) {
			$result = $this->access->readAttribute($dn, $attribute);
			\OCP\Config::setUserValue($uid, 'user_ldap', 'lastJpegPhotoLookup',
									  time());
			if($result !== false && is_array($result) && isset($result[0])) {
				return $result[0];
			}
		}

		return false;
	}

	/**
	 * Check if the password is correct
	 * @param string $uid The username
	 * @param string $password The password
	 * @return boolean
	 *
	 * Check if the password is correct without logging in the user
	 */
	public function checkPassword($uid, $password) {
		$uid = $this->access->escapeFilterPart($uid);

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

			\OCP\Config::setUserValue($ocname, 'user_ldap',
									  'firstLoginAccomplished', 1);

			$this->updateAvatar($ocname, $dn);
			//give back the display name
			return $ocname;
		}

		return false;
	}

	/**
	 * Get a list of all users
	 * @return string[] with all uids
	 *
	 * Get a list of all users.
	 */
	public function getUsers($search = '', $limit = 10, $offset = 0) {
		$search = $this->access->escapeFilterPart($search);
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
	 * check if a user exists
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
			\OCP\Util::writeLog('user_ldap', 'LDAP says no user '.$dn.' on '.
				$this->access->connection->ldapHost, \OCP\Util::DEBUG);
			$this->access->connection->writeToCache('userExists'.$uid, false);
			return false;
		}

		$this->access->connection->writeToCache('userExists'.$uid, true);
		$this->updateQuota($dn);
		$this->updateAvatar($uid, $dn);
		return true;
	}

	/**
	* delete a user
	* @param string $uid The username of the user to delete
	* @return bool
	*
	* Deletes a user
	*/
	public function deleteUser($uid) {
		return false;
	}

	/**
	* get the user's home directory
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
	 * get display name of the user
	 * @param string $uid user ID of the user
	 * @return string display name
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
	 * Get a list of all display names
	 * @return array with all displayNames (value) and the correspondig uids (key)
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
	* Check if backend implements actions
	* @param int $actions bitwise-or'ed actions
	* @return boolean
	*
	* Returns the supported actions as int to be
	* compared with OC_USER_BACKEND_CREATE_USER etc.
	*/
	public function implementsActions($actions) {
		return (bool)((OC_USER_BACKEND_CHECK_PASSWORD
			| OC_USER_BACKEND_GET_HOME
			| OC_USER_BACKEND_GET_DISPLAYNAME
			| OC_USER_BACKEND_PROVIDE_AVATAR
			| OC_USER_BACKEND_COUNT_USERS)
			& $actions);
	}

	/**
	 * @return bool
	 */
	public function hasUserListings() {
		return true;
	}

	/**
	 * counts the users in LDAP
	 *
	 * @return int|bool
	 */
	public function countUsers() {
		$filter = \OCP\Util::mb_str_replace(
			'%uid', '*', $this->access->connection->ldapLoginFilter, 'UTF-8');
		$entries = $this->access->countUsers($filter);
		return $entries;
	}
}
