<?php
/**
 * Copyright (c) 2012 Sam Tuke <samtuke@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Files_Encryption\Tests;

use OCA\Encryption;

/**
 * Class Test_Encryption_TestCase
 */
abstract class TestCase extends \Test\TestCase {
	/**
	 * @param string $user
	 * @param bool $create
	 * @param bool $password
	 */
	public static function loginHelper($user, $create = false, $password = false, $loadEncryption = true) {
		if ($create) {
			try {
				\OC_User::createUser($user, $user);
			} catch (\Exception $e) {
				// catch username is already being used from previous aborted runs
			}
		}

		if ($password === false) {
			$password = $user;
		}

		\OC_Util::tearDownFS();
		\OC_User::setUserId('');
		\OC\Files\Filesystem::tearDown();
		\OC_User::setUserId($user);
		\OC_Util::setupFS($user);

		if ($loadEncryption) {
			$params['uid'] = $user;
			$params['password'] = $password;
			\OCA\Encryption\Hooks::login($params);
		}
	}

	public static function logoutHelper() {
		\OC_Util::tearDownFS();
		\OC_User::setUserId(false);
		\OC\Files\Filesystem::tearDown();
	}
}
