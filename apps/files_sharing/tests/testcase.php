<?php
/**
 * ownCloud
 *
 * @author Bjoern Schiessle
 * @copyright 2013 Bjoern Schiessle <schiessle@owncloud.com>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Files_Sharing\Tests;

use OC\Files\Filesystem;
use OCA\Files\Share;

/**
 * Class Test_Files_Sharing_Base
 *
 * Base class for sharing tests.
 */
abstract class TestCase extends \Test\TestCase {

	const TEST_FILES_SHARING_API_USER1 = "test-share-user1";
	const TEST_FILES_SHARING_API_USER2 = "test-share-user2";
	const TEST_FILES_SHARING_API_USER3 = "test-share-user3";

	const TEST_FILES_SHARING_API_GROUP1 = "test-share-group1";

	public static $stateFilesEncryption;
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

		// remember files_encryption state
		self::$stateFilesEncryption = \OC_App::isEnabled('files_encryption');

		//we don't want to tests with app files_encryption enabled
		\OC_App::disable('files_encryption');

		// reset backend
		\OC_User::clearBackends();
		\OC_User::useBackend('database');

		// clear share hooks
		\OC_Hook::clear('OCP\\Share');
		\OC::registerShareHooks();
		\OCP\Util::connectHook('OC_Filesystem', 'setup', '\OC\Files\Storage\Shared', 'setup');

		// create users
		$backend = new \OC_User_Dummy();
		\OC_User::useBackend($backend);
		$backend->createUser(self::TEST_FILES_SHARING_API_USER1, self::TEST_FILES_SHARING_API_USER1);
		$backend->createUser(self::TEST_FILES_SHARING_API_USER2, self::TEST_FILES_SHARING_API_USER2);
		$backend->createUser(self::TEST_FILES_SHARING_API_USER3, self::TEST_FILES_SHARING_API_USER3);

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

		$this->assertFalse(\OC_App::isEnabled('files_encryption'));

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

		// reset app files_encryption
		if (self::$stateFilesEncryption) {
			\OC_App::enable('files_encryption');
		} else {
			\OC_App::disable('files_encryption');
		}

		\OC_Util::tearDownFS();
		\OC_User::setUserId('');
		Filesystem::tearDown();

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

		\OC_Util::tearDownFS();
		\OC::$server->getUserSession()->setUser(null);
		\OC\Files\Filesystem::tearDown();
		\OC::$server->getUserSession()->login($user, $password);
		\OC_Util::setupFS($user);
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
