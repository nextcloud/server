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
 * Class Test_Files_Sharing_Api
 */
class Test_Files_Sharing_Storage extends OCA\Files_sharing\Tests\TestCase {

	protected function setUp() {
		parent::setUp();
		\OCA\Files_Trashbin\Trashbin::registerHooks();
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
	 * if the parent of the mount point is gone then the mount point should move up
	 *
	 * @medium
	 */
	function testParentOfMountPointIsGone() {

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
		$fullPath = \OC_Config::getValue('datadirectory') . '/' . self::TEST_FILES_SHARING_API_USER2 . '/files/localfolder';
		rmdir($fullPath);

		//enforce reload of the mount points
		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);

		//mount point should be back at the root
		$this->assertTrue($user2View->is_dir($this->folder));

		//cleanup
		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);
		$this->view->unlink($this->folder);
	}

	/**
	 * @medium
	 */
	function testRenamePartFile() {

		// share to user
		$fileinfo = $this->view->getFileInfo($this->folder);
		$result = \OCP\Share::shareItem('folder', $fileinfo['fileid'], \OCP\Share::SHARE_TYPE_USER,
			self::TEST_FILES_SHARING_API_USER2, 31);

		$this->assertTrue($result);

		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);
		$user2View = new \OC\Files\View('/' . self::TEST_FILES_SHARING_API_USER2 . '/files');

		$this->assertTrue($user2View->file_exists($this->folder));

		// create part file
		$result = $user2View->file_put_contents($this->folder. '/foo.txt.part', 'some test data');

		$this->assertTrue(is_int($result));
		// rename part file to real file
		$result = $user2View->rename($this->folder. '/foo.txt.part', $this->folder. '/foo.txt');

		$this->assertTrue($result);

		// check if the new file really exists
		$this->assertTrue($user2View->file_exists( $this->folder. '/foo.txt'));

		// check if the rename also affected the owner
		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);

		$this->assertTrue($this->view->file_exists( $this->folder. '/foo.txt'));

		//cleanup
		\OCP\Share::unshare('folder', $fileinfo['fileid'], \OCP\Share::SHARE_TYPE_USER,
			self::TEST_FILES_SHARING_API_USER2);
	}

	public function testFilesize() {

		$fileinfoFolder = $this->view->getFileInfo($this->folder);
		$fileinfoFile = $this->view->getFileInfo($this->filename);

		$folderSize = $this->view->filesize($this->folder);
		$file1Size = $this->view->filesize($this->folder. $this->filename);
		$file2Size = $this->view->filesize($this->filename);

		$result = \OCP\Share::shareItem('folder', $fileinfoFolder['fileid'], \OCP\Share::SHARE_TYPE_USER,
			self::TEST_FILES_SHARING_API_USER2, 31);
		$this->assertTrue($result);

		$result = \OCP\Share::shareItem('file', $fileinfoFile['fileid'], \OCP\Share::SHARE_TYPE_USER,
			self::TEST_FILES_SHARING_API_USER2, 31);
		$this->assertTrue($result);

		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);

		// compare file size between user1 and user2, should always be the same
		$this->assertSame($folderSize, \OC\Files\Filesystem::filesize($this->folder));
		$this->assertSame($file1Size, \OC\Files\Filesystem::filesize($this->folder . $this->filename));
		$this->assertSame($file2Size, \OC\Files\Filesystem::filesize($this->filename));

		//cleanup
		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);
		$result = \OCP\Share::unshare('folder', $fileinfoFolder['fileid'], \OCP\Share::SHARE_TYPE_USER,
			self::TEST_FILES_SHARING_API_USER2);
		$this->assertTrue($result);
		$result = \OCP\Share::unshare('file', $fileinfoFile['fileid'], \OCP\Share::SHARE_TYPE_USER,
			self::TEST_FILES_SHARING_API_USER2);
		$this->assertTrue($result);
	}

	function testGetPermissions() {
		$fileinfoFolder = $this->view->getFileInfo($this->folder);

		$result = \OCP\Share::shareItem('folder', $fileinfoFolder['fileid'], \OCP\Share::SHARE_TYPE_USER,
			self::TEST_FILES_SHARING_API_USER2, 1);
		$this->assertTrue($result);

		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);

		$this->assertTrue(\OC\Files\Filesystem::is_dir($this->folder));

		// for the share root we expect:
		// the shared permissions (1)
		// the delete permission (8), to enable unshare
		// the update permission (2), to allow renaming of the mount point
		$rootInfo = \OC\Files\Filesystem::getFileInfo($this->folder);
		$this->assertSame(11, $rootInfo->getPermissions());

		// for the file within the shared folder we expect:
		// the shared permissions (1)
		$subfileInfo = \OC\Files\Filesystem::getFileInfo($this->folder . $this->filename);
		$this->assertSame(1, $subfileInfo->getPermissions());


		//cleanup
		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);
		$result = \OCP\Share::unshare('folder', $fileinfoFolder['fileid'], \OCP\Share::SHARE_TYPE_USER,
			self::TEST_FILES_SHARING_API_USER2);
		$this->assertTrue($result);
	}

	function testMountSharesOtherUser() {
		$folderInfo = $this->view->getFileInfo($this->folder);
		$fileInfo = $this->view->getFileInfo($this->filename);
		$rootView = new \OC\Files\View('');
		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);

		// share 2 different files with 2 different users
		\OCP\Share::shareItem('folder', $folderInfo['fileid'], \OCP\Share::SHARE_TYPE_USER,
			self::TEST_FILES_SHARING_API_USER2, 31);
		\OCP\Share::shareItem('file', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_USER,
			self::TEST_FILES_SHARING_API_USER3, 31);

		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);
		$this->assertTrue($rootView->file_exists('/' . self::TEST_FILES_SHARING_API_USER2 . '/files/' . $this->folder));
		OC_Hook::emit('OC_Filesystem', 'setup', array('user' => self::TEST_FILES_SHARING_API_USER3, 'user_dir' => \OC_User::getHome(self::TEST_FILES_SHARING_API_USER3)));

		$this->assertTrue($rootView->file_exists('/' . self::TEST_FILES_SHARING_API_USER3 . '/files/' . $this->filename));

		// make sure we didn't double setup shares for user 2 or mounted the shares for user 3 in user's 2 home
		$this->assertFalse($rootView->file_exists('/' . self::TEST_FILES_SHARING_API_USER2 . '/files/' . $this->folder .' (2)'));
		$this->assertFalse($rootView->file_exists('/' . self::TEST_FILES_SHARING_API_USER2 . '/files/' . $this->filename));

		//cleanup
		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);
		$this->view->unlink($this->folder);
	}
}
