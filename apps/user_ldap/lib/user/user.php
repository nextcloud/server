<?php

/**
 * ownCloud – LDAP User
 *
 * @author Arthur Schiwon
 * @copyright 2014 Arthur Schiwon blizzz@owncloud.com
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

namespace OCA\user_ldap\lib\user;

use OCA\user_ldap\lib\user\IUserTools;
use OCA\user_ldap\lib\Connection;
use OCA\user_ldap\lib\FilesystemHelper;
use OCA\user_ldap\lib\LogWrapper;

/**
 * User
 *
 * represents an LDAP user, gets and holds user-specific information from LDAP
 */
class User {
	/**
	 * @var IUserTools
	 */
	protected $access;
	/**
	 * @var Connection
	 */
	protected $connection;
	/**
	 * @var \OCP\IConfig
	 */
	protected $config;
	/**
	 * @var FilesystemHelper
	 */
	protected $fs;
	/**
	 * @var \OCP\Image
	 */
	protected $image;
	/**
	 * @var LogWrapper
	 */
	protected $log;
	/**
	 * @var \OCP\IAvatarManager
	 */
	protected $avatarManager;

	/**
	 * @var string
	 */
	protected $dn;
	/**
	 * @var string
	 */
	protected $uid;
	/**
	 * @var string[]
	 */
	protected $refreshedFeatures = array();
	/**
	 * @var string
	 */
	protected $avatarImage;

	/**
	 * DB config keys for user preferences
	 */
	const USER_PREFKEY_FIRSTLOGIN  = 'firstLoginAccomplished';
	const USER_PREFKEY_LASTREFRESH = 'lastFeatureRefresh';

	/**
	 * @brief constructor, make sure the subclasses call this one!
	 * @param string the internal username
	 * @param string the LDAP DN
	 * @param IUserTools $access an instance that implements IUserTools for
	 * LDAP interaction
	 * @param \OCP\IConfig
	 * @param FilesystemHelper
	 * @param \OCP\Image any empty instance
	 * @param LogWrapper
	 * @param \OCP\IAvatarManager
	 */
	public function __construct($username, $dn, IUserTools $access,
		\OCP\IConfig $config, FilesystemHelper $fs, \OCP\Image $image,
		LogWrapper $log, \OCP\IAvatarManager $avatarManager) {

		$this->access        = $access;
		$this->connection    = $access->getConnection();
		$this->config        = $config;
		$this->fs            = $fs;
		$this->dn            = $dn;
		$this->uid           = $username;
		$this->image         = $image;
		$this->log           = $log;
		$this->avatarManager = $avatarManager;
	}

	/**
	 * @brief updates properties like email, quota or avatar provided by LDAP
	 * @return null
	 */
	public function update() {
		if(is_null($this->dn)) {
			return null;
		}

		$hasLoggedIn = $this->config->getUserValue($this->uid, 'user_ldap',
				self::USER_PREFKEY_FIRSTLOGIN, 0);

		if($this->needsRefresh()) {
			$this->updateEmail();
			$this->updateQuota();
			if($hasLoggedIn !== 0) {
				//we do not need to try it, when the user has not been logged in
				//before, because the file system will not be ready.
				$this->updateAvatar();
				//in order to get an avatar as soon as possible, mark the user
				//as refreshed only when updating the avatar did happen
				$this->markRefreshTime();
			}
		}
	}

	/**
	 * @brief returns the LDAP DN of the user
	 * @return string
	 */
	public function getDN() {
		return $this->dn;
	}

	/**
	 * @brief returns the ownCloud internal username of the user
	 * @return string
	 */
	public function getUsername() {
		return $this->uid;
	}

	/**
	 * @brief reads the image from LDAP that shall be used as Avatar
	 * @return string data (provided by LDAP) | false
	 */
	public function getAvatarImage() {
		if(!is_null($this->avatarImage)) {
			return $this->avatarImage;
		}

		$this->avatarImage = false;
		$attributes = array('jpegPhoto', 'thumbnailPhoto');
		foreach($attributes as $attribute) {
			$result = $this->access->readAttribute($this->dn, $attribute);
			if($result !== false && is_array($result) && isset($result[0])) {
				$this->avatarImage = $result[0];
				break;
			}
		}

		return $this->avatarImage;
	}

	/**
	 * @brief marks the user as having logged in at least once
	 * @return null
	 */
	public function markLogin() {
		$this->config->setUserValue(
			$this->uid, 'user_ldap', self::USER_PREFKEY_FIRSTLOGIN, 1);
	}

