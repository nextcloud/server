<?php
/**
 * @author Björn Schießle <schiessle@owncloud.com>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Robin McCorkell <rmccorkell@karoshi.org.uk>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <pvince81@owncloud.com>
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

namespace OCA\Files_Sharing\Tests;

use OC\Files\Filesystem;
use OCA\Files\Share;
use OCA\Files_Sharing\Appinfo\Application;

/**
 * Class Test_Files_Sharing_Base
 *
 * Base class for sharing tests.
 */
abstract class TestCase extends \Test\TestCase {

	const TEST_FILES_SHARING_API_USER1 = "test-share-user1";
	const TEST_FILES_SHARING_API_USER2 = "test-share-user2";
	const TEST_FILES_SHARING_API_USER3 = "test-share-user3";
	const TEST_FILES_SHARING_API_USER4 = "test-share-user4";

	const TEST_FILES_SHARING_API_GROUP1 = "test-share-group1";

	public $filename;
	public $data;
	/**
	 * @var \OC\Files\View
	 */
	public $view;
	public $folder;
	public $subfolder;

	public static function setUpBeforeClass() {
		parent::setUpBeforeClass();

		$application = new Application();
		$application->registerMountProviders();
		$application->setupPropagation();
		
		// reset backend
		\OC_User::clearBackends();
		\OC_Group::clearBackends();

		// clear share hooks
		\OC_Hook::clear('OCP\\Share');
		\OC::registerShareHooks();

		// create users
		$backend = new \OC_User_Dummy();
		\OC_User::useBackend($backend);
		$backend->createUser(self::TEST_FILES_SHARING_API_USER1, self::TEST_FILES_SHARING_API_USER1);
		$backend->createUser(self::TEST_FILES_SHARING_API_USER2, self::TEST_FILES_SHARING_API_USER2);
		$backend->createUser(self::TEST_FILES_SHARING_API_USER3, self::TEST_FILES_SHARING_API_USER3);
		$backend->createUser(self::TEST_FILES_SHARING_API_USER4, self::TEST_FILES_SHARING_API_USER4);

		// create group
		$groupBackend = new \OC_Group_Dummy();
		$groupBackend->createGroup(self::TEST_FILES_SHARING_API_GROUP1);
		$groupBackend->createGroup('group');
		$groupBackend->addToGroup(self::TEST_FILES_SHARING_API_USER1, 'group');
		$groupBackend->addToGroup(self::TEST_FILES_SHARING_API_USER2, 'group');
		$groupBackend->addToGroup(self::TEST_FILES_SHARING_API_USER3, 'group');
		$groupBackend->addToGroup(self::TEST_FILES_SHARING_API_USER2, self::TEST_FILES_SHARING_API_GROUP1);
		\OC_Group::useBackend($groupBackend);

	}

	protected function setUp() {
		parent::setUp();

		//login as user1
		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);

		$this->data = 'foobar';
		$this->view = new \OC\Files\View('/' . self::TEST_FILES_SHARING_API_USER1 . '/files');
	}

	protected function tearDown() {
		$query = \OCP\DB::prepare('DELETE FROM `*PREFIX*share`');
		$query->execute();

		parent::tearDown();
	}

	public static function tearDownAfterClass() {
		// cleanup users
		\OC_User::deleteUser(self::TEST_FILES_SHARING_API_USER1);
		\OC_User::deleteUser(self::TEST_FILES_SHARING_API_USER2);
		\OC_User::deleteUser(self::TEST_FILES_SHARING_API_USER3);

		// delete group
		\OC_Group::deleteGroup(self::TEST_FILES_SHARING_API_GROUP1);

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
			\OC_User::createUser($user, $password);
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
		$isInitialized = $storage->getProperty('isInitialized');
		$isInitialized->setAccessible(true);
		$isInitialized->setValue(array());
		$isInitialized->setAccessible(false);
	}

	/**
	 * get some information from a given share
	 * @param int $shareID
	 * @return array with: item_source, share_type, share_with, item_type, permissions
	 */
	protected function getShareFromId($shareID) {
		$sql = 'SELECT `item_source`, `share_type`, `share_with`, `item_type`, `permissions` FROM `*PREFIX*share` WHERE `id` = ?';
		$args = array($shareID);
		$query = \OCP\DB::prepare($sql);
		$result = $query->execute($args);

		$share = Null;

		if ($result) {
			$share = $result->fetchRow();
		}

		return $share;

	}

}
