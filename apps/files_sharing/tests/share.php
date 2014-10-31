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

use OCA\Files\Share;

/**
 * Class Test_Files_Sharing
 */
class Test_Files_Sharing extends OCA\Files_sharing\Tests\TestCase {

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

		// clear database table
		$query = \OCP\DB::prepare('DELETE FROM `*PREFIX*share`');
		$query->execute();

		parent::tearDown();
	}

	function testUnshareFromSelf() {

		\OC_Group::createGroup('testGroup');
		\OC_Group::addToGroup(self::TEST_FILES_SHARING_API_USER2, 'testGroup');
		\OC_Group::addToGroup(self::TEST_FILES_SHARING_API_USER3, 'testGroup');

		$fileinfo = $this->view->getFileInfo($this->filename);

		$result = \OCP\Share::shareItem('file', $fileinfo['fileid'], \OCP\Share::SHARE_TYPE_USER,
				\Test_Files_Sharing::TEST_FILES_SHARING_API_USER2, 31);

		$this->assertTrue($result);

		$result = \OCP\Share::shareItem('file', $fileinfo['fileid'], \OCP\Share::SHARE_TYPE_GROUP,
				'testGroup', 31);

		$this->assertTrue($result);

		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);
		$this->assertTrue(\OC\Files\Filesystem::file_exists($this->filename));

		self::loginHelper(self::TEST_FILES_SHARING_API_USER3);
		$this->assertTrue(\OC\Files\Filesystem::file_exists($this->filename));

		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);
		\OC\Files\Filesystem::unlink($this->filename);
		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);
		// both group share and user share should be gone
		$this->assertFalse(\OC\Files\Filesystem::file_exists($this->filename));

		// for user3 nothing should change
		self::loginHelper(self::TEST_FILES_SHARING_API_USER3);
		$this->assertTrue(\OC\Files\Filesystem::file_exists($this->filename));
	}

	/**
	 * if a file was shared as group share and as individual share they should be grouped
	 */
	function testGroupingOfShares() {

		$fileinfo = $this->view->getFileInfo($this->filename);

		$result = \OCP\Share::shareItem('file', $fileinfo['fileid'], \OCP\Share::SHARE_TYPE_GROUP,
				\Test_Files_Sharing::TEST_FILES_SHARING_API_GROUP1, \OCP\PERMISSION_READ);

		$this->assertTrue($result);

		$result = \OCP\Share::shareItem('file', $fileinfo['fileid'], \OCP\Share::SHARE_TYPE_USER,
				\Test_Files_Sharing::TEST_FILES_SHARING_API_USER2, \OCP\PERMISSION_UPDATE);

		$this->assertTrue($result);

		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);

		$result = \OCP\Share::getItemSharedWith('file', null);

		$this->assertTrue(is_array($result));

		// test should return exactly one shares created from testCreateShare()
		$this->assertSame(1, count($result));

		$share = reset($result);
		$this->assertSame(\OCP\PERMISSION_READ | \OCP\PERMISSION_UPDATE, $share['permissions']);

		\OC\Files\Filesystem::rename($this->filename, $this->filename . '-renamed');

		$result = \OCP\Share::getItemSharedWith('file', null);

		$this->assertTrue(is_array($result));

		// test should return exactly one shares created from testCreateShare()
		$this->assertSame(1, count($result));

		$share = reset($result);
		$this->assertSame(\OCP\PERMISSION_READ | \OCP\PERMISSION_UPDATE, $share['permissions']);
		$this->assertSame($this->filename . '-renamed', $share['file_target']);

		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);

		// unshare user share
		$result = \OCP\Share::unshare('file', $fileinfo['fileid'], \OCP\Share::SHARE_TYPE_USER,
				\Test_Files_Sharing::TEST_FILES_SHARING_API_USER2);
		$this->assertTrue($result);

		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);

		$result = \OCP\Share::getItemSharedWith('file', null);

		$this->assertTrue(is_array($result));

		// test should return the remaining group share
		$this->assertSame(1, count($result));

		$share = reset($result);
		// only the group share permissions should be available now
		$this->assertSame(\OCP\PERMISSION_READ, $share['permissions']);
		$this->assertSame($this->filename . '-renamed', $share['file_target']);

	}

	/**
	 * user1 share file to a group and to a user2 in the same group. Then user2
	 * unshares the file from self. Afterwards user1 should no longer see the
	 * single user share to user2. If he re-shares the file to user2 the same target
	 * then the group share should be used to group the item
	 */
	function testShareAndUnshareFromSelf() {
		$fileinfo = $this->view->getFileInfo($this->filename);

		// share the file to group1 (user2 is a member of this group) and explicitely to user2
		\OCP\Share::shareItem('file', $fileinfo['fileid'], \OCP\Share::SHARE_TYPE_GROUP, self::TEST_FILES_SHARING_API_GROUP1, \OCP\PERMISSION_ALL);
		\OCP\Share::shareItem('file', $fileinfo['fileid'], \OCP\Share::SHARE_TYPE_USER, self::TEST_FILES_SHARING_API_USER2, \OCP\PERMISSION_ALL);

		// user1 should have to shared files
		$shares = \OCP\Share::getItemsShared('file');
		$this->assertSame(2, count($shares));

		// user2 should have two files "welcome.txt" and the shared file,
		// both the group share and the single share of the same file should be
		// grouped to one file
		\Test_Files_Sharing::loginHelper(self::TEST_FILES_SHARING_API_USER2);
		$dirContent = \OC\Files\Filesystem::getDirectoryContent('/');
		$this->assertSame(2, count($dirContent));
		$this->verifyDirContent($dirContent, array('welcome.txt', ltrim($this->filename, '/')));

		// now user2 deletes the share (= unshare from self)
		\OC\Files\Filesystem::unlink($this->filename);

		// only welcome.txt should exists
		$dirContent = \OC\Files\Filesystem::getDirectoryContent('/');
		$this->assertSame(1, count($dirContent));
		$this->verifyDirContent($dirContent, array('welcome.txt'));

		// login as user1...
		\Test_Files_Sharing::loginHelper(self::TEST_FILES_SHARING_API_USER1);

		// ... now user1 should have only one shared file, the group share
		$shares = \OCP\Share::getItemsShared('file');
		$this->assertSame(1, count($shares));

		// user1 shares a gain the file directly to user2
		\OCP\Share::shareItem('file', $fileinfo['fileid'], \OCP\Share::SHARE_TYPE_USER, self::TEST_FILES_SHARING_API_USER2, \OCP\PERMISSION_ALL);

		// user2 should see again welcome.txt and the shared file
		\Test_Files_Sharing::loginHelper(self::TEST_FILES_SHARING_API_USER2);
		$dirContent = \OC\Files\Filesystem::getDirectoryContent('/');
		$this->assertSame(2, count($dirContent));
		$this->verifyDirContent($dirContent, array('welcome.txt', ltrim($this->filename, '/')));


	}

	function verifyDirContent($content, $expected) {
		foreach ($content as $c) {
			if (!in_array($c['name'], $expected)) {
				$this->assertTrue(false, "folder should only contain '" . implode(',', $expected) . "', found: " .$c['name']);
			}
		}
	}

	function testShareWithDifferentShareFolder() {

		$fileinfo = $this->view->getFileInfo($this->filename);
		$folderinfo = $this->view->getFileInfo($this->folder);

		$fileShare = \OCP\Share::shareItem('file', $fileinfo['fileid'], \OCP\Share::SHARE_TYPE_USER,
				self::TEST_FILES_SHARING_API_USER2, 31);
		$this->assertTrue($fileShare);

		\OCA\Files_Sharing\Helper::setShareFolder('/Shared/subfolder');

		$folderShare = \OCP\Share::shareItem('folder', $folderinfo['fileid'], \OCP\Share::SHARE_TYPE_USER,
				self::TEST_FILES_SHARING_API_USER2, 31);
		$this->assertTrue($folderShare);

		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);

		$this->assertTrue(\OC\Files\Filesystem::file_exists($this->filename));
		$this->assertTrue(\OC\Files\Filesystem::file_exists('/Shared/subfolder/' . $this->folder));

		//cleanup
		\OCP\Config::deleteSystemValue('share_folder');
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
		$this->assertSame(1, count($result), 'more then one share found');

		$share = reset($result);
		$this->assertSame($expectedPermissions, $share['permissions']);
	}

	function DataProviderTestFileSharePermissions() {
		$permission1 = \OCP\PERMISSION_ALL;
		$permission3 = \OCP\PERMISSION_READ;
		$permission4 = \OCP\PERMISSION_READ | \OCP\PERMISSION_UPDATE;
		$permission5 = \OCP\PERMISSION_READ | \OCP\PERMISSION_DELETE;
		$permission6 = \OCP\PERMISSION_READ | \OCP\PERMISSION_UPDATE | \OCP\PERMISSION_DELETE;

		return array(
			array($permission1, \OCP\PERMISSION_ALL & ~\OCP\PERMISSION_DELETE),
			array($permission3, $permission3),
			array($permission4, $permission4),
			array($permission5, $permission3),
			array($permission6, $permission4),
		);
	}

}
