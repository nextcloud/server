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
use OCA\user_ldap\lib\Access;
use OCA\user_ldap\lib\user\OfflineUser;
use OCA\User_LDAP\lib\User\User;
use OCP\IConfig;

class USER_LDAP extends BackendUtility implements \OCP\IUserBackend, \OCP\UserInterface {
	/** @var string[] $homesToKill */
	protected $homesToKill = array();

	/** @var \OCP\IConfig */
	protected $ocConfig;

	/**
	 * @param \OCA\user_ldap\lib\Access $access
	 * @param \OCP\IConfig $ocConfig
	 */
	public function __construct(Access $access, IConfig $ocConfig) {
		parent::__construct($access);
		$this->ocConfig = $ocConfig;
	}

	/**
	 * checks whether the user is allowed to change his avatar in ownCloud
	 * @param string $uid the ownCloud user name
	 * @return boolean either the user can or cannot
	 */
	public function canChangeAvatar($uid) {
		$user = $this->access->userManager->get($uid);
		if(!$user instanceof User) {
			return false;
		}
		if($user->getAvatarImage() === false) {
			return true;
		}

		return false;
	}

	/**
	 * Check if the password is correct
	 * @param string $uid The username
	 * @param string $password The password
	 * @return false|string
	 *
	 * Check if the password is correct without logging in the user
	 */
	public function checkPassword($uid, $password) {
		$uid = $this->access->escapeFilterPart($uid);

		//find out dn of the user name
		$attrs = array($this->access->connection->ldapUserDisplayName, 'dn',
			'uid', 'samaccountname');
		$filter = \OCP\Util::mb_str_replace(
			'%uid', $uid, $this->access->connection->ldapLoginFilter, 'UTF-8');
		$users = $this->access->fetchListOfUsers($filter, $attrs);
		if(count($users) < 1) {
			return false;
		}
		$dn = $users[0]['dn'];
		$user = $this->access->userManager->get($dn);
		if(!$user instanceof User) {
			\OCP\Util::writeLog('user_ldap',
				'LDAP Login: Could not get user object for DN ' . $dn .
				'. Maybe the LDAP entry has no set display name attribute?',
				\OCP\Util::WARN);
			return false;
		}
		if($user->getUsername() !== false) {
			//are the credentials OK?
			if(!$this->access->areCredentialsValid($dn, $password)) {
				return false;
			}

			$user->markLogin();
			if(isset($users[0][$this->access->connection->ldapUserDisplayName])) {
				$dpn = $users[0][$this->access->connection->ldapUserDisplayName];
				$user->storeDisplayName($dpn);
			}
			if(isset($users[0]['uid'])) {
				$user->storeLDAPUserName($users[0]['uid']);
			} else if(isset($users[0]['samaccountname'])) {
				$user->storeLDAPUserName($users[0]['samaccountname']);
			}

			return $user->getUsername();
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
		$search = $this->access->escapeFilterPart($search, true);
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
	 * checks whether a user is still available on LDAP
	 * @param string|\OCA\User_LDAP\lib\user\User $user either the ownCloud user
	 * name or an instance of that user
	 * @return bool
	 */
	public function userExistsOnLDAP($user) {
		if(is_string($user)) {
			$user = $this->access->userManager->get($user);
		}
		if(!$user instanceof User) {
			return false;
		}

		$dn = $user->getDN();
		//check if user really still exists by reading its entry
		if(!is_array($this->access->readAttribute($dn, ''))) {
			$lcr = $this->access->connection->getConnectionResource();
			if(is_null($lcr)) {
				throw new \Exception('No LDAP Connection to server ' . $this->access->connection->ldapHost);
			}
			return false;
		}

		return true;
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
		$user = $this->access->userManager->get($uid);
		if(is_null($user)) {
			\OCP\Util::writeLog('user_ldap', 'No DN found for '.$uid.' on '.
				$this->access->connection->ldapHost, \OCP\Util::DEBUG);
			$this->access->connection->writeToCache('userExists'.$uid, false);
			return false;
		} else if($user instanceof OfflineUser) {
			//express check for users marked as deleted. Returning true is
			//necessary for cleanup
			return true;
		}

		try {
			$result = $this->userExistsOnLDAP($user);
			$this->access->connection->writeToCache('userExists'.$uid, $result);
			if($result === true) {
				$user->update();
			}
			return $result;
		} catch (\Exception $e) {
			\OCP\Util::writeLog('user_ldap', $e->getMessage(), \OCP\Util::WARN);
			return false;
		}
	}

	/**
	* returns whether a user was deleted in LDAP
	*
	* @param string $uid The username of the user to delete
	* @return bool
	*/
	public function deleteUser($uid) {
		$marked = $this->ocConfig->getUserValue($uid, 'user_ldap', 'isDeleted', 0);
		if(intval($marked) === 0) {
			\OC::$server->getLogger()->notice(
				'User '.$uid . ' is not marked as deleted, not cleaning up.',
				array('app' => 'user_ldap'));
			return false;
		}
		\OC::$server->getLogger()->info('Cleaning up after user ' . $uid,
			array('app' => 'user_ldap'));

		//Get Home Directory out of user preferences so we can return it later,
		//necessary for removing directories as done by OC_User.
		$home = $this->ocConfig->getUserValue($uid, 'user_ldap', 'homePath', '');
		$this->homesToKill[$uid] = $home;
		$this->access->getUserMapper()->unmap($uid);

		return true;
	}

	/**
	* get the user's home directory
	* @param string $uid the username
	* @return string|bool
	*/
	public function getHome($uid) {
		// user Exists check required as it is not done in user proxy!
		if(!$this->userExists($uid)) {
			return false;
		}

		if(isset($this->homesToKill[$uid]) && !empty($this->homesToKill[$uid])) {
			//a deleted user who needs some clean up
			return $this->homesToKill[$uid];
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
					$homedir = $this->ocConfig->getSystemValue('datadirectory',
						\OC::$SERVERROOT.'/data' ) . '/' . $homedir[0];
				}
				$this->access->connection->writeToCache($cacheKey, $homedir);
				//we need it to store it in the DB as well in case a user gets
				//deleted so we can clean up afterwards
				$this->ocConfig->setUserValue(
					$uid, 'user_ldap', 'homePath', $homedir
				);
				//TODO: if home directory changes, the old one needs to be removed.
				return $homedir;
			}
		}

		//false will apply default behaviour as defined and done by OC_User
		$this->access->connection->writeToCache($cacheKey, false);
		$this->ocConfig->setUserValue($uid, 'user_ldap', 'homePath', '');
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
		$filter = $this->access->getFilterForUserCount();
		$cacheKey = 'countUsers-'.$filter;
		if(!is_null($entries = $this->access->connection->getFromCache($cacheKey))) {
			return $entries;
		}
		$entries = $this->access->countUsers($filter);
		$this->access->connection->writeToCache($cacheKey, $entries);
		return $entries;
	}

	/**
	 * Backend name to be shown in user management
	 * @return string the name of the backend to be shown
	 */
	public function getBackendName(){
		return 'LDAP';
	}

}
