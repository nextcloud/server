<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
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

namespace OC\User;

use OC\Hooks\Emitter;
use OC_Helper;
use OCP\IAvatarManager;
use OCP\IImage;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IConfig;
use OCP\UserInterface;

class User implements IUser {
	/** @var string $uid */
	private $uid;

	/** @var string $displayName */
	private $displayName;

	/** @var UserInterface $backend */
	private $backend;

	/** @var bool $enabled */
	private $enabled;

	/** @var Emitter|Manager $emitter */
	private $emitter;

	/** @var string $home */
	private $home;

	/** @var int $lastLogin */
	private $lastLogin;

	/** @var \OCP\IConfig $config */
	private $config;

	/** @var IAvatarManager */
	private $avatarManager;

	/** @var IURLGenerator */
	private $urlGenerator;

	/**
	 * @param string $uid
	 * @param UserInterface $backend
	 * @param \OC\Hooks\Emitter $emitter
	 * @param IConfig|null $config
	 * @param IURLGenerator $urlGenerator
	 */
	public function __construct($uid, $backend, $emitter = null, IConfig $config = null, $urlGenerator = null) {
		$this->uid = $uid;
		$this->backend = $backend;
		$this->emitter = $emitter;
		if(is_null($config)) {
			$config = \OC::$server->getConfig();
		}
		$this->config = $config;
		$this->urlGenerator = $urlGenerator;
		$enabled = $this->config->getUserValue($uid, 'core', 'enabled', 'true');
		$this->enabled = ($enabled === 'true');
		$this->lastLogin = $this->config->getUserValue($uid, 'login', 'lastLogin', 0);
		if (is_null($this->urlGenerator)) {
			$this->urlGenerator = \OC::$server->getURLGenerator();
		}
	}

	/**
	 * get the user id
	 *
	 * @return string
	 */
	public function getUID() {
		return $this->uid;
	}

	/**
	 * get the display name for the user, if no specific display name is set it will fallback to the user id
	 *
	 * @return string
	 */
	public function getDisplayName() {
		if (!isset($this->displayName)) {
			$displayName = '';
			if ($this->backend and $this->backend->implementsActions(\OC\User\Backend::GET_DISPLAYNAME)) {
				// get display name and strip whitespace from the beginning and end of it
				$backendDisplayName = $this->backend->getDisplayName($this->uid);
				if (is_string($backendDisplayName)) {
					$displayName = trim($backendDisplayName);
				}
			}

			if (!empty($displayName)) {
				$this->displayName = $displayName;
			} else {
				$this->displayName = $this->uid;
			}
		}
		return $this->displayName;
	}

	/**
	 * set the displayname for the user
	 *
	 * @param string $displayName
	 * @return bool
	 */
	public function setDisplayName($displayName) {
		$displayName = trim($displayName);
		if ($this->backend->implementsActions(\OC\User\Backend::SET_DISPLAYNAME) && !empty($displayName)) {
			$result = $this->backend->setDisplayName($this->uid, $displayName);
			if ($result) {
				$this->displayName = $displayName;
				$this->triggerChange('displayName', $displayName);
			}
			return $result !== false;
		} else {
			return false;
		}
	}

	/**
	 * set the email address of the user
	 *
	 * @param string|null $mailAddress
	 * @return void
	 * @since 9.0.0
	 */
	public function setEMailAddress($mailAddress) {
		if($mailAddress === '') {
			$this->config->deleteUserValue($this->uid, 'settings', 'email');
		} else {
			$this->config->setUserValue($this->uid, 'settings', 'email', $mailAddress);
		}
		$this->triggerChange('eMailAddress', $mailAddress);
	}

	/**
	 * returns the timestamp of the user's last login or 0 if the user did never
	 * login
	 *
	 * @return int
	 */
	public function getLastLogin() {
		return $this->lastLogin;
	}

	/**
	 * updates the timestamp of the most recent login of this user
	 */
	public function updateLastLoginTimestamp() {
		$this->lastLogin = time();
		\OC::$server->getConfig()->setUserValue(
			$this->uid, 'login', 'lastLogin', $this->lastLogin);
	}

	/**
	 * Delete the user
	 *
	 * @return bool
	 */
	public function delete() {
		if ($this->emitter) {
			$this->emitter->emit('\OC\User', 'preDelete', array($this));
		}
		$result = $this->backend->deleteUser($this->uid);
		if ($result) {

			// FIXME: Feels like an hack - suggestions?

			// We have to delete the user from all groups
			foreach (\OC_Group::getUserGroups($this->uid) as $i) {
				\OC_Group::removeFromGroup($this->uid, $i);
			}
			// Delete the user's keys in preferences
			\OC::$server->getConfig()->deleteAllUserValues($this->uid);

			// Delete user files in /data/
			\OC_Helper::rmdirr(\OC_User::getHome($this->uid));

			// Delete the users entry in the storage table
			\OC\Files\Cache\Storage::remove('home::' . $this->uid);

			\OC::$server->getCommentsManager()->deleteReferencesOfActor('users', $this->uid);
			\OC::$server->getCommentsManager()->deleteReadMarksFromUser($this);

			$notification = \OC::$server->getNotificationManager()->createNotification();
			$notification->setUser($this->uid);
			\OC::$server->getNotificationManager()->markProcessed($notification);

			if ($this->emitter) {
				$this->emitter->emit('\OC\User', 'postDelete', array($this));
			}
		}
		return !($result === false);
	}

