<?php

/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\User;

use OC\Hooks\Emitter;

class User {
	/**
	 * @var string $uid
	 */
	private $uid;

	/**
	 * @var string $displayName
	 */
	private $displayName;

	/**
	 * @var \OC_User_Backend $backend
	 */
	private $backend;

	/**
	 * @var bool $enabled
	 */
	private $enabled;

	/**
	 * @var Emitter | Manager $emitter
	 */
	private $emitter;

	/**
	 * @param string $uid
	 * @param \OC_User_Backend $backend
	 * @param Emitter $emitter
	 */
	public function __construct($uid, $backend, $emitter = null) {
		$this->uid = $uid;
		if ($backend and $backend->implementsActions(OC_USER_BACKEND_GET_DISPLAYNAME)) {
			$this->displayName = $backend->getDisplayName($uid);
		} else {
			$this->displayName = $uid;
		}
		$this->backend = $backend;
		$this->emitter = $emitter;
		$enabled = \OC_Preferences::getValue($uid, 'core', 'enabled', 'true'); //TODO: DI for OC_Preferences
		$this->enabled = ($enabled === 'true');
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
		return $this->displayName;
	}

	/**
	 * set the displayname for the user
	 *
	 * @param string $displayName
	 * @return bool
	 */
	public function setDisplayName($displayName) {
		if ($this->canChangeDisplayName()) {
			$this->displayName = $displayName;
			$result = $this->backend->setDisplayName($this->uid, $displayName);
			return $result !== false;
		} else {
			return false;
		}
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
	public function setPassword($password, $recoveryPassword) {
		if ($this->emitter) {
			$this->emitter->emit('\OC\User', 'preSetPassword', array($this, $password, $recoveryPassword));
		}
		if ($this->backend->implementsActions(\OC_USER_BACKEND_SET_PASSWORD)) {
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
		if ($this->backend->implementsActions(\OC_USER_BACKEND_GET_HOME) and $home = $this->backend->getHome($this->uid)) {
			return $home;
		}
		return \OC_Config::getValue("datadirectory", \OC::$SERVERROOT . "/data") . '/' . $this->uid; //TODO switch to Config object once implemented
	}

	/**
	 * check if the backend supports changing passwords
	 *
	 * @return bool
	 */
	public function canChangePassword() {
		return $this->backend->implementsActions(\OC_USER_BACKEND_SET_PASSWORD);
	}

	/**
	 * check if the backend supports changing display names
	 *
	 * @return bool
	 */
	public function canChangeDisplayName() {
		return $this->backend->implementsActions(\OC_USER_BACKEND_SET_DISPLAYNAME);
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
		\OC_Preferences::setValue($this->uid, 'core', 'enabled', $enabled);
	}
}
