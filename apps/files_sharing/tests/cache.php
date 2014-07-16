<?php
/**
 * ownCloud
 *
 * @author Vincent Petry, Bjoern Schiessle
 * @copyright 2014 Vincent Petry <pvince81@owncloud.com>
 *            2014 Bjoern Schiessle <schiessle@owncloud.com>
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

class Test_Files_Sharing_Cache extends Test_Files_Sharing_Base {

	/**
	 * @var OC\Files\View
	 */
	public $user2View;

	function setUp() {
		parent::setUp();

		\OC_User::setDisplayName(self::TEST_FILES_SHARING_API_USER1, 'User One');
		\OC_User::setDisplayName(self::TEST_FILES_SHARING_API_USER2, 'User Two');

		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);

		$this->user2View = new \OC\Files\View('/'. self::TEST_FILES_SHARING_API_USER2 . '/files');

		// prepare user1's dir structure
		$this->view->mkdir('container');
		$this->view->mkdir('container/shareddir');
		$this->view->mkdir('container/shareddir/subdir');
		$this->view->mkdir('container/shareddir/emptydir');

		$textData = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$this->view->file_put_contents('container/not shared.txt', $textData);
		$this->view->file_put_contents('container/shared single file.txt', $textData);
		$this->view->file_put_contents('container/shareddir/bar.txt', $textData);
		$this->view->file_put_contents('container/shareddir/subdir/another.txt', $textData);
		$this->view->file_put_contents('container/shareddir/subdir/another too.txt', $textData);
		$this->view->file_put_contents('container/shareddir/subdir/not a text file.xml', '<xml></xml>');

		list($this->ownerStorage, $internalPath) = $this->view->resolvePath('');
		$this->ownerCache = $this->ownerStorage->getCache();
		$this->ownerStorage->getScanner()->scan('');

		// share "shareddir" with user2
		$fileinfo = $this->view->getFileInfo('container/shareddir');
		\OCP\Share::shareItem('folder', $fileinfo['fileid'], \OCP\Share::SHARE_TYPE_USER,
			self::TEST_FILES_SHARING_API_USER2, 31);

		$fileinfo = $this->view->getFileInfo('container/shared single file.txt');
		\OCP\Share::shareItem('file', $fileinfo['fileid'], \OCP\Share::SHARE_TYPE_USER,
			self::TEST_FILES_SHARING_API_USER2, 31);

		// login as user2
		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);

		// retrieve the shared storage
		$secondView = new \OC\Files\View('/' . self::TEST_FILES_SHARING_API_USER2);
		list($this->sharedStorage, $internalPath) = $secondView->resolvePath('files/shareddir');
		$this->sharedCache = $this->sharedStorage->getCache();
	}

	function tearDown() {
		$this->sharedCache->clear();

		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);

		$fileinfo = $this->view->getFileInfo('container/shareddir');
		\OCP\Share::unshare('folder', $fileinfo['fileid'], \OCP\Share::SHARE_TYPE_USER,
			self::TEST_FILES_SHARING_API_USER2);

		$fileinfo = $this->view->getFileInfo('container/shared single file.txt');
		\OCP\Share::unshare('file', $fileinfo['fileid'], \OCP\Share::SHARE_TYPE_USER,
			self::TEST_FILES_SHARING_API_USER2);

		$this->view->deleteAll('container');

		$this->ownerCache->clear();

		parent::tearDown();
	}

	/**
	 * Test searching by mime type
	 */
	function testSearchByMime() {
		$results = $this->sharedStorage->getCache()->searchByMime('text');
		$check = array(
				array(
					'name' => 'bar.txt',
					'path' => 'bar.txt'
				),
				array(
					'name' => 'another too.txt',
					'path' => 'subdir/another too.txt'
				),
				array(
					'name' => 'another.txt',
					'path' => 'subdir/another.txt'
				),
			);
		$this->verifyFiles($check, $results);

		$this->verifyFiles($check, $results);
	}

	function testGetFolderContentsInRoot() {
		$results = $this->user2View->getDirectoryContent('/');

		// we should get the shared items "shareddir" and "shared single file.txt"
		// additional root will always contain the example file "welcome.txt",
		//  so this will be part of the result
		$this->verifyFiles(
			array(
				array(
					'name' => 'welcome.txt',
					'path' => 'files/welcome.txt',
					'mimetype' => 'text/plain',
				),
				array(
					'name' => 'shareddir',
					'path' => 'files/shareddir',
					'mimetype' => 'httpd/unix-directory',
					'uid_owner' => self::TEST_FILES_SHARING_API_USER1,
					'displayname_owner' => 'User One',
				),
				array(
					'name' => 'shared single file.txt',
					'path' => 'files/shared single file.txt',
					'mimetype' => 'text/plain',
					'uid_owner' => self::TEST_FILES_SHARING_API_USER1,
					'displayname_owner' => 'User One',
				),
			),
			$results
		);
	}

	function testGetFolderContentsInSubdir() {
		$results = $this->user2View->getDirectoryContent('/shareddir');

		$this->verifyFiles(
			array(
				array(
					'name' => 'bar.txt',
					'path' => 'bar.txt',
					'mimetype' => 'text/plain',
					'uid_owner' => self::TEST_FILES_SHARING_API_USER1,
					'displayname_owner' => 'User One',
				),
				array(
					'name' => 'emptydir',
					'path' => 'emptydir',
					'mimetype' => 'httpd/unix-directory',
					'uid_owner' => self::TEST_FILES_SHARING_API_USER1,
					'displayname_owner' => 'User One',
				),
				array(
					'name' => 'subdir',
					'path' => 'subdir',
					'mimetype' => 'httpd/unix-directory',
					'uid_owner' => self::TEST_FILES_SHARING_API_USER1,
					'displayname_owner' => 'User One',
				),
			),
			$results
		);
	}

	function testGetFolderContentsWhenSubSubdirShared() {
		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);

		$fileinfo = $this->view->getFileInfo('container/shareddir/subdir');
		\OCP\Share::shareItem('folder', $fileinfo['fileid'], \OCP\Share::SHARE_TYPE_USER,
			self::TEST_FILES_SHARING_API_USER3, 31);

		self::loginHelper(self::TEST_FILES_SHARING_API_USER3);

		$thirdView = new \OC\Files\View('/' . self::TEST_FILES_SHARING_API_USER3 . '/files');
		$results = $thirdView->getDirectoryContent('/subdir');

		$this->verifyFiles(
			array(
				array(
					'name' => 'another too.txt',
					'path' => 'another too.txt',
					'mimetype' => 'text/plain',
					'uid_owner' => self::TEST_FILES_SHARING_API_USER1,
					'displayname_owner' => 'User One',
				),
				array(
					'name' => 'another.txt',
					'path' => 'another.txt',
					'mimetype' => 'text/plain',
					'uid_owner' => self::TEST_FILES_SHARING_API_USER1,
					'displayname_owner' => 'User One',
				),
				array(
					'name' => 'not a text file.xml',
					'path' => 'not a text file.xml',
					'mimetype' => 'application/xml',
					'uid_owner' => self::TEST_FILES_SHARING_API_USER1,
					'displayname_owner' => 'User One',
				),
			),
			$results
		);

		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);

		\OCP\Share::unshare('folder', $fileinfo['fileid'], \OCP\Share::SHARE_TYPE_USER,
			self::TEST_FILES_SHARING_API_USER3);
	}

	/**
	 * Check if 'results' contains the expected 'examples' only.
	 *
	 * @param array $examples array of example files
	 * @param array $results array of files
	 */
	private function verifyFiles($examples, $results) {
		$this->assertEquals(count($examples), count($results));

		foreach ($examples as $example) {
			foreach ($results as $key => $result) {
				if ($result['name'] === $example['name']) {
					$this->verifyKeys($example, $result);
					unset($results[$key]);
					break;
				}
			}
		}
		$this->assertTrue(empty($results));
	}

	/**
	 * verify if each value from the result matches the expected result
	 * @param array $example array with the expected results
	 * @param array $result array with the results
	 */
	private function verifyKeys($example, $result) {
		foreach ($example as $key => $value) {
			$this->assertEquals($value, $result[$key]);
		}
	}

	public function testGetPathByIdDirectShare() {
		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);
		\OC\Files\Filesystem::file_put_contents('test.txt', 'foo');
		$info = \OC\Files\Filesystem::getFileInfo('test.txt');
		\OCP\Share::shareItem('file', $info->getId(), \OCP\Share::SHARE_TYPE_USER, self::TEST_FILES_SHARING_API_USER2, \OCP\PERMISSION_ALL);
		\OC_Util::tearDownFS();

		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);
		$this->assertTrue(\OC\Files\Filesystem::file_exists('/test.txt'));
		list($sharedStorage) = \OC\Files\Filesystem::resolvePath('/' . self::TEST_FILES_SHARING_API_USER2 . '/files/test.txt');
		/**
		 * @var \OC\Files\Storage\Shared $sharedStorage
		 */

		$sharedCache = $sharedStorage->getCache();
		$this->assertEquals('', $sharedCache->getPathById($info->getId()));
	}

	public function testGetPathByIdShareSubFolder() {
		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);
		\OC\Files\Filesystem::mkdir('foo');
		\OC\Files\Filesystem::mkdir('foo/bar');
		\OC\Files\Filesystem::touch('foo/bar/test.txt', 'bar');
		$folderInfo = \OC\Files\Filesystem::getFileInfo('foo');
		$fileInfo = \OC\Files\Filesystem::getFileInfo('foo/bar/test.txt');
		\OCP\Share::shareItem('folder', $folderInfo->getId(), \OCP\Share::SHARE_TYPE_USER, self::TEST_FILES_SHARING_API_USER2, \OCP\PERMISSION_ALL);
		\OC_Util::tearDownFS();

		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);
		$this->assertTrue(\OC\Files\Filesystem::file_exists('/foo'));
		list($sharedStorage) = \OC\Files\Filesystem::resolvePath('/' . self::TEST_FILES_SHARING_API_USER2 . '/files/foo');
		/**
		 * @var \OC\Files\Storage\Shared $sharedStorage
		 */

		$sharedCache = $sharedStorage->getCache();
		$this->assertEquals('', $sharedCache->getPathById($folderInfo->getId()));
		$this->assertEquals('bar/test.txt', $sharedCache->getPathById($fileInfo->getId()));
	}
}
