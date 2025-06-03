<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
use OC\AllConfig;
use OC\AppFramework\Bootstrap\BootContext;
use OC\AppFramework\DependencyInjection\DIContainer;
use OC\Files\Cache\Watcher;
use OC\Files\Filesystem;
use OC\Files\Storage\Local;
use OC\Files\View;
use OC\SystemConfig;
use OC\User\Database;
use OCA\Files_Sharing\AppInfo\Application;
use OCA\Files_Trashbin\AppInfo\Application as TrashbinApplication;
use OCA\Files_Trashbin\Expiration;
use OCA\Files_Trashbin\Helper;
use OCA\Files_Trashbin\Trashbin;
use OCP\App\IAppManager;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Constants;
use OCP\Files\FileInfo;
use OCP\Files\IRootFolder;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IUserManager;
use OCP\Server;
use OCP\Share\IShare;

/**
 * Class Test_Encryption
 *
 * @group DB
 */
class TrashbinTest extends \Test\TestCase {
	public const TEST_TRASHBIN_USER1 = 'test-trashbin-user1';
	public const TEST_TRASHBIN_USER2 = 'test-trashbin-user2';

	private $trashRoot1;
	private $trashRoot2;

	private static $rememberRetentionObligation;
	private static bool $trashBinStatus;
	private View $rootView;

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		$appManager = Server::get(IAppManager::class);
		self::$trashBinStatus = $appManager->isEnabledForUser('files_trashbin');

		// reset backend
		Server::get(IUserManager::class)->clearBackends();
		Server::get(IUserManager::class)->registerBackend(new Database());

		// clear share hooks
		\OC_Hook::clear('OCP\\Share');
		\OC::registerShareHooks(Server::get(SystemConfig::class));

		// init files sharing
		new Application();

		//disable encryption
		Server::get(IAppManager::class)->disableApp('encryption');

		$config = Server::get(IConfig::class);
		//configure trashbin
		self::$rememberRetentionObligation = (string)$config->getSystemValue('trashbin_retention_obligation', Expiration::DEFAULT_RETENTION_OBLIGATION);
		/** @var Expiration $expiration */
		$expiration = Server::get(Expiration::class);
		$expiration->setRetentionObligation('auto, 2');

		// register trashbin hooks
		$trashbinApp = new TrashbinApplication();
		$trashbinApp->boot(new BootContext(new DIContainer('', [], \OC::$server)));

