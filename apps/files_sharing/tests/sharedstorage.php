<?php
/**
 * @author Björn Schießle <schiessle@owncloud.com>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
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

		\OC\Files\Filesystem::getLoader()->removeStorageWrapper('oc_trashbin');

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
		$result = $user2View->file_put_contents($this->folder . '/foo.txt.part', 'some test data');

		$this->assertTrue(is_int($result));
		// rename part file to real file
		$result = $user2View->rename($this->folder . '/foo.txt.part', $this->folder . '/foo.txt');

		$this->assertTrue($result);

		// check if the new file really exists
		$this->assertTrue($user2View->file_exists($this->folder . '/foo.txt'));

		// check if the rename also affected the owner
		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);

		$this->assertTrue($this->view->file_exists($this->folder . '/foo.txt'));

		//cleanup
		\OCP\Share::unshare('folder', $fileinfo['fileid'], \OCP\Share::SHARE_TYPE_USER,
			self::TEST_FILES_SHARING_API_USER2);
	}

	public function testFilesize() {

		$fileinfoFolder = $this->view->getFileInfo($this->folder);
		$fileinfoFile = $this->view->getFileInfo($this->filename);

		$folderSize = $this->view->filesize($this->folder);
		$file1Size = $this->view->filesize($this->folder . $this->filename);
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
		$rootInfo = \OC\Files\Filesystem::getFileInfo($this->folder);
		$this->assertSame(9, $rootInfo->getPermissions());

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

	public function testFopenWithReadOnlyPermission() {
		$this->view->file_put_contents($this->folder . '/existing.txt', 'foo');
		$fileinfoFolder = $this->view->getFileInfo($this->folder);
		$result = \OCP\Share::shareItem('folder', $fileinfoFolder['fileid'], \OCP\Share::SHARE_TYPE_USER,
			self::TEST_FILES_SHARING_API_USER2, \OCP\Constants::PERMISSION_READ);
		$this->assertTrue($result);

		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);
		$user2View = new \OC\Files\View('/' . self::TEST_FILES_SHARING_API_USER2 . '/files');

		// part file should be forbidden
		$handle = $user2View->fopen($this->folder . '/test.txt.part', 'w');
		$this->assertFalse($handle);

		// regular file forbidden
		$handle = $user2View->fopen($this->folder . '/test.txt', 'w');
		$this->assertFalse($handle);

		// rename forbidden
		$this->assertFalse($user2View->rename($this->folder . '/existing.txt', $this->folder . '/existing2.txt'));

		// delete forbidden
		$this->assertFalse($user2View->unlink($this->folder . '/existing.txt'));

		//cleanup
		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);
		$result = \OCP\Share::unshare('folder', $fileinfoFolder['fileid'], \OCP\Share::SHARE_TYPE_USER,
			self::TEST_FILES_SHARING_API_USER2);
		$this->assertTrue($result);
	}

	public function testFopenWithCreateOnlyPermission() {
		$this->view->file_put_contents($this->folder . '/existing.txt', 'foo');
		$fileinfoFolder = $this->view->getFileInfo($this->folder);
		$result = \OCP\Share::shareItem('folder', $fileinfoFolder['fileid'], \OCP\Share::SHARE_TYPE_USER,
			self::TEST_FILES_SHARING_API_USER2, \OCP\Constants::PERMISSION_READ | \OCP\Constants::PERMISSION_CREATE);
		$this->assertTrue($result);

		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);
		$user2View = new \OC\Files\View('/' . self::TEST_FILES_SHARING_API_USER2 . '/files');

		// create part file allowed
		$handle = $user2View->fopen($this->folder . '/test.txt.part', 'w');
		$this->assertNotFalse($handle);
		fclose($handle);

		// create regular file allowed
		$handle = $user2View->fopen($this->folder . '/test-create.txt', 'w');
		$this->assertNotFalse($handle);
		fclose($handle);

		// rename file never allowed
		$this->assertFalse($user2View->rename($this->folder . '/test-create.txt', $this->folder . '/newtarget.txt'));
		$this->assertFalse($user2View->file_exists($this->folder . '/newtarget.txt'));

		// rename file not allowed if target exists 
		$this->assertFalse($user2View->rename($this->folder . '/newtarget.txt', $this->folder . '/existing.txt'));

		// overwriting file not allowed
		$handle = $user2View->fopen($this->folder . '/existing.txt', 'w');
		$this->assertFalse($handle);

		// overwrite forbidden (no update permission)
		$this->assertFalse($user2View->rename($this->folder . '/test.txt.part', $this->folder . '/existing.txt'));

		// delete forbidden
		$this->assertFalse($user2View->unlink($this->folder . '/existing.txt'));

		//cleanup
		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);
		$result = \OCP\Share::unshare('folder', $fileinfoFolder['fileid'], \OCP\Share::SHARE_TYPE_USER,
			self::TEST_FILES_SHARING_API_USER2);
		$this->assertTrue($result);
	}

	public function testFopenWithUpdateOnlyPermission() {
		$this->view->file_put_contents($this->folder . '/existing.txt', 'foo');
		$fileinfoFolder = $this->view->getFileInfo($this->folder);

		$result = \OCP\Share::shareItem('folder', $fileinfoFolder['fileid'], \OCP\Share::SHARE_TYPE_USER,
			self::TEST_FILES_SHARING_API_USER2, \OCP\Constants::PERMISSION_READ | \OCP\Constants::PERMISSION_UPDATE);
		$this->assertTrue($result);

		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);
		$user2View = new \OC\Files\View('/' . self::TEST_FILES_SHARING_API_USER2 . '/files');

		// create part file allowed
		$handle = $user2View->fopen($this->folder . '/test.txt.part', 'w');
		$this->assertNotFalse($handle);
		fclose($handle);

		// create regular file not allowed
		$handle = $user2View->fopen($this->folder . '/test-create.txt', 'w');
		$this->assertFalse($handle);

		// rename part file not allowed to non-existing file
		$this->assertFalse($user2View->rename($this->folder . '/test.txt.part', $this->folder . '/nonexist.txt'));

		// rename part file allowed to target existing file
		$this->assertTrue($user2View->rename($this->folder . '/test.txt.part', $this->folder . '/existing.txt'));
		$this->assertTrue($user2View->file_exists($this->folder . '/existing.txt'));

		// rename regular file allowed
		$this->assertTrue($user2View->rename($this->folder . '/existing.txt', $this->folder . '/existing-renamed.txt'));
		$this->assertTrue($user2View->file_exists($this->folder . '/existing-renamed.txt'));

		// overwriting file directly is allowed
		$handle = $user2View->fopen($this->folder . '/existing-renamed.txt', 'w');
		$this->assertNotFalse($handle);
		fclose($handle);

		// delete forbidden
		$this->assertFalse($user2View->unlink($this->folder . '/existing-renamed.txt'));

		//cleanup
		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);
		$result = \OCP\Share::unshare('folder', $fileinfoFolder['fileid'], \OCP\Share::SHARE_TYPE_USER,
			self::TEST_FILES_SHARING_API_USER2);
		$this->assertTrue($result);
	}

	public function testFopenWithDeleteOnlyPermission() {
		$this->view->file_put_contents($this->folder . '/existing.txt', 'foo');
		$fileinfoFolder = $this->view->getFileInfo($this->folder);
		$result = \OCP\Share::shareItem('folder', $fileinfoFolder['fileid'], \OCP\Share::SHARE_TYPE_USER,
			self::TEST_FILES_SHARING_API_USER2, \OCP\Constants::PERMISSION_READ | \OCP\Constants::PERMISSION_DELETE);
		$this->assertTrue($result);

		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);
		$user2View = new \OC\Files\View('/' . self::TEST_FILES_SHARING_API_USER2 . '/files');

		// part file should be forbidden
		$handle = $user2View->fopen($this->folder . '/test.txt.part', 'w');
		$this->assertFalse($handle);

		// regular file forbidden
		$handle = $user2View->fopen($this->folder . '/test.txt', 'w');
		$this->assertFalse($handle);

		// rename forbidden
		$this->assertFalse($user2View->rename($this->folder . '/existing.txt', $this->folder . '/existing2.txt'));

		// delete allowed
		$this->assertTrue($user2View->unlink($this->folder . '/existing.txt'));

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

		$mountConfigManager = \OC::$server->getMountProviderCollection();
		$mounts = $mountConfigManager->getMountsForUser(\OC::$server->getUserManager()->get(self::TEST_FILES_SHARING_API_USER3));
		array_walk($mounts, array(\OC\Files\Filesystem::getMountManager(), 'addMount'));

		$this->assertTrue($rootView->file_exists('/' . self::TEST_FILES_SHARING_API_USER3 . '/files/' . $this->filename));

		// make sure we didn't double setup shares for user 2 or mounted the shares for user 3 in user's 2 home
		$this->assertFalse($rootView->file_exists('/' . self::TEST_FILES_SHARING_API_USER2 . '/files/' . $this->folder . ' (2)'));
		$this->assertFalse($rootView->file_exists('/' . self::TEST_FILES_SHARING_API_USER2 . '/files/' . $this->filename));

		//cleanup
		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);
		$this->view->unlink($this->folder);
	}

	public function testCopyFromStorage() {
		$folderInfo = $this->view->getFileInfo($this->folder);
		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);

		// share 2 different files with 2 different users
		\OCP\Share::shareItem('folder', $folderInfo['fileid'], \OCP\Share::SHARE_TYPE_USER,
			self::TEST_FILES_SHARING_API_USER2, 31);

		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);
		$view = new \OC\Files\View('/' . self::TEST_FILES_SHARING_API_USER2 . '/files');
		$this->assertTrue($view->file_exists($this->folder));

		/**
		 * @var \OCP\Files\Storage $sharedStorage
		 */
		list($sharedStorage,) = $view->resolvePath($this->folder);
		$this->assertTrue($sharedStorage->instanceOfStorage('OCA\Files_Sharing\ISharedStorage'));

		$sourceStorage = new \OC\Files\Storage\Temporary(array());
		$sourceStorage->file_put_contents('foo.txt', 'asd');

		$sharedStorage->copyFromStorage($sourceStorage, 'foo.txt', 'bar.txt');
		$this->assertTrue($sharedStorage->file_exists('bar.txt'));
		$this->assertEquals('asd', $sharedStorage->file_get_contents('bar.txt'));

		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);
		$this->view->unlink($this->folder);
	}

	public function testMoveFromStorage() {
		$folderInfo = $this->view->getFileInfo($this->folder);
		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);

		// share 2 different files with 2 different users
		\OCP\Share::shareItem('folder', $folderInfo['fileid'], \OCP\Share::SHARE_TYPE_USER,
			self::TEST_FILES_SHARING_API_USER2, 31);

		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);
		$view = new \OC\Files\View('/' . self::TEST_FILES_SHARING_API_USER2 . '/files');
		$this->assertTrue($view->file_exists($this->folder));

		/**
		 * @var \OCP\Files\Storage $sharedStorage
		 */
		list($sharedStorage,) = $view->resolvePath($this->folder);
		$this->assertTrue($sharedStorage->instanceOfStorage('OCA\Files_Sharing\ISharedStorage'));

		$sourceStorage = new \OC\Files\Storage\Temporary(array());
		$sourceStorage->file_put_contents('foo.txt', 'asd');

		$sharedStorage->moveFromStorage($sourceStorage, 'foo.txt', 'bar.txt');
		$this->assertTrue($sharedStorage->file_exists('bar.txt'));
		$this->assertEquals('asd', $sharedStorage->file_get_contents('bar.txt'));

		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);
		$this->view->unlink($this->folder);
	}
}
