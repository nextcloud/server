<?php
/**
 * ownCloud
 *
 * @author Vincent Petry
 * @copyright 2013 Vincent Petry <pvince81@owncloud.com>
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
use OC\Files\Cache\Cache;
use OC\Files\Storage\Storage;
use OC\Files\View;


class Test_Files_Sharing_Permissions extends OCA\Files_sharing\Tests\TestCase {

	/**
	 * @var Storage
	 */
	private $sharedStorageRestrictedShare;

	/**
	 * @var Storage
	 */
	private $sharedCacheRestrictedShare;

	/**
	 * @var View
	 */
	private $secondView;

	/**
	 * @var Storage
	 */
	private $ownerStorage;

	/**
	 * @var Storage
	 */
	private $sharedStorage;

	/**
	 * @var Cache
	 */
	private $sharedCache;

	/**
	 * @var Cache
	 */
	private $ownerCache;

	protected function setUp() {
		parent::setUp();

		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);

		// prepare user1's dir structure
		$textData = "dummy file data\n";
		$this->view->mkdir('container');
		$this->view->mkdir('container/shareddir');
		$this->view->mkdir('container/shareddir/subdir');
		$this->view->mkdir('container/shareddirrestricted');
		$this->view->mkdir('container/shareddirrestricted/subdir');
		$this->view->file_put_contents('container/shareddir/textfile.txt', $textData);
		$this->view->file_put_contents('container/shareddirrestricted/textfile1.txt', $textData);

		list($this->ownerStorage, $internalPath) = $this->view->resolvePath('');
		$this->ownerCache = $this->ownerStorage->getCache();
		$this->ownerStorage->getScanner()->scan('');

		// share "shareddir" with user2
		$fileinfo = $this->view->getFileInfo('container/shareddir');
		\OCP\Share::shareItem('folder', $fileinfo['fileid'], \OCP\Share::SHARE_TYPE_USER,
			self::TEST_FILES_SHARING_API_USER2, 31);
		$fileinfo2 = $this->view->getFileInfo('container/shareddirrestricted');
		\OCP\Share::shareItem('folder', $fileinfo2['fileid'], \OCP\Share::SHARE_TYPE_USER,
			self::TEST_FILES_SHARING_API_USER2, 7);

		// login as user2
		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);

		// retrieve the shared storage
		$this->secondView = new \OC\Files\View('/' . self::TEST_FILES_SHARING_API_USER2);
		list($this->sharedStorage, $internalPath) = $this->secondView->resolvePath('files/shareddir');
		list($this->sharedStorageRestrictedShare, $internalPath) = $this->secondView->resolvePath('files/shareddirrestricted');
		$this->sharedCache = $this->sharedStorage->getCache();
		$this->sharedCacheRestrictedShare = $this->sharedStorageRestrictedShare->getCache();
	}

	protected function tearDown() {
		$this->sharedCache->clear();

		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);

		$fileinfo = $this->view->getFileInfo('container/shareddir');
		\OCP\Share::unshare('folder', $fileinfo['fileid'], \OCP\Share::SHARE_TYPE_USER,
			self::TEST_FILES_SHARING_API_USER2);
		$fileinfo2 = $this->view->getFileInfo('container/shareddirrestricted');
		\OCP\Share::unshare('folder', $fileinfo2['fileid'], \OCP\Share::SHARE_TYPE_USER,
			self::TEST_FILES_SHARING_API_USER2);

		$this->view->deleteAll('container');

		$this->ownerCache->clear();

		parent::tearDown();
	}

	/**
	 * Test that the permissions of shared directory are returned correctly
	 */
	function testGetPermissions() {
		$sharedDirPerms = $this->sharedStorage->getPermissions('shareddir');
		$this->assertEquals(31, $sharedDirPerms);
		$sharedDirPerms = $this->sharedStorage->getPermissions('shareddir/textfile.txt');
		$this->assertEquals(31, $sharedDirPerms);
		$sharedDirRestrictedPerms = $this->sharedStorageRestrictedShare->getPermissions('shareddirrestricted');
		$this->assertEquals(7, $sharedDirRestrictedPerms);
		$sharedDirRestrictedPerms = $this->sharedStorageRestrictedShare->getPermissions('shareddirrestricted/textfile.txt');
		$this->assertEquals(7, $sharedDirRestrictedPerms);
	}

	/**
	 * Test that the permissions of shared directory are returned correctly
	 */
	function testGetDirectoryPermissions() {
		$contents = $this->secondView->getDirectoryContent('files/shareddir');
		$this->assertEquals('subdir', $contents[0]['name']);
		$this->assertEquals(31, $contents[0]['permissions']);
		$this->assertEquals('textfile.txt', $contents[1]['name']);
		// 27 is correct because create is reserved to folders only - requires more unit tests overall to ensure this
		$this->assertEquals(27, $contents[1]['permissions']);
		$contents = $this->secondView->getDirectoryContent('files/shareddirrestricted');
		$this->assertEquals('subdir', $contents[0]['name']);
		$this->assertEquals(7, $contents[0]['permissions']);
		$this->assertEquals('textfile1.txt', $contents[1]['name']);
		// 3 is correct because create is reserved to folders only
		$this->assertEquals(3, $contents[1]['permissions']);
	}
}
