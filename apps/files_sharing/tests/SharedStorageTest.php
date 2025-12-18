<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Sharing\Tests;

use OC\Files\Cache\FailedCache;
use OC\Files\Filesystem;
use OC\Files\Storage\FailedStorage;
use OC\Files\Storage\Temporary;
use OC\Files\View;
use OCA\Files_Sharing\SharedStorage;
use OCA\Files_Trashbin\AppInfo\Application;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\Constants;
use OCP\Files\Config\IMountProviderCollection;
use OCP\Files\NotFoundException;
use OCP\IUserManager;
use OCP\Server;
use OCP\Share\IShare;

/**
 * Class SharedStorageTest
 */
#[\PHPUnit\Framework\Attributes\Group('DB')]
class SharedStorageTest extends TestCase {
	protected function setUp(): void {
		parent::setUp();
		// register trashbin hooks
		$trashbinApp = new Application();
		$trashbinApp->boot($this->createMock(IBootContext::class));
		$this->folder = '/folder_share_storage_test';

		$this->filename = '/share-api-storage.txt';


		$this->view->mkdir($this->folder);

		// save file with content
		$this->view->file_put_contents($this->filename, 'root file');
		$this->view->file_put_contents($this->folder . $this->filename, 'file in subfolder');
	}

	protected function tearDown(): void {
		if ($this->view) {
			if ($this->view->file_exists($this->folder)) {
				$this->view->unlink($this->folder);
			}
			if ($this->view->file_exists($this->filename)) {
				$this->view->unlink($this->filename);
			}
		}

		Filesystem::getLoader()->removeStorageWrapper('oc_trashbin');

		parent::tearDown();
	}

