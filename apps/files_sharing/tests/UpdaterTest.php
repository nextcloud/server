<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Sharing\Tests;

use OC\Files\FileInfo;
use OC\Files\Filesystem;
use OC\Files\View;
use OCA\Files_Sharing\Helper;
use OCA\Files_Trashbin\AppInfo\Application;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\Constants;
use OCP\Share\IShare;

/**
 * Class UpdaterTest
 *
 * @group DB
 */
class UpdaterTest extends TestCase {
	public const TEST_FOLDER_NAME = '/folder_share_updater_test';

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		Helper::registerHooks();
	}

	protected function setUp(): void {
		parent::setUp();

		$this->folder = self::TEST_FOLDER_NAME;

		$this->filename = '/share-updater-test.txt';

		// save file with content
		$this->view->file_put_contents($this->filename, $this->data);
		$this->view->mkdir($this->folder);
		$this->view->file_put_contents($this->folder . '/' . $this->filename, $this->data);
	}

	protected function tearDown(): void {
		if ($this->view) {
			$this->view->unlink($this->filename);
			$this->view->deleteAll($this->folder);
		}

		parent::tearDown();
	}

	/**
	 * test deletion of a folder which contains share mount points. Share mount
	 * points should be unshared before the folder gets deleted so
	 * that the mount point doesn't end up at the trash bin
	 */
	public function testDeleteParentFolder(): void {
		$status = \OC::$server->getAppManager()->isEnabledForUser('files_trashbin');
		(new \OC_App())->enable('files_trashbin');

		// register trashbin hooks
		$trashbinApp = new Application();
		$trashbinApp->boot($this->createMock(IBootContext::class));

		$fileinfo = Filesystem::getFileInfo($this->folder);
		$this->assertTrue($fileinfo instanceof FileInfo);

		$this->share(
			IShare::TYPE_USER,
			$this->folder,
			self::TEST_FILES_SHARING_API_USER1,
			self::TEST_FILES_SHARING_API_USER2,
			Constants::PERMISSION_ALL
		);

		$this->loginHelper(self::TEST_FILES_SHARING_API_USER2);
		$view = new View('/' . self::TEST_FILES_SHARING_API_USER2 . '/files');

		// check if user2 can see the shared folder
		$this->assertTrue($view->file_exists($this->folder));

		$foldersShared = $this->shareManager->getSharesBy(self::TEST_FILES_SHARING_API_USER1, IShare::TYPE_USER);
		$this->assertCount(1, $foldersShared);

		$view->mkdir('localFolder');
		$view->file_put_contents('localFolder/localFile.txt', 'local file');

		$view->rename($this->folder, 'localFolder/' . $this->folder);

		// share mount point should now be moved to the subfolder
		$this->assertFalse($view->file_exists($this->folder));
		$this->assertTrue($view->file_exists('localFolder/' . $this->folder));

		$view->unlink('localFolder');

		$this->loginHelper(self::TEST_FILES_SHARING_API_USER2);

		// shared folder should be unshared
		$foldersShared = $this->shareManager->getSharesBy(self::TEST_FILES_SHARING_API_USER1, IShare::TYPE_USER);
		$this->assertCount(0, $foldersShared);

		// trashbin should contain the local file but not the mount point
		$rootView = new View('/' . self::TEST_FILES_SHARING_API_USER2);
		$trashContent = \OCA\Files_Trashbin\Helper::getTrashFiles('/', self::TEST_FILES_SHARING_API_USER2);
		$this->assertSame(1, count($trashContent));
		$firstElement = reset($trashContent);
		$timestamp = $firstElement['mtime'];
		$this->assertTrue($rootView->file_exists('files_trashbin/files/localFolder.d' . $timestamp . '/localFile.txt'));
		$this->assertFalse($rootView->file_exists('files_trashbin/files/localFolder.d' . $timestamp . '/' . $this->folder));

		//cleanup
		$rootView->deleteAll('files_trashin');

		if ($status === false) {
			\OC::$server->getAppManager()->disableApp('files_trashbin');
		}

		Filesystem::getLoader()->removeStorageWrapper('oc_trashbin');
	}

	public function shareFolderProvider() {
		return [
			['/'],
			['/my_shares'],
		];
	}

	/**
	 * if a file gets shared the etag for the recipients root should change
	 *
	 * @dataProvider shareFolderProvider
	 *
	 * @param string $shareFolder share folder to use
	 */
	public function testShareFile($shareFolder): void {
		$config = \OC::$server->getConfig();
		$oldShareFolder = $config->getSystemValue('share_folder');
		$config->setSystemValue('share_folder', $shareFolder);

		$this->loginHelper(self::TEST_FILES_SHARING_API_USER2);

		$beforeShareRoot = Filesystem::getFileInfo('');
		$etagBeforeShareRoot = $beforeShareRoot->getEtag();

		Filesystem::mkdir($shareFolder);

		$beforeShareDir = Filesystem::getFileInfo($shareFolder);
		$etagBeforeShareDir = $beforeShareDir->getEtag();

		$this->loginHelper(self::TEST_FILES_SHARING_API_USER1);

		$share = $this->share(
			IShare::TYPE_USER,
			$this->folder,
			self::TEST_FILES_SHARING_API_USER1,
			self::TEST_FILES_SHARING_API_USER2,
			Constants::PERMISSION_ALL
		);

		$this->loginHelper(self::TEST_FILES_SHARING_API_USER2);

		$afterShareRoot = Filesystem::getFileInfo('');
		$etagAfterShareRoot = $afterShareRoot->getEtag();

		$afterShareDir = Filesystem::getFileInfo($shareFolder);
		$etagAfterShareDir = $afterShareDir->getEtag();

		$this->assertTrue(is_string($etagBeforeShareRoot));
		$this->assertTrue(is_string($etagBeforeShareDir));
		$this->assertTrue(is_string($etagAfterShareRoot));
		$this->assertTrue(is_string($etagAfterShareDir));
		$this->assertTrue($etagBeforeShareRoot !== $etagAfterShareRoot);
		$this->assertTrue($etagBeforeShareDir !== $etagAfterShareDir);

		// cleanup
		$this->loginHelper(self::TEST_FILES_SHARING_API_USER1);
		$this->shareManager->deleteShare($share);

		$config->setSystemValue('share_folder', $oldShareFolder);
	}

	/**
	 * if a folder gets renamed all children mount points should be renamed too
	 */
	public function testRename(): void {
		$fileinfo = Filesystem::getFileInfo($this->folder);

		$share = $this->share(
			IShare::TYPE_USER,
			$this->folder,
			self::TEST_FILES_SHARING_API_USER1,
			self::TEST_FILES_SHARING_API_USER2,
			Constants::PERMISSION_ALL
		);

		$this->loginHelper(self::TEST_FILES_SHARING_API_USER2);

		// make sure that the shared folder exists
		$this->assertTrue(Filesystem::file_exists($this->folder));

		Filesystem::mkdir('oldTarget');
		Filesystem::mkdir('oldTarget/subfolder');
		Filesystem::mkdir('newTarget');

		Filesystem::rename($this->folder, 'oldTarget/subfolder/' . $this->folder);

		// re-login to make sure that the new mount points are initialized
		$this->loginHelper(self::TEST_FILES_SHARING_API_USER2);

		Filesystem::rename('/oldTarget', '/newTarget/oldTarget');

		// re-login to make sure that the new mount points are initialized
		$this->loginHelper(self::TEST_FILES_SHARING_API_USER2);

		$this->assertTrue(Filesystem::file_exists('/newTarget/oldTarget/subfolder/' . $this->folder));

		// cleanup
		$this->shareManager->deleteShare($share);
	}

	/**
	 * If a folder gets moved into shared folder, children shares should have their uid_owner and permissions adjusted
	 * user1
	 * 	|-folder1 --> shared with user2
	 * user2
	 * 	|-folder2 --> shared with user3 and moved into folder1
	 * 	  |-subfolder1 --> shared with user3
	 * 	  |-file1.txt --> shared with user3
	 * 	  |-subfolder2
	 * 	    |-file2.txt --> shared with user3
	 */
	public function testMovedIntoShareChangeOwner(): void {
		$this->markTestSkipped('Skipped because this is failing with S3 as primary as file id are change when moved.');

		// user1 creates folder1
		$viewUser1 = new View('/' . self::TEST_FILES_SHARING_API_USER1 . '/files');
		$folder1 = 'folder1';
		$viewUser1->mkdir($folder1);

		// user1 shares folder1 to user2
		$folder1Share = $this->share(
			IShare::TYPE_USER,
			$folder1,
			self::TEST_FILES_SHARING_API_USER1,
			self::TEST_FILES_SHARING_API_USER2,
			Constants::PERMISSION_READ | Constants::PERMISSION_SHARE
		);

		$this->loginHelper(self::TEST_FILES_SHARING_API_USER2);
		$viewUser2 = new View('/' . self::TEST_FILES_SHARING_API_USER2 . '/files');
		// Create user2 files
		$folder2 = 'folder2';
		$viewUser2->mkdir($folder2);
		$file1 = 'folder2/file1.txt';
		$viewUser2->touch($file1);
		$subfolder1 = 'folder2/subfolder1';
		$viewUser2->mkdir($subfolder1);
		$subfolder2 = 'folder2/subfolder2';
		$viewUser2->mkdir($subfolder2);
		$file2 = 'folder2/subfolder2/file2.txt';
		$viewUser2->touch($file2);

		// user2 shares folder2 to user3
		$folder2Share = $this->share(
			IShare::TYPE_USER,
			$folder2,
			self::TEST_FILES_SHARING_API_USER2,
			self::TEST_FILES_SHARING_API_USER3,
			Constants::PERMISSION_ALL
		);
		// user2 shares folder2/file1 to user3
		$file1Share = $this->share(
			IShare::TYPE_USER,
			$file1,
			self::TEST_FILES_SHARING_API_USER2,
			self::TEST_FILES_SHARING_API_USER3,
			Constants::PERMISSION_READ | Constants::PERMISSION_SHARE
		);
		// user2 shares subfolder1 to user3
		$subfolder1Share = $this->share(
			IShare::TYPE_USER,
			$subfolder1,
			self::TEST_FILES_SHARING_API_USER2,
			self::TEST_FILES_SHARING_API_USER3,
			Constants::PERMISSION_ALL
		);
		// user2 shares subfolder2/file2.txt to user3
		$file2Share = $this->share(
			IShare::TYPE_USER,
			$file2,
			self::TEST_FILES_SHARING_API_USER2,
			self::TEST_FILES_SHARING_API_USER3,
			Constants::PERMISSION_READ | Constants::PERMISSION_SHARE
		);

		// user2 moves folder2 into folder1
		$viewUser2->rename($folder2, $folder1 . '/' . $folder2);
		$folder2Share = $this->shareManager->getShareById($folder2Share->getFullId());
		$file1Share = $this->shareManager->getShareById($file1Share->getFullId());
		$subfolder1Share = $this->shareManager->getShareById($subfolder1Share->getFullId());
		$file2Share = $this->shareManager->getShareById($file2Share->getFullId());

		// Expect uid_owner of both shares to be user1
		$this->assertEquals(self::TEST_FILES_SHARING_API_USER1, $folder2Share->getShareOwner());
		$this->assertEquals(self::TEST_FILES_SHARING_API_USER1, $file1Share->getShareOwner());
		$this->assertEquals(self::TEST_FILES_SHARING_API_USER1, $subfolder1Share->getShareOwner());
		$this->assertEquals(self::TEST_FILES_SHARING_API_USER1, $file2Share->getShareOwner());
		// Expect permissions to be limited by the permissions of the destination share
		$this->assertEquals(Constants::PERMISSION_READ | Constants::PERMISSION_SHARE, $folder2Share->getPermissions());
		$this->assertEquals(Constants::PERMISSION_READ | Constants::PERMISSION_SHARE, $file1Share->getPermissions());
		$this->assertEquals(Constants::PERMISSION_READ | Constants::PERMISSION_SHARE, $subfolder1Share->getPermissions());
		$this->assertEquals(Constants::PERMISSION_READ | Constants::PERMISSION_SHARE, $file2Share->getPermissions());

		// user2 moves folder2 out of folder1
		$viewUser2->rename($folder1 . '/' . $folder2, $folder2);
		$folder2Share = $this->shareManager->getShareById($folder2Share->getFullId());
		$file1Share = $this->shareManager->getShareById($file1Share->getFullId());
		$subfolder1Share = $this->shareManager->getShareById($subfolder1Share->getFullId());
		$file2Share = $this->shareManager->getShareById($file2Share->getFullId());

		// Expect uid_owner of both shares to be user2
		$this->assertEquals(self::TEST_FILES_SHARING_API_USER2, $folder2Share->getShareOwner());
		$this->assertEquals(self::TEST_FILES_SHARING_API_USER2, $file1Share->getShareOwner());
		$this->assertEquals(self::TEST_FILES_SHARING_API_USER2, $subfolder1Share->getShareOwner());
		$this->assertEquals(self::TEST_FILES_SHARING_API_USER2, $file2Share->getShareOwner());
		// Expect permissions to not change
		$this->assertEquals(Constants::PERMISSION_READ | Constants::PERMISSION_SHARE, $folder2Share->getPermissions());
		$this->assertEquals(Constants::PERMISSION_READ | Constants::PERMISSION_SHARE, $file1Share->getPermissions());
		$this->assertEquals(Constants::PERMISSION_READ | Constants::PERMISSION_SHARE, $subfolder1Share->getPermissions());
		$this->assertEquals(Constants::PERMISSION_READ | Constants::PERMISSION_SHARE, $file2Share->getPermissions());

		// cleanup
		$this->shareManager->deleteShare($folder1Share);
		$this->shareManager->deleteShare($folder2Share);
		$this->shareManager->deleteShare($file1Share);
		$this->shareManager->deleteShare($subfolder1Share);
		$this->shareManager->deleteShare($file2Share);
	}
}
