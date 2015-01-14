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

/**
 * Class Test_Files_Sharing_Api
 */
class Test_Files_Sharing_Mount extends OCA\Files_sharing\Tests\TestCase {

	protected function setUp() {
		parent::setUp();

		$this->folder = '/folder_share_storage_test';

		$this->filename = '/share-api-storage.txt';


		$this->view->mkdir($this->folder);

		// save file with content
		$this->view->file_put_contents($this->filename, "root file");
		$this->view->file_put_contents($this->folder . $this->filename, "file in subfolder");
	}

	protected function tearDown() {
		$this->view->unlink($this->folder);
		$this->view->unlink($this->filename);

		parent::tearDown();
	}

	/**
	 * test if the mount point moves up if the parent folder no longer exists
	 */
	function testShareMountLoseParentFolder() {

		// share to user
		$fileinfo = $this->view->getFileInfo($this->folder);
		$result = \OCP\Share::shareItem('folder', $fileinfo['fileid'], \OCP\Share::SHARE_TYPE_USER,
			self::TEST_FILES_SHARING_API_USER2, 31);

		$statement = "UPDATE `*PREFIX*share` SET `file_target` = ? where `share_with` = ?";
		$query = \OC_DB::prepare($statement);
		$arguments = array('/foo/bar' . $this->folder, self::TEST_FILES_SHARING_API_USER2);
		$query->execute($arguments);

		$query = \OC_DB::prepare('SELECT * FROM `*PREFIX*share`');
		$result = $query->execute();

		$shares = $result->fetchAll();

		$this->assertSame(1, count($shares));

		$share = reset($shares);
		$this->assertSame('/foo/bar' . $this->folder, $share['file_target']);

		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);

		// share should have moved up

		$query = \OC_DB::prepare('SELECT * FROM `*PREFIX*share`');
		$result = $query->execute();

		$shares = $result->fetchAll();

		$this->assertSame(1, count($shares));

		$share = reset($shares);
		$this->assertSame($this->folder, $share['file_target']);

