<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Björn Schießle <bjoern@schiessle.org>
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

namespace OCA\FederatedFileSharing\Tests;

use OC\Files\Filesystem;
use OCA\Files\Share;

/**
 * Class Test_Files_Sharing_Base
 *
 * @group DB
 *
 * Base class for sharing tests.
 */
abstract class TestCase extends \Test\TestCase {

	const TEST_FILES_SHARING_API_USER1 = "test-share-user1";
	const TEST_FILES_SHARING_API_USER2 = "test-share-user2";

	public static function setUpBeforeClass() {
		parent::setUpBeforeClass();

		// reset backend
		\OC_User::clearBackends();
		\OC_Group::clearBackends();

		// create users
		$backend = new \Test\Util\User\Dummy();
		\OC_User::useBackend($backend);
		$backend->createUser(self::TEST_FILES_SHARING_API_USER1, self::TEST_FILES_SHARING_API_USER1);
		$backend->createUser(self::TEST_FILES_SHARING_API_USER2, self::TEST_FILES_SHARING_API_USER2);
	}

	protected function setUp() {
		parent::setUp();

		//login as user1
		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);
	}

	public static function tearDownAfterClass() {
		// cleanup users
		$user = \OC::$server->getUserManager()->get(self::TEST_FILES_SHARING_API_USER1);
		if ($user !== null) {
			$user->delete();
		}
		$user = \OC::$server->getUserManager()->get(self::TEST_FILES_SHARING_API_USER2);
		if ($user !== null) {
			$user->delete();
		}

		\OC_Util::tearDownFS();
		\OC_User::setUserId('');
		Filesystem::tearDown();

		// reset backend
		\OC_User::clearBackends();
		\OC_User::useBackend('database');
		\OC_Group::clearBackends();
		\OC_Group::useBackend(new \OC_Group_Database());

		parent::tearDownAfterClass();
	}

	/**
	 * @param string $user
	 * @param bool $create
	 * @param bool $password
	 */
	protected static function loginHelper($user, $create = false, $password = false) {

		if ($password === false) {
			$password = $user;
		}

		if ($create) {
			\OC::$server->getUserManager()->createUser($user, $password);
			\OC_Group::createGroup('group');
			\OC_Group::addToGroup($user, 'group');
		}

		self::resetStorage();

		\OC_Util::tearDownFS();
		\OC::$server->getUserSession()->setUser(null);
		\OC\Files\Filesystem::tearDown();
		\OC::$server->getUserSession()->login($user, $password);
		\OC::$server->getUserFolder($user);

		\OC_Util::setupFS($user);
	}

	/**
	 * reset init status for the share storage
	 */
	protected static function resetStorage() {
		$storage = new \ReflectionClass('\OC\Files\Storage\Shared');
		$isInitialized = $storage->getProperty('initialized');
		$isInitialized->setAccessible(true);
		$isInitialized->setValue($storage, false);
		$isInitialized->setAccessible(false);
	}

}
