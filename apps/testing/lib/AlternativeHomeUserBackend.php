<?php
/**
 * SPDX-FileCopyrightText: 2016 ownCloud GmbH.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Testing;

use OC\User\Database;

/**
 * Alternative home user backend.
 *
 * It returns a md5 of the home folder instead of the user id.
 * To configure, need to add this in config.php:
 *	'user_backends' => [
 *			'default' => false, [
 *				'class' => '\\OCA\\Testing\\AlternativeHomeUserBackend',
 *				'arguments' => [],
 *			],
 *	]
 */
class AlternativeHomeUserBackend extends Database {
	public function __construct() {
		parent::__construct();
	}
	/**
	 * get the user's home directory
	 * @param string $uid the username
	 * @return string|false
	 */
	public function getHome($uid) {
		if ($this->userExists($uid)) {
			// workaround to avoid killing the admin
			if ($uid !== 'admin') {
				$uid = md5($uid);
			}
			return \OC::$server->getConfig()->getSystemValue('datadirectory', \OC::$SERVERROOT . '/data') . '/' . $uid;
		}

		return false;
	}
}