	/**
	 * @brief marks the time when user features like email have been updated
	 * @return null
	 */
	private function markRefreshTime() {
		$this->config->setUserValue(
			$this->uid, 'user_ldap', self::USER_PREFKEY_LASTREFRESH, time());
	}

	/**
	 * @brief checks whether user features needs to be updated again by
	 * comparing the difference of time of the last refresh to now with the
	 * desired interval
	 * @return bool
	 */
	private function needsRefresh() {
		$lastChecked = $this->config->getUserValue($this->uid, 'user_ldap',
			self::USER_PREFKEY_LASTREFRESH, 0);

		//TODO make interval configurable
		if((time() - intval($lastChecked)) < 86400 ) {
			return false;
		}
		return  true;
	}

	/**
	 * Stores a key-value pair in relation to this user
	 * @param string $key
	 * @param string $value
	 */
	private function store($key, $value) {
		$this->config->setUserValue($this->uid, 'user_ldap', $key, $value);
	}

	/**
	 * Stores the display name in the databae
	 * @param string $displayName
	 */
	public function storeDisplayName($displayName) {
		$this->store('displayName', $displayName);
	}

	/**
	 * Stores the LDAP Username in the Database
	 * @param string $userName
	 */
	public function storeLDAPUserName($userName) {
		$this->store('uid', $userName);
	}

	/**
	 * @brief checks whether an update method specified by feature was run
	 * already. If not, it will marked like this, because it is expected that
	 * the method will be run, when false is returned.
	 * @param string email | quota | avatar (can be extended)
	 * @return bool
	 */
	private function wasRefreshed($feature) {
		if(isset($this->refreshedFeatures[$feature])) {
			return true;
		}
		$this->refreshedFeatures[$feature] = 1;
		return false;
	}

	/**
	 * @brief fetches the email from LDAP and stores it as ownCloud user value
	 * @return null
	 */
	public function updateEmail() {
		if($this->wasRefreshed('email')) {
			return;
		}

		$email = null;
		$emailAttribute = $this->connection->ldapEmailAttribute;
		if(!empty($emailAttribute)) {
			$aEmail = $this->access->readAttribute($this->dn, $emailAttribute);
			if($aEmail && (count($aEmail) > 0)) {
				$email = $aEmail[0];
			}
			if(!is_null($email)) {
				$this->config->setUserValue(
					$this->uid, 'settings', 'email', $email);
			}
		}
	}

	/**
	 * @brief fetches the quota from LDAP and stores it as ownCloud user value
	 * @return null
	 */
	public function updateQuota() {
		if($this->wasRefreshed('quota')) {
			return;
		}

		$quota = null;
		$quotaDefault = $this->connection->ldapQuotaDefault;
		$quotaAttribute = $this->connection->ldapQuotaAttribute;
		if(!empty($quotaDefault)) {
			$quota = $quotaDefault;
		}
		if(!empty($quotaAttribute)) {
			$aQuota = $this->access->readAttribute($this->dn, $quotaAttribute);

			if($aQuota && (count($aQuota) > 0)) {
				$quota = $aQuota[0];
			}
		}
		if(!is_null($quota)) {
			$this->config->setUserValue($this->uid, 'files', 'quota', $quota);
		}
	}

	/**
	 * @brief attempts to get an image from LDAP and sets it as ownCloud avatar
	 * @return null
	 */
	public function updateAvatar() {
		if($this->wasRefreshed('avatar')) {
			return;
		}
		$avatarImage = $this->getAvatarImage();
		if($avatarImage === false) {
			//not set, nothing left to do;
			return;
		}
		$this->image->loadFromBase64(base64_encode($avatarImage));
		$this->setOwnCloudAvatar();
	}

	/**
	 * @brief sets an image as ownCloud avatar
	 * @return null
	 */
	private function setOwnCloudAvatar() {
		if(!$this->image->valid()) {
			$this->log->log('user_ldap', 'jpegPhoto data invalid for '.$this->dn,
				\OCP\Util::ERROR);
			return;
		}
		//make sure it is a square and not bigger than 128x128
		$size = min(array($this->image->width(), $this->image->height(), 128));
		if(!$this->image->centerCrop($size)) {
			$this->log->log('user_ldap',
				'croping image for avatar failed for '.$this->dn,
				\OCP\Util::ERROR);
			return;
		}

		if(!$this->fs->isLoaded()) {
			$this->fs->setup($this->uid);
		}

		$avatar = $this->avatarManager->getAvatar($this->uid);
		$avatar->set($this->image);
	}

}