	/**
	 * Set the password of the user
	 *
	 * @param string $password
	 * @param string $recoveryPassword for the encryption app to reset encryption keys
	 * @return bool
	 */
	public function setPassword($password, $recoveryPassword = null) {
		if ($this->emitter) {
			$this->emitter->emit('\OC\User', 'preSetPassword', array($this, $password, $recoveryPassword));
		}
		if ($this->backend->implementsActions(\OC\User\Backend::SET_PASSWORD)) {
			$result = $this->backend->setPassword($this->uid, $password);
			if ($this->emitter) {
				$this->emitter->emit('\OC\User', 'postSetPassword', array($this, $password, $recoveryPassword));
			}
			return !($result === false);
		} else {
			return false;
		}
	}

	/**
	 * get the users home folder to mount
	 *
	 * @return string
	 */
	public function getHome() {
		if (!$this->home) {
			if ($this->backend->implementsActions(\OC\User\Backend::GET_HOME) and $home = $this->backend->getHome($this->uid)) {
				$this->home = $home;
			} elseif ($this->config) {
				$this->home = $this->config->getSystemValue('datadirectory') . '/' . $this->uid;
			} else {
				$this->home = \OC::$SERVERROOT . '/data/' . $this->uid;
			}
		}
		return $this->home;
	}

	/**
	 * Get the name of the backend class the user is connected with
	 *
	 * @return string
	 */
	public function getBackendClassName() {
		if($this->backend instanceof \OCP\IUserBackend) {
			return $this->backend->getBackendName();
		}
		return get_class($this->backend);
	}

	/**
	 * check if the backend allows the user to change his avatar on Personal page
	 *
	 * @return bool
	 */
	public function canChangeAvatar() {
		if ($this->backend->implementsActions(\OC\User\Backend::PROVIDE_AVATAR)) {
			return $this->backend->canChangeAvatar($this->uid);
		}
		return true;
	}

	/**
	 * check if the backend supports changing passwords
	 *
	 * @return bool
	 */
	public function canChangePassword() {
		return $this->backend->implementsActions(\OC\User\Backend::SET_PASSWORD);
	}

	/**
	 * check if the backend supports changing display names
	 *
	 * @return bool
	 */
	public function canChangeDisplayName() {
		if ($this->config->getSystemValue('allow_user_to_change_display_name') === false) {
			return false;
		}
		return $this->backend->implementsActions(\OC\User\Backend::SET_DISPLAYNAME);
	}

	/**
	 * check if the user is enabled
	 *
	 * @return bool
	 */
	public function isEnabled() {
		return $this->enabled;
	}

	/**
	 * set the enabled status for the user
	 *
	 * @param bool $enabled
	 */
	public function setEnabled($enabled) {
		$this->enabled = $enabled;
		$enabled = ($enabled) ? 'true' : 'false';
		$this->config->setUserValue($this->uid, 'core', 'enabled', $enabled);
	}

	/**
	 * get the users email address
	 *
	 * @return string|null
	 * @since 9.0.0
	 */
	public function getEMailAddress() {
		return $this->config->getUserValue($this->uid, 'settings', 'email', null);
	}

	/**
	 * get the users' quota
	 *
	 * @return string
	 * @since 9.0.0
	 */
	public function getQuota() {
		$quota = $this->config->getUserValue($this->uid, 'files', 'quota', 'default');
		if($quota === 'default') {
			$quota = $this->config->getAppValue('files', 'default_quota', 'none');
		}
		return $quota;
	}

	/**
	 * set the users' quota
	 *
	 * @param string $quota
	 * @return void
	 * @since 9.0.0
	 */
	public function setQuota($quota) {
		if($quota !== 'none' and $quota !== 'default') {
			$quota = OC_Helper::computerFileSize($quota);
			$quota = OC_Helper::humanFileSize($quota);
		}
		$this->config->setUserValue($this->uid, 'files', 'quota', $quota);
		$this->triggerChange('quota', $quota);
	}

	/**
	 * get the avatar image if it exists
	 *
	 * @param int $size
	 * @return IImage|null
	 * @since 9.0.0
	 */
	public function getAvatarImage($size) {
		// delay the initialization
		if (is_null($this->avatarManager)) {
			$this->avatarManager = \OC::$server->getAvatarManager();
		}

		$avatar = $this->avatarManager->getAvatar($this->uid);
		$image = $avatar->get(-1);
		if ($image) {
			return $image;
		}

		return null;
	}

	/**
	 * get the federation cloud id
	 *
	 * @return string
	 * @since 9.0.0
	 */
	public function getCloudId() {
		$uid = $this->getUID();
		$server = $this->urlGenerator->getAbsoluteURL('/');
		return $uid . '@' . rtrim( $this->removeProtocolFromUrl($server), '/');
	}

	/**
	 * @param string $url
	 * @return string
	 */
	private function removeProtocolFromUrl($url) {
		if (strpos($url, 'https://') === 0) {
			return substr($url, strlen('https://'));
		} else if (strpos($url, 'http://') === 0) {
			return substr($url, strlen('http://'));
		}

		return $url;
	}

	public function triggerChange($feature, $value = null) {
		if ($this->emitter) {
			$this->emitter->emit('\OC\User', 'changeUser', array($this, $feature, $value));
		}
	}
}
