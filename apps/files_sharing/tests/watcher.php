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
require_once __DIR__ . '/base.php';

class Test_Files_Sharing_Watcher extends Test_Files_Sharing_Base {

	function setUp() {
		parent::setUp();

		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);

		// prepare user1's dir structure
		$textData = "dummy file data\n";
		$this->view->mkdir('container');
		$this->view->mkdir('container/shareddir');
		$this->view->mkdir('container/shareddir/subdir');

		list($this->ownerStorage, $internalPath) = $this->view->resolvePath('');
		$this->ownerCache = $this->ownerStorage->getCache();
		$this->ownerStorage->getScanner()->scan('');

		// share "shareddir" with user2
		$fileinfo = $this->view->getFileInfo('container/shareddir');
		\OCP\Share::shareItem('folder', $fileinfo['fileid'], \OCP\Share::SHARE_TYPE_USER,
			self::TEST_FILES_SHARING_API_USER2, 31);

		// login as user2
		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);

		// retrieve the shared storage
		$secondView = new \OC\Files\View('/' . self::TEST_FILES_SHARING_API_USER2);
		list($this->sharedStorage, $internalPath) = $secondView->resolvePath('files/Shared/shareddir');
		$this->sharedCache = $this->sharedStorage->getCache();
	}

	function tearDown() {
		$this->sharedCache->clear();

		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);

		$fileinfo = $this->view->getFileInfo('container/shareddir');
		\OCP\Share::unshare('folder', $fileinfo['fileid'], \OCP\Share::SHARE_TYPE_USER,
			self::TEST_FILES_SHARING_API_USER2);

		$this->view->deleteAll('container');

		$this->ownerCache->clear();

		parent::tearDown();
	}

	/**
	 * Tests that writing a file using the shared storage will propagate the file
	 * size to the owner's parent folders.
	 */
	function testFolderSizePropagationToOwnerStorage() {
		$initialSizes = self::getOwnerDirSizes('files/container/shareddir');

		$textData = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$dataLen = strlen($textData);
		$this->sharedCache->put('shareddir/bar.txt', array('storage_mtime' => 10));
		$this->sharedStorage->file_put_contents('shareddir/bar.txt', $textData);
		$this->sharedCache->put('shareddir', array('storage_mtime' => 10));

		// run the propagation code
		$result = $this->sharedStorage->getWatcher()->checkUpdate('shareddir');

		$this->assertTrue($result);

		// the owner's parent dirs must have increase size
		$newSizes = self::getOwnerDirSizes('files/container/shareddir');
		$this->assertEquals($initialSizes[''] + $dataLen, $newSizes['']);
		$this->assertEquals($initialSizes['files'] + $dataLen, $newSizes['files']);
		$this->assertEquals($initialSizes['files/container'] + $dataLen, $newSizes['files/container']);
		$this->assertEquals($initialSizes['files/container/shareddir'] + $dataLen, $newSizes['files/container/shareddir']);

		// no more updates
		$result = $this->sharedStorage->getWatcher()->checkUpdate('shareddir');

		$this->assertFalse($result);
	}

	/**
	 * Tests that writing a file using the shared storage will propagate the file
	 * size to the owner's parent folders.
	 */
	function testSubFolderSizePropagationToOwnerStorage() {
		$initialSizes = self::getOwnerDirSizes('files/container/shareddir/subdir');

		$textData = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$dataLen = strlen($textData);
		$this->sharedCache->put('shareddir/subdir/bar.txt', array('storage_mtime' => 10));
		$this->sharedStorage->file_put_contents('shareddir/subdir/bar.txt', $textData);
		$this->sharedCache->put('shareddir/subdir', array('storage_mtime' => 10));

		// run the propagation code
		$result = $this->sharedStorage->getWatcher()->checkUpdate('shareddir/subdir');

		$this->assertTrue($result);

		// the owner's parent dirs must have increase size
		$newSizes = self::getOwnerDirSizes('files/container/shareddir/subdir');
		$this->assertEquals($initialSizes[''] + $dataLen, $newSizes['']);
		$this->assertEquals($initialSizes['files'] + $dataLen, $newSizes['files']);
		$this->assertEquals($initialSizes['files/container'] + $dataLen, $newSizes['files/container']);
		$this->assertEquals($initialSizes['files/container/shareddir'] + $dataLen, $newSizes['files/container/shareddir']);
		$this->assertEquals($initialSizes['files/container/shareddir/subdir'] + $dataLen, $newSizes['files/container/shareddir/subdir']);

		// no more updates
		$result = $this->sharedStorage->getWatcher()->checkUpdate('shareddir/subdir');

		$this->assertFalse($result);
	}

	function testNoUpdateOnRoot() {
		// no updates when called for root path
		$result = $this->sharedStorage->getWatcher()->checkUpdate('');

		$this->assertFalse($result);

		// FIXME: for some reason when running this "naked" test,
		// there will be remaining nonsensical entries in the
		// database with a path "test-share-user1/container/..."
	}

	/**
	 * Returns the sizes of the path and its parent dirs in a hash
	 * where the key is the path and the value is the size.
	 */
	function getOwnerDirSizes($path) {
		$result = array();

		while ($path != '' && $path != '' && $path != '.') {
			$cachedData = $this->ownerCache->get($path);
			$result[$path] = $cachedData['size'];
			$path = dirname($path);
		}
		$cachedData = $this->ownerCache->get('');
		$result[''] = $cachedData['size'];
		return $result;
	}
}
