<?php

/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Files_Sharing\Connector;

class PublicAuth extends \Sabre\DAV\Auth\Backend\AbstractBasic {

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
		\OC_User::setIncognitoMode(true);
		$this->share = $linkItem;
		if (!$linkItem) {
			return false;
		}

		// check if the share is password protected
		if (isset($linkItem['share_with'])) {
			if ($linkItem['share_type'] == \OCP\Share::SHARE_TYPE_LINK) {
				// Check Password
				$newHash = '';
				if(\OC::$server->getHasher()->verify($password, $linkItem['share_with'], $newHash)) {
					/**
					 * FIXME: Migrate old hashes to new hash format
					 * Due to the fact that there is no reasonable functionality to update the password
					 * of an existing share no migration is yet performed there.
					 * The only possibility is to update the existing share which will result in a new
					 * share ID and is a major hack.
					 *
					 * In the future the migration should be performed once there is a proper method
					 * to update the share's password. (for example `$share->updatePassword($password)`
					 *
					 * @link https://github.com/owncloud/core/issues/10671
					 */
					if(!empty($newHash)) {

					}
					return true;
				} else {
					return false;
				}
			} elseif ($linkItem['share_type'] == \OCP\Share::SHARE_TYPE_REMOTE) {
				return true;
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
