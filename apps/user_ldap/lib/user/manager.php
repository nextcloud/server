<?php
/**
 * @author Arthur Schiwon <blizzz@owncloud.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Morris Jobke <hey@morrisjobke.de>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\user_ldap\lib\user;

use OCA\user_ldap\lib\user\IUserTools;
use OCA\user_ldap\lib\user\User;
use OCA\user_ldap\lib\LogWrapper;
use OCA\user_ldap\lib\FilesystemHelper;
use OCA\user_ldap\lib\user\OfflineUser;

/**
 * Manager
 *
 * upon request, returns an LDAP user object either by creating or from run-time
 * cache
 */
class Manager {
	/** @var IUserTools */
	protected $access;

	/** @var \OCP\IConfig */
	protected $ocConfig;

	/** @var \OCP\IDBConnection */
	protected $db;

	/** @var FilesystemHelper */
	protected $ocFilesystem;

	/** @var LogWrapper */
	protected $ocLog;

	/** @var \OCP\Image */
	protected $image;

	/** @param \OCP\IAvatarManager */
	protected $avatarManager;

	/**
	 * array['byDN']	\OCA\user_ldap\lib\User[]
	 * 	['byUid']	\OCA\user_ldap\lib\User[]
	 * @var array $users
	 */
	protected $users = array(
		'byDN'  => array(),
		'byUid' => array(),
	);

	/**
	 * @param \OCP\IConfig $ocConfig
	 * @param \OCA\user_ldap\lib\FilesystemHelper $ocFilesystem object that
	 * gives access to necessary functions from the OC filesystem
	 * @param  \OCA\user_ldap\lib\LogWrapper $ocLog
	 * @param \OCP\IAvatarManager $avatarManager
	 * @param \OCP\Image $image an empty image instance
	 * @param \OCP\IDBConnection $db
	 * @throws Exception when the methods mentioned above do not exist
	 */
	public function __construct(\OCP\IConfig $ocConfig,
		FilesystemHelper $ocFilesystem, LogWrapper $ocLog,
		\OCP\IAvatarManager $avatarManager, \OCP\Image $image, \OCP\IDBConnection $db) {

		$this->ocConfig      = $ocConfig;
		$this->ocFilesystem  = $ocFilesystem;
		$this->ocLog         = $ocLog;
		$this->avatarManager = $avatarManager;
		$this->image         = $image;
		$this->db            = $db;
	}

	/**
	 * @brief binds manager to an instance of IUserTools (implemented by
	 * Access). It needs to be assigned first before the manager can be used.
	 * @param IUserTools
	 */
	public function setLdapAccess(IUserTools $access) {
		$this->access = $access;
	}

	/**
	 * @brief creates an instance of User and caches (just runtime) it in the
	 * property array
	 * @param string the DN of the user
	 * @param string the internal (owncloud) username
	 * @return \OCA\user_ldap\lib\User
	 */
	private function createAndCache($dn, $uid) {
		$this->checkAccess();
		$user = new User($uid, $dn, $this->access, $this->ocConfig,
			$this->ocFilesystem, clone $this->image, $this->ocLog,
			$this->avatarManager);
		$this->users['byDN'][$dn]   = $user;
		$this->users['byUid'][$uid] = $user;
		return $user;
	}

	/**
	 * @brief checks whether the Access instance has been set
	 * @throws Exception if Access has not been set
	 * @return null
	 */
	private function checkAccess() {
		if(is_null($this->access)) {
			throw new \Exception('LDAP Access instance must be set first');
		}
	}

	/**
	 * returns a list of attributes that will be processed further, e.g. quota,
	 * email, displayname, or others.
	 * @param bool $minimal - optional, set to true to skip attributes with big
	 * payload
	 * @return string[]
	 */
	public function getAttributes($minimal = false) {
		$attributes = array('dn', 'uid', 'samaccountname', 'memberof');
		$possible = array(
			$this->access->getConnection()->ldapQuotaAttribute,
			$this->access->getConnection()->ldapEmailAttribute,
			$this->access->getConnection()->ldapUserDisplayName,
			$this->access->getConnection()->ldapUserDisplayName2,
		);
		foreach($possible as $attr) {
			if(!is_null($attr)) {
				$attributes[] = $attr;
			}
		}

		$homeRule = $this->access->getConnection()->homeFolderNamingRule;
		if(strpos($homeRule, 'attr:') === 0) {
			$attributes[] = substr($homeRule, strlen('attr:'));
		}

		if(!$minimal) {
			// attributes that are not really important but may come with big
			// payload.
			$attributes = array_merge($attributes, array(
				'jpegphoto',
				'thumbnailphoto'
			));
		}

		return $attributes;
	}

	/**
	 * Checks whether the specified user is marked as deleted
	 * @param string $id the ownCloud user name
	 * @return bool
	 */
	public function isDeletedUser($id) {
		$isDeleted = $this->ocConfig->getUserValue(
			$id, 'user_ldap', 'isDeleted', 0);
		return intval($isDeleted) === 1;
	}

	/**
	 * creates and returns an instance of OfflineUser for the specified user
	 * @param string $id
	 * @return \OCA\user_ldap\lib\user\OfflineUser
	 */
	public function getDeletedUser($id) {
		return new OfflineUser(
			$id,
			$this->ocConfig,
			$this->db,
			$this->access->getUserMapper());
	}

	/**
	 * @brief returns a User object by it's ownCloud username
	 * @param string the DN or username of the user
	 * @return \OCA\user_ldap\lib\user\User|\OCA\user_ldap\lib\user\OfflineUser|null
	 */
	protected function createInstancyByUserName($id) {
		//most likely a uid. Check whether it is a deleted user
		if($this->isDeletedUser($id)) {
			return $this->getDeletedUser($id);
		}
		$dn = $this->access->username2dn($id);
		if($dn !== false) {
			return $this->createAndCache($dn, $id);
		}
		return null;
	}

	/**
	 * @brief returns a User object by it's DN or ownCloud username
	 * @param string the DN or username of the user
	 * @return \OCA\user_ldap\lib\user\User|\OCA\user_ldap\lib\user\OfflineUser|null
	 * @throws \Exception when connection could not be established
	 */
	public function get($id) {
		$this->checkAccess();
		if(isset($this->users['byDN'][$id])) {
			return $this->users['byDN'][$id];
		} else if(isset($this->users['byUid'][$id])) {
			return $this->users['byUid'][$id];
		}

		if($this->access->stringResemblesDN($id) ) {
			$uid = $this->access->dn2username($id);
			if($uid !== false) {
				return $this->createAndCache($id, $uid);
			}
		}

		return $this->createInstancyByUserName($id);
	}

}
