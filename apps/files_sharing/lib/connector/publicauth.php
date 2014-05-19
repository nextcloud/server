<?php

/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Files_Sharing\Connector;

class PublicAuth extends \Sabre_DAV_Auth_Backend_AbstractBasic {

	/**
	 * @var \OCP\IConfig
	 */
	private $config;

	private $share;

	/**
	 * @param \OCP\IConfig $config
	 */
	public function __construct($config) {
		$this->config = $config;
	}

	/**
	 * Validates a username and password
	 *
	 * This method should return true or false depending on if login
	 * succeeded.
	 *
	 * @param string $username
	 * @param string $password
	 *
	 * @return bool
	 */
	protected function validateUserPass($username, $password) {
		$linkItem = \OCP\Share::getShareByToken($username, false);
		$this->share = $linkItem;
		if (!$linkItem) {
			return false;
		}

		// check if the share is password protected
		if (isset($linkItem['share_with'])) {
			if ($linkItem['share_type'] == \OCP\Share::SHARE_TYPE_LINK) {
				// Check Password
				$forcePortable = (CRYPT_BLOWFISH != 1);
				$hasher = new \PasswordHash(8, $forcePortable);
				if (!$hasher->CheckPassword($password . $this->config->getSystemValue('passwordsalt', ''), $linkItem['share_with'])) {
					return false;
				} else {
					return true;
				}
			} else {
				return false;
			}
		} else {
			return true;
		}
	}

	/**
	 * @return array
	 */
	public function getShare() {
		return $this->share;
	}
}