	public function testRenamePartFile(): void {

		// share to user
		$share = $this->share(
			IShare::TYPE_USER,
			$this->folder,
			self::TEST_FILES_SHARING_API_USER1,
			self::TEST_FILES_SHARING_API_USER2,
			Constants::PERMISSION_ALL
		);


		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);
		$user2View = new View('/' . self::TEST_FILES_SHARING_API_USER2 . '/files');

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
		$this->shareManager->deleteShare($share);
	}

	public function testFilesize(): void {
		$folderSize = $this->view->filesize($this->folder);
		$file1Size = $this->view->filesize($this->folder . $this->filename);
		$file2Size = $this->view->filesize($this->filename);

		$share1 = $this->share(
			IShare::TYPE_USER,
			$this->folder,
			self::TEST_FILES_SHARING_API_USER1,
			self::TEST_FILES_SHARING_API_USER2,
			Constants::PERMISSION_ALL
		);

		$share2 = $this->share(
			IShare::TYPE_USER,
			$this->filename,
			self::TEST_FILES_SHARING_API_USER1,
			self::TEST_FILES_SHARING_API_USER2,
			Constants::PERMISSION_READ | Constants::PERMISSION_UPDATE | Constants::PERMISSION_SHARE
		);


		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);

		// compare file size between user1 and user2, should always be the same
		$this->assertSame($folderSize, Filesystem::filesize($this->folder));
		$this->assertSame($file1Size, Filesystem::filesize($this->folder . $this->filename));
		$this->assertSame($file2Size, Filesystem::filesize($this->filename));

		//cleanup
		$this->shareManager->deleteShare($share1);
		$this->shareManager->deleteShare($share2);
	}

	public function testGetPermissions(): void {
		$share = $this->share(
			IShare::TYPE_USER,
			$this->folder,
			self::TEST_FILES_SHARING_API_USER1,
			self::TEST_FILES_SHARING_API_USER2,
			Constants::PERMISSION_READ
		);


		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);

		$this->assertTrue(Filesystem::is_dir($this->folder));

		// for the share root we expect:
		// the read permissions (1)
		// the delete permission (8), to enable unshare
		$rootInfo = Filesystem::getFileInfo($this->folder);
		$this->assertSame(9, $rootInfo->getPermissions());

		// for the file within the shared folder we expect:
		// the read permissions (1)
		$subfileInfo = Filesystem::getFileInfo($this->folder . $this->filename);
		$this->assertSame(1, $subfileInfo->getPermissions());


		//cleanup
		$this->shareManager->deleteShare($share);
	}

	public function testFopenWithReadOnlyPermission(): void {
		$this->view->file_put_contents($this->folder . '/existing.txt', 'foo');

		$share = $this->share(
			IShare::TYPE_USER,
			$this->folder,
			self::TEST_FILES_SHARING_API_USER1,
			self::TEST_FILES_SHARING_API_USER2,
			Constants::PERMISSION_READ
		);

		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);
		$user2View = new View('/' . self::TEST_FILES_SHARING_API_USER2 . '/files');

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
		$this->shareManager->deleteShare($share);
	}

	public function testFopenWithCreateOnlyPermission(): void {
		$this->view->file_put_contents($this->folder . '/existing.txt', 'foo');
		$fileinfoFolder = $this->view->getFileInfo($this->folder);

		$share = $this->share(
			IShare::TYPE_USER,
			$this->folder,
			self::TEST_FILES_SHARING_API_USER1,
			self::TEST_FILES_SHARING_API_USER2,
			Constants::PERMISSION_READ | Constants::PERMISSION_CREATE
		);

		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);
		$user2View = new View('/' . self::TEST_FILES_SHARING_API_USER2 . '/files');

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
		$this->shareManager->deleteShare($share);
	}

	public function testFopenWithUpdateOnlyPermission(): void {
		$this->view->file_put_contents($this->folder . '/existing.txt', 'foo');

		$share = $this->share(
			IShare::TYPE_USER,
			$this->folder,
			self::TEST_FILES_SHARING_API_USER1,
			self::TEST_FILES_SHARING_API_USER2,
			Constants::PERMISSION_READ | Constants::PERMISSION_UPDATE
		);

		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);
		$user2View = new View('/' . self::TEST_FILES_SHARING_API_USER2 . '/files');

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
		$this->shareManager->deleteShare($share);
	}

	public function testFopenWithDeleteOnlyPermission(): void {
		$this->view->file_put_contents($this->folder . '/existing.txt', 'foo');

		$share = $this->share(
			IShare::TYPE_USER,
			$this->folder,
			self::TEST_FILES_SHARING_API_USER1,
			self::TEST_FILES_SHARING_API_USER2,
			Constants::PERMISSION_READ | Constants::PERMISSION_DELETE
		);

		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);
		$user2View = new View('/' . self::TEST_FILES_SHARING_API_USER2 . '/files');

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
		$this->shareManager->deleteShare($share);
	}

	public function testMountSharesOtherUser(): void {
		$rootView = new View('');
		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);

		// share 2 different files with 2 different users
		$share1 = $this->share(
			IShare::TYPE_USER,
			$this->folder,
			self::TEST_FILES_SHARING_API_USER1,
			self::TEST_FILES_SHARING_API_USER2,
			Constants::PERMISSION_ALL
		);
		$share2 = $this->share(
			IShare::TYPE_USER,
			$this->filename,
			self::TEST_FILES_SHARING_API_USER1,
			self::TEST_FILES_SHARING_API_USER3,
			Constants::PERMISSION_READ | Constants::PERMISSION_UPDATE | Constants::PERMISSION_SHARE
		);

		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);
		$this->assertTrue($rootView->file_exists('/' . self::TEST_FILES_SHARING_API_USER2 . '/files/' . $this->folder));

		$mountConfigManager = Server::get(IMountProviderCollection::class);
		$mounts = $mountConfigManager->getMountsForUser(Server::get(IUserManager::class)->get(self::TEST_FILES_SHARING_API_USER3));
		array_walk($mounts, [Filesystem::getMountManager(), 'addMount']);

		$this->assertTrue($rootView->file_exists('/' . self::TEST_FILES_SHARING_API_USER3 . '/files/' . $this->filename));

		// make sure we didn't double setup shares for user 2 or mounted the shares for user 3 in user's 2 home
		$this->assertFalse($rootView->file_exists('/' . self::TEST_FILES_SHARING_API_USER2 . '/files/' . $this->folder . ' (2)'));
		$this->assertFalse($rootView->file_exists('/' . self::TEST_FILES_SHARING_API_USER2 . '/files/' . $this->filename));

		//cleanup
		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);
		$this->view->unlink($this->folder);

		$this->shareManager->deleteShare($share1);
		$this->shareManager->deleteShare($share2);
	}

	public function testCopyFromStorage(): void {
		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);

		$share = $this->share(
			IShare::TYPE_USER,
			$this->folder,
			self::TEST_FILES_SHARING_API_USER1,
			self::TEST_FILES_SHARING_API_USER2,
			Constants::PERMISSION_ALL
		);

		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);
		$view = new View('/' . self::TEST_FILES_SHARING_API_USER2 . '/files');
		$this->assertTrue($view->file_exists($this->folder));

		[$sharedStorage,] = $view->resolvePath($this->folder);
		$this->assertTrue($sharedStorage->instanceOfStorage('OCA\Files_Sharing\ISharedStorage'));

		$sourceStorage = new Temporary([]);
		$sourceStorage->file_put_contents('foo.txt', 'asd');

		$sharedStorage->copyFromStorage($sourceStorage, 'foo.txt', 'bar.txt');
		$this->assertTrue($sharedStorage->file_exists('bar.txt'));
		$this->assertEquals('asd', $sharedStorage->file_get_contents('bar.txt'));

		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);
		$this->view->unlink($this->folder);
		$this->shareManager->deleteShare($share);
	}

	public function testMoveFromStorage(): void {
		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);

		$share = $this->share(
			IShare::TYPE_USER,
			$this->folder,
			self::TEST_FILES_SHARING_API_USER1,
			self::TEST_FILES_SHARING_API_USER2,
			Constants::PERMISSION_ALL
		);

		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);
		$view = new View('/' . self::TEST_FILES_SHARING_API_USER2 . '/files');
		$this->assertTrue($view->file_exists($this->folder));

		[$sharedStorage,] = $view->resolvePath($this->folder);
		$this->assertTrue($sharedStorage->instanceOfStorage('OCA\Files_Sharing\ISharedStorage'));

		$sourceStorage = new Temporary([]);
		$sourceStorage->file_put_contents('foo.txt', 'asd');
		$sourceStorage->getScanner()->scan('');

		$sharedStorage->moveFromStorage($sourceStorage, 'foo.txt', 'bar.txt');
		$this->assertTrue($sharedStorage->file_exists('bar.txt'));
		$this->assertEquals('asd', $sharedStorage->file_get_contents('bar.txt'));

		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);
		$this->view->unlink($this->folder);
		$this->shareManager->deleteShare($share);
	}

	public function testOwnerPermissions(): void {
		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);

		$share = $this->share(
			IShare::TYPE_USER,
			$this->folder,
			self::TEST_FILES_SHARING_API_USER1,
			self::TEST_FILES_SHARING_API_USER2,
			Constants::PERMISSION_ALL - Constants::PERMISSION_DELETE
		);

		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);
		$view = new View('/' . self::TEST_FILES_SHARING_API_USER2 . '/files');
		$this->assertTrue($view->file_exists($this->folder));

		$view->file_put_contents($this->folder . '/newfile.txt', 'asd');

		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);

		$this->assertTrue($this->view->file_exists($this->folder . '/newfile.txt'));
		$this->assertEquals(Constants::PERMISSION_ALL - Constants::PERMISSION_CREATE,
			$this->view->getFileInfo($this->folder . '/newfile.txt')->getPermissions());

		$this->view->unlink($this->folder);
		$this->shareManager->deleteShare($share);
	}

	public function testInitWithNonExistingUser(): void {
		$share = $this->createMock(IShare::class);
		$share->method('getShareOwner')->willReturn('unexist');
		$ownerView = $this->createMock(View::class);
		$storage = new SharedStorage([
			'ownerView' => $ownerView,
			'superShare' => $share,
			'groupedShares' => [$share],
			'user' => 'user1',
		]);

		// trigger init
		$this->assertInstanceOf(FailedStorage::class, $storage->getSourceStorage());
		$this->assertInstanceOf(FailedCache::class, $storage->getCache());
	}

	public function testInitWithNotFoundSource(): void {
		$share = $this->createMock(IShare::class);
		$share->method('getShareOwner')->willReturn(self::TEST_FILES_SHARING_API_USER1);
		$share->method('getNodeId')->willReturn(1);
		$ownerView = $this->createMock(View::class);
		$ownerView->method('getPath')->willThrowException(new NotFoundException());
		$storage = new SharedStorage([
			'ownerView' => $ownerView,
			'superShare' => $share,
			'groupedShares' => [$share],
			'user' => 'user1',
		]);

		// trigger init
		$this->assertInstanceOf(FailedStorage::class, $storage->getSourceStorage());
		$this->assertInstanceOf(FailedCache::class, $storage->getCache());
	}

	public function testCopyPermissions(): void {
		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);

		$share = $this->share(
			IShare::TYPE_USER,
			$this->filename,
			self::TEST_FILES_SHARING_API_USER1,
			self::TEST_FILES_SHARING_API_USER2,
			Constants::PERMISSION_ALL - Constants::PERMISSION_CREATE - Constants::PERMISSION_DELETE
		);

		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);
		$view = new View('/' . self::TEST_FILES_SHARING_API_USER2 . '/files');
		$this->assertTrue($view->file_exists($this->filename));

		$this->assertTrue($view->copy($this->filename, '/target.txt'));

		$this->assertTrue($view->file_exists('/target.txt'));

		$info = $view->getFileInfo('/target.txt');
		$this->assertEquals(Constants::PERMISSION_ALL - Constants::PERMISSION_CREATE, $info->getPermissions());

		$this->view->unlink($this->filename);
		$this->shareManager->deleteShare($share);
	}
}