		//cleanup
		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);
		\OCP\Share::unshare('folder', $fileinfo['fileid'], \OCP\Share::SHARE_TYPE_USER, self::TEST_FILES_SHARING_API_USER2);
		$this->view->unlink($this->folder);
	}

	/**
	 * @medium
	 */
	function testDeleteParentOfMountPoint() {

		// share to user
		$fileinfo = $this->view->getFileInfo($this->folder);
		$result = \OCP\Share::shareItem('folder', $fileinfo['fileid'], \OCP\Share::SHARE_TYPE_USER,
			self::TEST_FILES_SHARING_API_USER2, 31);

		$this->assertTrue($result);

		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);
		$user2View = new \OC\Files\View('/' . self::TEST_FILES_SHARING_API_USER2 . '/files');
		$this->assertTrue($user2View->file_exists($this->folder));

		// create a local folder
		$result = $user2View->mkdir('localfolder');
		$this->assertTrue($result);

		// move mount point to local folder
		$result = $user2View->rename($this->folder, '/localfolder/' . $this->folder);
		$this->assertTrue($result);

		// mount point in the root folder should no longer exist
		$this->assertFalse($user2View->is_dir($this->folder));

		// delete the local folder
		$result = $user2View->unlink('/localfolder');
		$this->assertTrue($result);

		//enforce reload of the mount points
		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);

		//mount point should be back at the root
		$this->assertTrue($user2View->is_dir($this->folder));

		//cleanup
		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);
		$this->view->unlink($this->folder);
	}

	function testMoveSharedFile() {
		$fileinfo = $this->view->getFileInfo($this->filename);
		$result = \OCP\Share::shareItem('file', $fileinfo['fileid'], \OCP\Share::SHARE_TYPE_USER,
			self::TEST_FILES_SHARING_API_USER2, 31);

		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);

		\OC\Files\Filesystem::rename($this->filename, "newFileName");

		$this->assertTrue(\OC\Files\Filesystem::file_exists('newFileName'));
		$this->assertFalse(\OC\Files\Filesystem::file_exists($this->filename));

		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);
		$this->assertTrue(\OC\Files\Filesystem::file_exists($this->filename));
		$this->assertFalse(\OC\Files\Filesystem::file_exists("newFileName"));

		//cleanup
		\OCP\Share::unshare('file', $fileinfo['fileid'], \OCP\Share::SHARE_TYPE_USER, self::TEST_FILES_SHARING_API_USER2);
	}

	/**
	 * share file with a group if a user renames the file the filename should not change
	 * for the other users
	 */
	function testMoveGroupShare () {
		\OC_Group::createGroup('testGroup');
		\OC_Group::addToGroup(self::TEST_FILES_SHARING_API_USER1, 'testGroup');
		\OC_Group::addToGroup(self::TEST_FILES_SHARING_API_USER2, 'testGroup');
		\OC_Group::addToGroup(self::TEST_FILES_SHARING_API_USER3, 'testGroup');

		$fileinfo = $this->view->getFileInfo($this->filename);
		$result = \OCP\Share::shareItem('file', $fileinfo['fileid'], \OCP\Share::SHARE_TYPE_GROUP,
			"testGroup", 31);

		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);

		$this->assertTrue(\OC\Files\Filesystem::file_exists($this->filename));

		\OC\Files\Filesystem::rename($this->filename, "newFileName");

		$this->assertTrue(\OC\Files\Filesystem::file_exists('newFileName'));
		$this->assertFalse(\OC\Files\Filesystem::file_exists($this->filename));

		self::loginHelper(self::TEST_FILES_SHARING_API_USER3);
		$this->assertTrue(\OC\Files\Filesystem::file_exists($this->filename));
		$this->assertFalse(\OC\Files\Filesystem::file_exists("newFileName"));

		self::loginHelper(self::TEST_FILES_SHARING_API_USER3);
		$this->assertTrue(\OC\Files\Filesystem::file_exists($this->filename));
		$this->assertFalse(\OC\Files\Filesystem::file_exists("newFileName"));

		//cleanup
		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);
		\OCP\Share::unshare('file', $fileinfo['fileid'], \OCP\Share::SHARE_TYPE_GROUP, 'testGroup');
		\OC_Group::removeFromGroup(self::TEST_FILES_SHARING_API_USER1, 'testGroup');
		\OC_Group::removeFromGroup(self::TEST_FILES_SHARING_API_USER2, 'testGroup');
		\OC_Group::removeFromGroup(self::TEST_FILES_SHARING_API_USER3, 'testGroup');
	}

	/**
	 * @dataProvider dataProviderTestStripUserFilesPath
	 * @param string $path
	 * @param string $expectedResult
	 * @param bool $exception if a exception is expected
	 */
	function testStripUserFilesPath($path, $expectedResult, $exception) {
		$testClass = new DummyTestClassSharedMount(null, null);
		try {
			$result = $testClass->stripUserFilesPathDummy($path);
			$this->assertSame($expectedResult, $result);
		} catch (\Exception $e) {
			if ($exception) {
				$this->assertSame(10, $e->getCode());
			} else {
				$this->assertTrue(false, "Exception catched, but expected: " . $expectedResult);
			}
		}
	}

	function dataProviderTestStripUserFilesPath() {
		return array(
			array('/user/files/foo.txt', '/foo.txt', false),
			array('/user/files/folder/foo.txt', '/folder/foo.txt', false),
			array('/data/user/files/foo.txt', null, true),
			array('/data/user/files/', null, true),
			array('/files/foo.txt', null, true),
			array('/foo.txt', null, true),
		);
	}

}

class DummyTestClassSharedMount extends \OCA\Files_Sharing\SharedMount {
	public function __construct($storage, $mountpoint, $arguments = null, $loader = null){
		// noop
	}

	public function stripUserFilesPathDummy($path) {
		return $this->stripUserFilesPath($path);
	}
}