		// create test user
		self::loginHelper(self::TEST_TRASHBIN_USER2, true);
		self::loginHelper(self::TEST_TRASHBIN_USER1, true);
	}


	public static function tearDownAfterClass(): void {
		// cleanup test user
		$user = Server::get(IUserManager::class)->get(self::TEST_TRASHBIN_USER1);
		if ($user !== null) {
			$user->delete();
		}

		/** @var Expiration $expiration */
		$expiration = Server::get(Expiration::class);
		$expiration->setRetentionObligation(self::$rememberRetentionObligation);

		\OC_Hook::clear();

		Filesystem::getLoader()->removeStorageWrapper('oc_trashbin');

		if (self::$trashBinStatus) {
			Server::get(IAppManager::class)->enableApp('files_trashbin');
		}

		parent::tearDownAfterClass();
	}

	protected function setUp(): void {
		parent::setUp();

		Server::get(IAppManager::class)->enableApp('files_trashbin');
		$config = Server::get(IConfig::class);
		$mockConfig = $this->getMockBuilder(AllConfig::class)
			->onlyMethods(['getSystemValue'])
			->setConstructorArgs([Server::get(SystemConfig::class)])
			->getMock();
		$mockConfig->expects($this->any())
			->method('getSystemValue')
			->willReturnCallback(static function ($key, $default) use ($config) {
				if ($key === 'filesystem_check_changes') {
					return Watcher::CHECK_ONCE;
				} else {
					return $config->getSystemValue($key, $default);
				}
			});
		$this->overwriteService(AllConfig::class, $mockConfig);

		$this->trashRoot1 = '/' . self::TEST_TRASHBIN_USER1 . '/files_trashbin';
		$this->trashRoot2 = '/' . self::TEST_TRASHBIN_USER2 . '/files_trashbin';
		$this->rootView = new View();
		self::loginHelper(self::TEST_TRASHBIN_USER1);
	}

	protected function tearDown(): void {
		$this->restoreService(AllConfig::class);
		// disable trashbin to be able to properly clean up
		Server::get(IAppManager::class)->disableApp('files_trashbin');

		$this->rootView->deleteAll('/' . self::TEST_TRASHBIN_USER1 . '/files');
		$this->rootView->deleteAll('/' . self::TEST_TRASHBIN_USER2 . '/files');
		$this->rootView->deleteAll($this->trashRoot1);
		$this->rootView->deleteAll($this->trashRoot2);

		// clear trash table
		$connection = Server::get(IDBConnection::class);
		$connection->executeUpdate('DELETE FROM `*PREFIX*files_trash`');

		parent::tearDown();
	}

	/**
	 * test expiration of files older then the max storage time defined for the trash
	 */
	public function testExpireOldFiles(): void {

		/** @var ITimeFactory $time */
		$time = Server::get(ITimeFactory::class);
		$currentTime = $time->getTime();
		$expireAt = $currentTime - 2 * 24 * 60 * 60;
		$expiredDate = $currentTime - 3 * 24 * 60 * 60;

		// create some files
		Filesystem::file_put_contents('file1.txt', 'file1');
		Filesystem::file_put_contents('file2.txt', 'file2');
		Filesystem::file_put_contents('file3.txt', 'file3');

		// delete them so that they end up in the trash bin
		Filesystem::unlink('file1.txt');
		Filesystem::unlink('file2.txt');
		Filesystem::unlink('file3.txt');

		//make sure that files are in the trash bin
		$filesInTrash = Helper::getTrashFiles('/', self::TEST_TRASHBIN_USER1, 'name');
		$this->assertSame(3, count($filesInTrash));

		// every second file will get a date in the past so that it will get expired
		$manipulatedList = $this->manipulateDeleteTime($filesInTrash, $this->trashRoot1, $expiredDate);

		$testClass = new TrashbinForTesting();
		[$sizeOfDeletedFiles, $count] = $testClass->dummyDeleteExpiredFiles($manipulatedList, $expireAt);

		$this->assertSame(10, $sizeOfDeletedFiles);
		$this->assertSame(2, $count);

		// only file2.txt should be left
		$remainingFiles = array_slice($manipulatedList, $count);
		$this->assertCount(1, $remainingFiles);
		$remainingFile = reset($remainingFiles);
		// TODO: failing test
		#$this->assertSame('file2.txt', $remainingFile['name']);

		// check that file1.txt and file3.txt was really deleted
		$newTrashContent = Helper::getTrashFiles('/', self::TEST_TRASHBIN_USER1);
		$this->assertCount(1, $newTrashContent);
		$element = reset($newTrashContent);
		// TODO: failing test
		#$this->assertSame('file2.txt', $element['name']);
	}

	/**
	 * test expiration of files older then the max storage time defined for the trash
	 * in this test we delete a shared file and check if both trash bins, the one from
	 * the owner of the file and the one from the user who deleted the file get expired
	 * correctly
	 */
	public function testExpireOldFilesShared(): void {
		$currentTime = time();
		$folder = 'trashTest-' . $currentTime . '/';
		$expiredDate = $currentTime - 3 * 24 * 60 * 60;

		// create some files
		Filesystem::mkdir($folder);
		Filesystem::file_put_contents($folder . 'user1-1.txt', 'file1');
		Filesystem::file_put_contents($folder . 'user1-2.txt', 'file2');
		Filesystem::file_put_contents($folder . 'user1-3.txt', 'file3');
		Filesystem::file_put_contents($folder . 'user1-4.txt', 'file4');

		//share user1-4.txt with user2
		$node = \OC::$server->getUserFolder(self::TEST_TRASHBIN_USER1)->get($folder);
		$share = Server::get(\OCP\Share\IManager::class)->newShare();
		$share->setShareType(IShare::TYPE_USER)
			->setNode($node)
			->setSharedBy(self::TEST_TRASHBIN_USER1)
			->setSharedWith(self::TEST_TRASHBIN_USER2)
			->setPermissions(Constants::PERMISSION_ALL);
		$share = Server::get(\OCP\Share\IManager::class)->createShare($share);
		Server::get(\OCP\Share\IManager::class)->acceptShare($share, self::TEST_TRASHBIN_USER2);

		// delete them so that they end up in the trash bin
		Filesystem::unlink($folder . 'user1-1.txt');
		Filesystem::unlink($folder . 'user1-2.txt');
		Filesystem::unlink($folder . 'user1-3.txt');

		$filesInTrash = Helper::getTrashFiles('/', self::TEST_TRASHBIN_USER1, 'name');
		$this->assertSame(3, count($filesInTrash));

		// every second file will get a date in the past so that it will get expired
		$this->manipulateDeleteTime($filesInTrash, $this->trashRoot1, $expiredDate);

		// login as user2
		self::loginHelper(self::TEST_TRASHBIN_USER2);

		$this->assertTrue(Filesystem::file_exists($folder . 'user1-4.txt'));

		// create some files
		Filesystem::file_put_contents('user2-1.txt', 'file1');
		Filesystem::file_put_contents('user2-2.txt', 'file2');

		// delete them so that they end up in the trash bin
		Filesystem::unlink('user2-1.txt');
		Filesystem::unlink('user2-2.txt');

		$filesInTrashUser2 = Helper::getTrashFiles('/', self::TEST_TRASHBIN_USER2, 'name');
		$this->assertSame(2, count($filesInTrashUser2));

		// every second file will get a date in the past so that it will get expired
		$this->manipulateDeleteTime($filesInTrashUser2, $this->trashRoot2, $expiredDate);

		Filesystem::unlink($folder . 'user1-4.txt');

		$this->runCommands();

		$filesInTrashUser2AfterDelete = Helper::getTrashFiles('/', self::TEST_TRASHBIN_USER2);

		// user2-1.txt should have been expired
		$this->verifyArray($filesInTrashUser2AfterDelete, ['user2-2.txt', 'user1-4.txt']);

		self::loginHelper(self::TEST_TRASHBIN_USER1);

		// user1-1.txt and user1-3.txt should have been expired
		$filesInTrashUser1AfterDelete = Helper::getTrashFiles('/', self::TEST_TRASHBIN_USER1);

		$this->verifyArray($filesInTrashUser1AfterDelete, ['user1-2.txt', 'user1-4.txt']);
	}

	/**
	 * verify that the array contains the expected results
	 *
	 * @param FileInfo[] $result
	 * @param string[] $expected
	 */
	private function verifyArray(array $result, array $expected): void {
		$this->assertCount(count($expected), $result);
		foreach ($expected as $expectedFile) {
			$found = false;
			foreach ($result as $fileInTrash) {
				if ($expectedFile === $fileInTrash['name']) {
					$found = true;
					break;
				}
			}
			if (!$found) {
				// if we didn't found the expected file, something went wrong
				$this->assertTrue(false, "can't find expected file '" . $expectedFile . "' in trash bin");
			}
		}
	}

	/**
	 * @param FileInfo[] $files
	 */
	private function manipulateDeleteTime(array $files, string $trashRoot, int $expireDate): array {
		$counter = 0;
		foreach ($files as &$file) {
			// modify every second file
			$counter = ($counter + 1) % 2;
			if ($counter === 1) {
				$source = $trashRoot . '/files/' . $file['name'] . '.d' . $file['mtime'];
				$target = Filesystem::normalizePath($trashRoot . '/files/' . $file['name'] . '.d' . $expireDate);
				$this->rootView->rename($source, $target);
				$file['mtime'] = $expireDate;
			}
		}
		return \OCA\Files\Helper::sortFiles($files, 'mtime');
	}


	/**
	 * test expiration of old files in the trash bin until the max size
	 * of the trash bin is met again
	 */
	public function testExpireOldFilesUtilLimitsAreMet(): void {

		// create some files
		Filesystem::file_put_contents('file1.txt', 'file1');
		Filesystem::file_put_contents('file2.txt', 'file2');
		Filesystem::file_put_contents('file3.txt', 'file3');

		// delete them so that they end up in the trash bin
		Filesystem::unlink('file3.txt');
		sleep(1); // make sure that every file has a unique mtime
		Filesystem::unlink('file2.txt');
		sleep(1); // make sure that every file has a unique mtime
		Filesystem::unlink('file1.txt');

		//make sure that files are in the trash bin
		$filesInTrash = Helper::getTrashFiles('/', self::TEST_TRASHBIN_USER1, 'mtime');
		$this->assertSame(3, count($filesInTrash));

		$testClass = new TrashbinForTesting();
		$sizeOfDeletedFiles = $testClass->dummyDeleteFiles($filesInTrash, -8);

		// the two oldest files (file3.txt and file2.txt) should be deleted
		$this->assertSame(10, $sizeOfDeletedFiles);

		$newTrashContent = Helper::getTrashFiles('/', self::TEST_TRASHBIN_USER1);
		$this->assertSame(1, count($newTrashContent));
		$element = reset($newTrashContent);
		$this->assertSame('file1.txt', $element['name']);
	}

	/**
	 * Test restoring a file
	 */
	public function testRestoreFileInRoot(): void {
		$userFolder = \OCP\Server::get(IRootFolder::class)->getUserFolder(self::TEST_TRASHBIN_USER1);
		$file = $userFolder->newFile('file1.txt');
		$file->putContent('foo');

		$this->assertTrue($userFolder->nodeExists('file1.txt'));

		$file->delete();

		$this->assertFalse($userFolder->nodeExists('file1.txt'));

		$filesInTrash = Helper::getTrashFiles('/', self::TEST_TRASHBIN_USER1, 'mtime');
		$this->assertCount(1, $filesInTrash);

		/** @var FileInfo */
		$trashedFile = $filesInTrash[0];

		$this->assertTrue(
			Trashbin::restore(
				'file1.txt.d' . $trashedFile->getMtime(),
				$trashedFile->getName(),
				$trashedFile->getMtime()
			)
		);

		$file = $userFolder->get('file1.txt');
		$this->assertEquals('foo', $file->getContent());
	}

	/**
	 * Test restoring a file in subfolder
	 */
	public function testRestoreFileInSubfolder(): void {
		$userFolder = \OCP\Server::get(IRootFolder::class)->getUserFolder(self::TEST_TRASHBIN_USER1);
		$folder = $userFolder->newFolder('folder');
		$file = $folder->newFile('file1.txt');
		$file->putContent('foo');

		$this->assertTrue($userFolder->nodeExists('folder/file1.txt'));

		$file->delete();

		$this->assertFalse($userFolder->nodeExists('folder/file1.txt'));

		$filesInTrash = Helper::getTrashFiles('/', self::TEST_TRASHBIN_USER1, 'mtime');
		$this->assertCount(1, $filesInTrash);

		/** @var FileInfo */
		$trashedFile = $filesInTrash[0];

		$this->assertTrue(
			Trashbin::restore(
				'file1.txt.d' . $trashedFile->getMtime(),
				$trashedFile->getName(),
				$trashedFile->getMtime()
			)
		);

		$file = $userFolder->get('folder/file1.txt');
		$this->assertEquals('foo', $file->getContent());
	}

	/**
	 * Test restoring a folder
	 */
	public function testRestoreFolder(): void {
		$userFolder = \OCP\Server::get(IRootFolder::class)->getUserFolder(self::TEST_TRASHBIN_USER1);
		$folder = $userFolder->newFolder('folder');
		$file = $folder->newFile('file1.txt');
		$file->putContent('foo');

		$this->assertTrue($userFolder->nodeExists('folder'));

		$folder->delete();

		$this->assertFalse($userFolder->nodeExists('folder'));

		$filesInTrash = Helper::getTrashFiles('/', self::TEST_TRASHBIN_USER1, 'mtime');
		$this->assertCount(1, $filesInTrash);

		/** @var FileInfo */
		$trashedFolder = $filesInTrash[0];

		$this->assertTrue(
			Trashbin::restore(
				'folder.d' . $trashedFolder->getMtime(),
				$trashedFolder->getName(),
				$trashedFolder->getMtime()
			)
		);

		$file = $userFolder->get('folder/file1.txt');
		$this->assertEquals('foo', $file->getContent());
	}

	/**
	 * Test restoring a file from inside a trashed folder
	 */
	public function testRestoreFileFromTrashedSubfolder(): void {
		$userFolder = \OCP\Server::get(IRootFolder::class)->getUserFolder(self::TEST_TRASHBIN_USER1);
		$folder = $userFolder->newFolder('folder');
		$file = $folder->newFile('file1.txt');
		$file->putContent('foo');

		$this->assertTrue($userFolder->nodeExists('folder'));

		$folder->delete();

		$this->assertFalse($userFolder->nodeExists('folder'));

		$filesInTrash = Helper::getTrashFiles('/', self::TEST_TRASHBIN_USER1, 'mtime');
		$this->assertCount(1, $filesInTrash);

		/** @var FileInfo */
		$trashedFile = $filesInTrash[0];

		$this->assertTrue(
			Trashbin::restore(
				'folder.d' . $trashedFile->getMtime() . '/file1.txt',
				'file1.txt',
				$trashedFile->getMtime()
			)
		);

		$file = $userFolder->get('file1.txt');
		$this->assertEquals('foo', $file->getContent());
	}

	/**
	 * Test restoring a file whenever the source folder was removed.
	 * The file should then land in the root.
	 */
	public function testRestoreFileWithMissingSourceFolder(): void {
		$userFolder = \OCP\Server::get(IRootFolder::class)->getUserFolder(self::TEST_TRASHBIN_USER1);
		$folder = $userFolder->newFolder('folder');
		$file = $folder->newFile('file1.txt');
		$file->putContent('foo');

		$this->assertTrue($userFolder->nodeExists('folder/file1.txt'));

		$file->delete();

		$this->assertFalse($userFolder->nodeExists('folder/file1.txt'));

		$filesInTrash = Helper::getTrashFiles('/', self::TEST_TRASHBIN_USER1, 'mtime');
		$this->assertCount(1, $filesInTrash);

		/** @var FileInfo */
		$trashedFile = $filesInTrash[0];

		// delete source folder
		$folder->delete();

		$this->assertTrue(
			Trashbin::restore(
				'file1.txt.d' . $trashedFile->getMtime(),
				$trashedFile->getName(),
				$trashedFile->getMtime()
			)
		);

		$file = $userFolder->get('file1.txt');
		$this->assertEquals('foo', $file->getContent());
	}

	/**
	 * Test restoring a file in the root folder whenever there is another file
	 * with the same name in the root folder
	 */
	public function testRestoreFileDoesNotOverwriteExistingInRoot(): void {
		$userFolder = \OCP\Server::get(IRootFolder::class)->getUserFolder(self::TEST_TRASHBIN_USER1);
		$file = $userFolder->newFile('file1.txt');
		$file->putContent('foo');

		$this->assertTrue($userFolder->nodeExists('file1.txt'));

		$file->delete();

		$this->assertFalse($userFolder->nodeExists('file1.txt'));

		$filesInTrash = Helper::getTrashFiles('/', self::TEST_TRASHBIN_USER1, 'mtime');
		$this->assertCount(1, $filesInTrash);

		/** @var FileInfo */
		$trashedFile = $filesInTrash[0];

		// create another file
		$file = $userFolder->newFile('file1.txt');
		$file->putContent('bar');

		$this->assertTrue(
			Trashbin::restore(
				'file1.txt.d' . $trashedFile->getMtime(),
				$trashedFile->getName(),
				$trashedFile->getMtime()
			)
		);

		$anotherFile = $userFolder->get('file1.txt');
		$this->assertEquals('bar', $anotherFile->getContent());

		$restoredFile = $userFolder->get('file1 (restored).txt');
		$this->assertEquals('foo', $restoredFile->getContent());
	}

	/**
	 * Test restoring a file whenever there is another file
	 * with the same name in the source folder
	 */
	public function testRestoreFileDoesNotOverwriteExistingInSubfolder(): void {
		$userFolder = \OCP\Server::get(IRootFolder::class)->getUserFolder(self::TEST_TRASHBIN_USER1);
		$folder = $userFolder->newFolder('folder');
		$file = $folder->newFile('file1.txt');
		$file->putContent('foo');

		$this->assertTrue($userFolder->nodeExists('folder/file1.txt'));

		$file->delete();

		$this->assertFalse($userFolder->nodeExists('folder/file1.txt'));

		$filesInTrash = Helper::getTrashFiles('/', self::TEST_TRASHBIN_USER1, 'mtime');
		$this->assertCount(1, $filesInTrash);

		/** @var FileInfo */
		$trashedFile = $filesInTrash[0];

		// create another file
		$file = $folder->newFile('file1.txt');
		$file->putContent('bar');

		$this->assertTrue(
			Trashbin::restore(
				'file1.txt.d' . $trashedFile->getMtime(),
				$trashedFile->getName(),
				$trashedFile->getMtime()
			)
		);

		$anotherFile = $userFolder->get('folder/file1.txt');
		$this->assertEquals('bar', $anotherFile->getContent());

		$restoredFile = $userFolder->get('folder/file1 (restored).txt');
		$this->assertEquals('foo', $restoredFile->getContent());
	}

	/**
	 * Test restoring a non-existing file from trashbin, returns false
	 */
	public function testRestoreUnexistingFile(): void {
		$this->assertFalse(
			Trashbin::restore(
				'unexist.txt.d123456',
				'unexist.txt',
				'123456'
			)
		);
	}

	/**
	 * Test restoring a file into a read-only folder, will restore
	 * the file to root instead
	 */
	public function testRestoreFileIntoReadOnlySourceFolder(): void {
		$userFolder = \OCP\Server::get(IRootFolder::class)->getUserFolder(self::TEST_TRASHBIN_USER1);
		$folder = $userFolder->newFolder('folder');
		$file = $folder->newFile('file1.txt');
		$file->putContent('foo');

		$this->assertTrue($userFolder->nodeExists('folder/file1.txt'));

		$file->delete();

		$this->assertFalse($userFolder->nodeExists('folder/file1.txt'));

		$filesInTrash = Helper::getTrashFiles('/', self::TEST_TRASHBIN_USER1, 'mtime');
		$this->assertCount(1, $filesInTrash);

		/** @var FileInfo */
		$trashedFile = $filesInTrash[0];

		// delete source folder
		[$storage, $internalPath] = $this->rootView->resolvePath('/' . self::TEST_TRASHBIN_USER1 . '/files/folder');
		if ($storage instanceof Local) {
			$folderAbsPath = $storage->getSourcePath($internalPath);
			// make folder read-only
			chmod($folderAbsPath, 0555);

			$this->assertTrue(
				Trashbin::restore(
					'file1.txt.d' . $trashedFile->getMtime(),
					$trashedFile->getName(),
					$trashedFile->getMtime()
				)
			);

			$file = $userFolder->get('file1.txt');
			$this->assertEquals('foo', $file->getContent());

			chmod($folderAbsPath, 0755);
		}
	}

	/**
	 * @param string $user
	 * @param bool $create
	 */
	public static function loginHelper($user, $create = false) {
		if ($create) {
			try {
				Server::get(IUserManager::class)->createUser($user, $user);
			} catch (\Exception $e) { // catch username is already being used from previous aborted runs
			}
		}

		\OC_Util::tearDownFS();
		\OC_User::setUserId('');
		Filesystem::tearDown();
		\OC_User::setUserId($user);
		\OC_Util::setupFS($user);
		\OCP\Server::get(IRootFolder::class)->getUserFolder($user);
	}
}


// just a dummy class to make protected methods available for testing
class TrashbinForTesting extends Trashbin {

	/**
	 * @param FileInfo[] $files
	 * @param integer $limit
	 */
	public function dummyDeleteExpiredFiles($files) {
		// dummy value for $retention_obligation because it is not needed here
		return parent::deleteExpiredFiles($files, TrashbinTest::TEST_TRASHBIN_USER1);
	}

	/**
	 * @param FileInfo[] $files
	 * @param integer $availableSpace
	 */
	public function dummyDeleteFiles($files, $availableSpace) {
		return parent::deleteFiles($files, TrashbinTest::TEST_TRASHBIN_USER1, $availableSpace);
	}
}
