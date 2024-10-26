<?php

/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\FederatedFileSharing\Tests;

use OC\Files\Filesystem;
use OC\Group\Database;

/**
 * Class Test_Files_Sharing_Base
 *
 * @group DB
 *
 * Base class for sharing tests.
 */
abstract class TestCase extends \Test\TestCase {
	public const TEST_FILES_SHARING_API_USER1 = 'test-share-user1';
	public const TEST_FILES_SHARING_API_USER2 = 'test-share-user2';

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		// reset backend
		\OC_User::clearBackends();
		\OC::$server->getGroupManager()->clearBackends();

		// create users
		$backend = new \Test\Util\User\Dummy();
		\OC_User::useBackend($backend);
		$backend->createUser(self::TEST_FILES_SHARING_API_USER1, self::TEST_FILES_SHARING_API_USER1);
		$backend->createUser(self::TEST_FILES_SHARING_API_USER2, self::TEST_FILES_SHARING_API_USER2);
	}

	protected function setUp(): void {
		parent::setUp();

		//login as user1
		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);
	}

	public static function tearDownAfterClass(): void {
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
		\OC::$server->getGroupManager()->clearBackends();
		\OC::$server->getGroupManager()->addBackend(new Database());

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
			$userManager = \OC::$server->getUserManager();
			$groupManager = \OC::$server->getGroupManager();

			$userObject = $userManager->createUser($user, $password);
			$group = $groupManager->createGroup('group');

			if ($group and $userObject) {
				$group->addUser($userObject);
			}
		}

		\OC_Util::tearDownFS();
		\OC::$server->getUserSession()->setUser(null);
		Filesystem::tearDown();
		\OC::$server->getUserSession()->login($user, $password);
		\OC::$server->getUserFolder($user);

		\OC_Util::setupFS($user);
	}
}
