<?php
/**
 * ownCloud
 *
 * @author Bjoern Schiessle
 * @copyright 2014 Bjoern Schiessle <schiessle@owncloud.com>
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

require_once __DIR__ . '/base.php';

use OCA\Files\Share;

/**
 * Class Test_Files_Sharing
 */
class Test_Files_Sharing extends Test_Files_Sharing_Base {

	const TEST_FOLDER_NAME = '/folder_share_api_test';

	private static $tempStorage;

	function setUp() {
		parent::setUp();

		$this->folder = self::TEST_FOLDER_NAME;
		$this->subfolder  = '/subfolder_share_api_test';
		$this->subsubfolder = '/subsubfolder_share_api_test';

		$this->filename = '/share-api-test.txt';

		// save file with content
		$this->view->file_put_contents($this->filename, $this->data);
		$this->view->mkdir($this->folder);
		$this->view->mkdir($this->folder . $this->subfolder);
		$this->view->mkdir($this->folder . $this->subfolder . $this->subsubfolder);
		$this->view->file_put_contents($this->folder.$this->filename, $this->data);
		$this->view->file_put_contents($this->folder . $this->subfolder . $this->filename, $this->data);
	}

	function tearDown() {
		$this->view->unlink($this->filename);
		$this->view->deleteAll($this->folder);

		self::$tempStorage = null;

		parent::tearDown();
	}

	function testUnshareFromSelf() {

		\OC_Group::createGroup('testGroup');
		\OC_Group::addToGroup(self::TEST_FILES_SHARING_API_USER2, 'testGroup');
		\OC_Group::addToGroup(self::TEST_FILES_SHARING_API_USER3, 'testGroup');

		$fileinfo = $this->view->getFileInfo($this->filename);

		$pathinfo = pathinfo($this->filename);

		$duplicate = '/' . $pathinfo['filename'] . ' (2).' . $pathinfo['extension'];

		$result = \OCP\Share::shareItem('file', $fileinfo['fileid'], \OCP\Share::SHARE_TYPE_USER,
				\Test_Files_Sharing::TEST_FILES_SHARING_API_USER2, 31);

		$this->assertTrue($result);

		$result = \OCP\Share::shareItem('file', $fileinfo['fileid'], \OCP\Share::SHARE_TYPE_GROUP,
				'testGroup', 31);

		$this->assertTrue($result);

		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);
		$this->assertTrue(\OC\Files\Filesystem::file_exists($this->filename));
		$this->assertTrue(\OC\Files\Filesystem::file_exists($duplicate));

		self::loginHelper(self::TEST_FILES_SHARING_API_USER3);
		$this->assertTrue(\OC\Files\Filesystem::file_exists($this->filename));
		$this->assertFalse(\OC\Files\Filesystem::file_exists($duplicate));

		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);
		\OC\Files\Filesystem::unlink($this->filename);
		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);
		$this->assertFalse(\OC\Files\Filesystem::file_exists($this->filename));
		$this->assertTrue(\OC\Files\Filesystem::file_exists($duplicate));

		// for user3 nothing should change
		self::loginHelper(self::TEST_FILES_SHARING_API_USER3);
		$this->assertTrue(\OC\Files\Filesystem::file_exists($this->filename));
		$this->assertFalse(\OC\Files\Filesystem::file_exists($duplicate));

		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);
		\OC\Files\Filesystem::unlink($duplicate);
		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);
		$this->assertFalse(\OC\Files\Filesystem::file_exists($this->filename));
		$this->assertFalse(\OC\Files\Filesystem::file_exists($duplicate));

		// for user3 nothing should change
		self::loginHelper(self::TEST_FILES_SHARING_API_USER3);
		$this->assertTrue(\OC\Files\Filesystem::file_exists($this->filename));
		$this->assertFalse(\OC\Files\Filesystem::file_exists($duplicate));

		//cleanup
		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);
		\OCP\Share::unshare('file', $fileinfo['fileid'], \OCP\Share::SHARE_TYPE_GROUP,
				'testGroup');
		\OCP\Share::unshare('file', $fileinfo['fileid'], \OCP\Share::SHARE_TYPE_USER,
				self::TEST_FILES_SHARING_API_USER2);
		\OC_Group::removeFromGroup(self::TEST_FILES_SHARING_API_USER2, 'testGroup');
		\OC_Group::removeFromGroup(self::TEST_FILES_SHARING_API_USER2, 'testGroup');
		\OC_Group::deleteGroup('testGroup');


	}

	/**
	 * shared files should never have delete permissions
	 * @dataProvider  DataProviderTestFileSharePermissions
	 */
	function testFileSharePermissions($permission, $expectedPermissions) {

		$fileinfo = $this->view->getFileInfo($this->filename);

		$result = \OCP\Share::shareItem('file', $fileinfo['fileid'], \OCP\Share::SHARE_TYPE_USER,
				\Test_Files_Sharing::TEST_FILES_SHARING_API_USER2, $permission);

		$this->assertTrue($result);

		$result = \OCP\Share::getItemShared('file', null);

		$this->assertTrue(is_array($result));

		// test should return exactly one shares created from testCreateShare()
		$this->assertTrue(count($result) === 1);

		$share = reset($result);
		$this->assertSame($expectedPermissions, $share['permissions']);

		\OCP\Share::unshare('file', $fileinfo['fileid'], \OCP\Share::SHARE_TYPE_USER,
				\Test_Files_Sharing::TEST_FILES_SHARING_API_USER2);
	}

	function DataProviderTestFileSharePermissions() {
		$permission1 = \OCP\PERMISSION_ALL;
		$permission2 = \OCP\PERMISSION_DELETE;
		$permission3 = \OCP\PERMISSION_READ;
		$permission4 = \OCP\PERMISSION_READ | \OCP\PERMISSION_UPDATE;
		$permission5 = \OCP\PERMISSION_READ | \OCP\PERMISSION_DELETE;
		$permission6 = \OCP\PERMISSION_READ | \OCP\PERMISSION_UPDATE | \OCP\PERMISSION_DELETE;

		return array(
			array($permission1, \OCP\PERMISSION_ALL & ~\OCP\PERMISSION_DELETE),
			array($permission2, 0),
			array($permission3, $permission3),
			array($permission4, $permission4),
			array($permission5, $permission3),
			array($permission6, $permission4),
		);
	}

}
