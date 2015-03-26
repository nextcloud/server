<?php
/**
 * @author Arthur Schiwon <blizzz@owncloud.com>
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Björn Schießle <schiessle@owncloud.com>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Robin McCorkell <rmccorkell@karoshi.org.uk>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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

namespace OC\User;

use OC\Hooks\Emitter;
use OCP\IUser;
use OCP\IConfig;

class User implements IUser {
	/**
	 * @var string $uid
	 */
	private $uid;

	/**
	 * @var string $displayName
	 */
	private $displayName;

	/**
	 * @var \OC_User_Interface $backend
	 */
	private $backend;

	/**
	 * @var bool $enabled
	 */
	private $enabled;

	/**
	 * @var Emitter|Manager $emitter
	 */
	private $emitter;

	/**
	 * @var string $home
	 */
	private $home;

	/**
	 * @var int $lastLogin
	 */
	private $lastLogin;

	/**
	 * @var \OCP\IConfig $config
	 */
	private $config;

	/**
	 * @param string $uid
	 * @param \OC_User_Interface $backend
	 * @param \OC\Hooks\Emitter $emitter
	 * @param \OCP\IConfig $config
	 */
	public function __construct($uid, $backend, $emitter = null, IConfig $config = null) {
		$this->uid = $uid;
		$this->backend = $backend;
		$this->emitter = $emitter;
		$this->config = $config;
		if ($this->config) {
			$enabled = $this->config->getUserValue($uid, 'core', 'enabled', 'true');
			$this->enabled = ($enabled === 'true');
			$this->lastLogin = $this->config->getUserValue($uid, 'login', 'lastLogin', 0);
		} else {
			$this->enabled = true;
			$this->lastLogin = \OC::$server->getConfig()->getUserValue($uid, 'login', 'lastLogin', 0);
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
	 * get the displayname for the user, if no specific displayname is set it will fallback to the user id
	 *
	 * @return string
	 */
	public function getDisplayName() {
		if (!isset($this->displayName)) {
			$displayName = '';
			if ($this->backend and $this->backend->implementsActions(\OC_User_Backend::GET_DISPLAYNAME)) {
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
		if ($this->backend->implementsActions(\OC_User_Backend::SET_DISPLAYNAME) && !empty($displayName)) {
			$this->displayName = $displayName;
			$result = $this->backend->setDisplayName($this->uid, $displayName);
			return $result !== false;
		} else {
			return false;
		}
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
		}

		if ($this->emitter) {
			$this->emitter->emit('\OC\User', 'postDelete', array($this));
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
		if ($this->backend->implementsActions(\OC_User_Backend::SET_PASSWORD)) {
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
			if ($this->backend->implementsActions(\OC_User_Backend::GET_HOME) and $home = $this->backend->getHome($this->uid)) {
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
		if ($this->backend->implementsActions(\OC_User_Backend::PROVIDE_AVATAR)) {
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
		return $this->backend->implementsActions(\OC_User_Backend::SET_PASSWORD);
	}

	/**
	 * check if the backend supports changing display names
	 *
	 * @return bool
	 */
	public function canChangeDisplayName() {
		if ($this->config and $this->config->getSystemValue('allow_user_to_change_display_name') === false) {
			return false;
		} else {
			return $this->backend->implementsActions(\OC_User_Backend::SET_DISPLAYNAME);
		}
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
		if ($this->config) {
			$enabled = ($enabled) ? 'true' : 'false';
			$this->config->setUserValue($this->uid, 'core', 'enabled', $enabled);
		}
	}
}
