<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Trashbin\Tests;

use OC\Files\Filesystem;
use OC\Files\Storage\Common;
use OC\Files\Storage\Temporary;
use OC\Files\View;
use OCA\Files_Trashbin\AppInfo\Application;
use OCA\Files_Trashbin\Events\MoveToTrashEvent;
use OCA\Files_Trashbin\Storage;
use OCA\Files_Trashbin\Trash\ITrashManager;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Constants;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\Cache\ICache;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\Files\Storage\IStorage;
use OCP\IUserManager;
use OCP\Lock\ILockingProvider;
use OCP\Server;
use OCP\Share\IShare;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\Traits\MountProviderTrait;

class TemporaryNoCross extends Temporary {
	public function copyFromStorage(IStorage $sourceStorage, string $sourceInternalPath, string $targetInternalPath, ?bool $preserveMtime = null): bool {
		return Common::copyFromStorage($sourceStorage, $sourceInternalPath, $targetInternalPath, $preserveMtime);
	}

	public function moveFromStorage(IStorage $sourceStorage, string $sourceInternalPath, string $targetInternalPath): bool {
		return Common::moveFromStorage($sourceStorage, $sourceInternalPath, $targetInternalPath);
	}
}

/**
 * Class Storage
 *
 * @group DB
 *
 * @package OCA\Files_Trashbin\Tests
 */
class StorageTest extends \Test\TestCase {
	use MountProviderTrait;

	private string $user;
	private View $rootView;
	private View $userView;

	// 239 chars so appended timestamp of 12 chars will exceed max length of 250 chars
	private const LONG_FILENAME = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa.txt';
	// 250 chars
	private const MAX_FILENAME = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa.txt';

	protected function setUp(): void {
		parent::setUp();

		\OC_Hook::clear();
		\OC::$server->boot();

		// register trashbin hooks
		$trashbinApp = new Application();
		$trashbinApp->boot($this->createMock(IBootContext::class));

		$this->user = $this->getUniqueId('user');
		Server::get(IUserManager::class)->createUser($this->user, $this->user);

		// this will setup the FS
		$this->loginAsUser($this->user);

		Storage::setupStorage();

		$this->rootView = new View('/');
		$this->userView = new View('/' . $this->user . '/files/');
		$this->userView->file_put_contents('test.txt', 'foo');
		$this->userView->file_put_contents(static::LONG_FILENAME, 'foo');
		$this->userView->file_put_contents(static::MAX_FILENAME, 'foo');

		$this->userView->mkdir('folder');
		$this->userView->file_put_contents('folder/inside.txt', 'bar');
	}

	protected function tearDown(): void {
		Filesystem::getLoader()->removeStorageWrapper('oc_trashbin');
		$this->logout();
		$user = Server::get(IUserManager::class)->get($this->user);
		if ($user !== null) {
			$user->delete();
		}
		\OC_Hook::clear();
		parent::tearDown();
	}

	/**
	 * Test that deleting a file puts it into the trashbin.
	 */
	public function testSingleStorageDeleteFile(): void {
		$this->assertTrue($this->userView->file_exists('test.txt'));
		$this->userView->unlink('test.txt');
		[$storage,] = $this->userView->resolvePath('test.txt');
		$storage->getScanner()->scan(''); // make sure we check the storage
		$this->assertFalse($this->userView->getFileInfo('test.txt'));

		// check if file is in trashbin
		$results = $this->rootView->getDirectoryContent($this->user . '/files_trashbin/files/');
		$this->assertCount(1, $results);
		$name = $results[0]->getName();
		$this->assertEquals('test.txt', substr($name, 0, strrpos($name, '.')));
	}

	/**
	 * Test that deleting a folder puts it into the trashbin.
	 */
	public function testSingleStorageDeleteFolder(): void {
		$this->assertTrue($this->userView->file_exists('folder/inside.txt'));
		$this->userView->rmdir('folder');
		[$storage,] = $this->userView->resolvePath('folder/inside.txt');
		$storage->getScanner()->scan(''); // make sure we check the storage
		$this->assertFalse($this->userView->getFileInfo('folder'));

		// check if folder is in trashbin
		$results = $this->rootView->getDirectoryContent($this->user . '/files_trashbin/files/');
		$this->assertCount(1, $results);
		$name = $results[0]->getName();
		$this->assertEquals('folder', substr($name, 0, strrpos($name, '.')));

		$results = $this->rootView->getDirectoryContent($this->user . '/files_trashbin/files/' . $name . '/');
		$this->assertEquals(1, count($results));
		$name = $results[0]->getName();
		$this->assertEquals('inside.txt', $name);
	}

	/**
	 * Test that deleting a file with a long filename puts it into the trashbin.
	 */
	public function testSingleStorageDeleteLongFilename(): void {
		$truncatedFilename = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa.txt';

		$this->assertTrue($this->userView->file_exists(static::LONG_FILENAME));
		$this->userView->unlink(static::LONG_FILENAME);
		[$storage,] = $this->userView->resolvePath(static::LONG_FILENAME);
		$storage->getScanner()->scan(''); // make sure we check the storage
		$this->assertFalse($this->userView->getFileInfo(static::LONG_FILENAME));

		// check if file is in trashbin
		$results = $this->rootView->getDirectoryContent($this->user . '/files_trashbin/files/');
		$this->assertCount(1, $results);
		$name = $results[0]->getName();
		$this->assertEquals($truncatedFilename, substr($name, 0, strrpos($name, '.')));
	}

	/**
	 * Test that deleting a file with the max filename length puts it into the trashbin.
	 */
	public function testSingleStorageDeleteMaxLengthFilename(): void {
		$truncatedFilename = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa.txt';

		$this->assertTrue($this->userView->file_exists(static::MAX_FILENAME));
		$this->userView->unlink(static::MAX_FILENAME);
		[$storage,] = $this->userView->resolvePath(static::MAX_FILENAME);
		$storage->getScanner()->scan(''); // make sure we check the storage
		$this->assertFalse($this->userView->getFileInfo(static::MAX_FILENAME));

		// check if file is in trashbin
		$results = $this->rootView->getDirectoryContent($this->user . '/files_trashbin/files/');
		$this->assertCount(1, $results);
		$name = $results[0]->getName();
		$this->assertEquals($truncatedFilename, substr($name, 0, strrpos($name, '.')));
	}

	/**
	 * Test that deleting a file from another mounted storage properly
	 * lands in the trashbin. This is a cross-storage situation because
	 * the trashbin folder is in the root storage while the mounted one
	 * isn't.
	 */
	public function testCrossStorageDeleteFile(): void {
		$storage2 = new Temporary([]);
		Filesystem::mount($storage2, [], $this->user . '/files/substorage');

		$this->userView->file_put_contents('substorage/subfile.txt', 'foo');
		$storage2->getScanner()->scan('');
		$this->assertTrue($storage2->file_exists('subfile.txt'));
		$this->userView->unlink('substorage/subfile.txt');

		$storage2->getScanner()->scan('');
		$this->assertFalse($this->userView->getFileInfo('substorage/subfile.txt'));
		$this->assertFalse($storage2->file_exists('subfile.txt'));

		// check if file is in trashbin
		$results = $this->rootView->getDirectoryContent($this->user . '/files_trashbin/files');
		$this->assertCount(1, $results);
		$name = $results[0]->getName();
		$this->assertEquals('subfile.txt', substr($name, 0, strrpos($name, '.')));
	}

	/**
	 * Test that deleting a folder from another mounted storage properly
	 * lands in the trashbin. This is a cross-storage situation because
	 * the trashbin folder is in the root storage while the mounted one
	 * isn't.
	 */
	public function testCrossStorageDeleteFolder(): void {
		$storage2 = new Temporary([]);
		Filesystem::mount($storage2, [], $this->user . '/files/substorage');

		$this->userView->mkdir('substorage/folder');
		$this->userView->file_put_contents('substorage/folder/subfile.txt', 'bar');
		$storage2->getScanner()->scan('');
		$this->assertTrue($storage2->file_exists('folder/subfile.txt'));
		$this->userView->rmdir('substorage/folder');

		$storage2->getScanner()->scan('');
		$this->assertFalse($this->userView->getFileInfo('substorage/folder'));
		$this->assertFalse($storage2->file_exists('folder/subfile.txt'));

		// check if folder is in trashbin
		$results = $this->rootView->getDirectoryContent($this->user . '/files_trashbin/files');
		$this->assertCount(1, $results);
		$name = $results[0]->getName();
		$this->assertEquals('folder', substr($name, 0, strrpos($name, '.')));

		$results = $this->rootView->getDirectoryContent($this->user . '/files_trashbin/files/' . $name . '/');
		$this->assertCount(1, $results);
		$name = $results[0]->getName();
		$this->assertEquals('subfile.txt', $name);
	}

	/**
	 * Test that deleted versions properly land in the trashbin.
	 */
	public function testDeleteVersionsOfFile(): void {
		// trigger a version (multiple would not work because of the expire logic)
		$this->userView->file_put_contents('test.txt', 'v1');

		$results = $this->rootView->getDirectoryContent($this->user . '/files_versions/');
		$this->assertEquals(1, count($results));

		$this->userView->unlink('test.txt');

		// rescan trash storage
		[$rootStorage,] = $this->rootView->resolvePath($this->user . '/files_trashbin');
		$rootStorage->getScanner()->scan('');

		// check if versions are in trashbin
		$results = $this->rootView->getDirectoryContent($this->user . '/files_trashbin/versions');
		$this->assertCount(1, $results);
		$name = $results[0]->getName();
		$this->assertEquals('test.txt.v', substr($name, 0, strlen('test.txt.v')));

		// versions deleted
		$results = $this->rootView->getDirectoryContent($this->user . '/files_versions/');
		$this->assertCount(0, $results);
	}

	/**
	 * Test that deleted versions properly land in the trashbin.
	 */
	public function testDeleteVersionsOfFolder(): void {
		// trigger a version (multiple would not work because of the expire logic)
		$this->userView->file_put_contents('folder/inside.txt', 'v1');

		$results = $this->rootView->getDirectoryContent($this->user . '/files_versions/folder/');
		$this->assertCount(1, $results);

		$this->userView->rmdir('folder');

		// rescan trash storage
		[$rootStorage,] = $this->rootView->resolvePath($this->user . '/files_trashbin');
		$rootStorage->getScanner()->scan('');

		// check if versions are in trashbin
		$results = $this->rootView->getDirectoryContent($this->user . '/files_trashbin/versions');
		$this->assertCount(1, $results);
		$name = $results[0]->getName();
		$this->assertEquals('folder.d', substr($name, 0, strlen('folder.d')));

		// check if versions are in trashbin
		$results = $this->rootView->getDirectoryContent($this->user . '/files_trashbin/versions/' . $name . '/');
		$this->assertCount(1, $results);
		$name = $results[0]->getName();
		$this->assertEquals('inside.txt.v', substr($name, 0, strlen('inside.txt.v')));

		// versions deleted
		$results = $this->rootView->getDirectoryContent($this->user . '/files_versions/folder/');
		$this->assertCount(0, $results);
	}

	/**
	 * Test that deleted versions properly land in the trashbin when deleting as share recipient.
	 */
	public function testDeleteVersionsOfFileAsRecipient(): void {
		$this->userView->mkdir('share');
		// trigger a version (multiple would not work because of the expire logic)
		$this->userView->file_put_contents('share/test.txt', 'v1');
		$this->userView->file_put_contents('share/test.txt', 'v2');

		$results = $this->rootView->getDirectoryContent($this->user . '/files_versions/share/');
		$this->assertCount(1, $results);

		$recipientUser = $this->getUniqueId('recipient_');
		Server::get(IUserManager::class)->createUser($recipientUser, $recipientUser);

		$node = \OCP\Server::get(IRootFolder::class)->getUserFolder($this->user)->get('share');
		$share = Server::get(\OCP\Share\IManager::class)->newShare();
		$share->setNode($node)
			->setShareType(IShare::TYPE_USER)
			->setSharedBy($this->user)
			->setSharedWith($recipientUser)
			->setPermissions(Constants::PERMISSION_ALL);
		$share = Server::get(\OCP\Share\IManager::class)->createShare($share);
		Server::get(\OCP\Share\IManager::class)->acceptShare($share, $recipientUser);

		$this->loginAsUser($recipientUser);

		// delete as recipient
		$recipientView = new View('/' . $recipientUser . '/files');
		$recipientView->unlink('share/test.txt');

		// rescan trash storage for both users
		[$rootStorage,] = $this->rootView->resolvePath($this->user . '/files_trashbin');
		$rootStorage->getScanner()->scan('');

		// check if versions are in trashbin for both users
		$results = $this->rootView->getDirectoryContent($this->user . '/files_trashbin/versions');
		$this->assertCount(1, $results, 'Versions in owner\'s trashbin');
		$name = $results[0]->getName();
		$this->assertEquals('test.txt.v', substr($name, 0, strlen('test.txt.v')));

		$results = $this->rootView->getDirectoryContent($recipientUser . '/files_trashbin/versions');
		$this->assertCount(1, $results, 'Versions in recipient\'s trashbin');
		$name = $results[0]->getName();
		$this->assertEquals('test.txt.v', substr($name, 0, strlen('test.txt.v')));

		// versions deleted
		$results = $this->rootView->getDirectoryContent($this->user . '/files_versions/share/');
		$this->assertCount(0, $results);
	}

	/**
	 * Test that deleted versions properly land in the trashbin when deleting as share recipient.
	 */
	public function testDeleteVersionsOfFolderAsRecipient(): void {
		$this->userView->mkdir('share');
		$this->userView->mkdir('share/folder');
		// trigger a version (multiple would not work because of the expire logic)
		$this->userView->file_put_contents('share/folder/test.txt', 'v1');
		$this->userView->file_put_contents('share/folder/test.txt', 'v2');

		$results = $this->rootView->getDirectoryContent($this->user . '/files_versions/share/folder/');
		$this->assertCount(1, $results);

		$recipientUser = $this->getUniqueId('recipient_');
		Server::get(IUserManager::class)->createUser($recipientUser, $recipientUser);
		$node = \OCP\Server::get(IRootFolder::class)->getUserFolder($this->user)->get('share');
		$share = Server::get(\OCP\Share\IManager::class)->newShare();
		$share->setNode($node)
			->setShareType(IShare::TYPE_USER)
			->setSharedBy($this->user)
			->setSharedWith($recipientUser)
			->setPermissions(Constants::PERMISSION_ALL);
		$share = Server::get(\OCP\Share\IManager::class)->createShare($share);
		Server::get(\OCP\Share\IManager::class)->acceptShare($share, $recipientUser);

		$this->loginAsUser($recipientUser);

		// delete as recipient
		$recipientView = new View('/' . $recipientUser . '/files');
		$recipientView->rmdir('share/folder');

		// rescan trash storage
		[$rootStorage,] = $this->rootView->resolvePath($this->user . '/files_trashbin');
		$rootStorage->getScanner()->scan('');

		// check if versions are in trashbin for owner
		$results = $this->rootView->getDirectoryContent($this->user . '/files_trashbin/versions');
		$this->assertCount(1, $results);
		$name = $results[0]->getName();
		$this->assertEquals('folder.d', substr($name, 0, strlen('folder.d')));

		// check if file versions are in trashbin for owner
		$results = $this->rootView->getDirectoryContent($this->user . '/files_trashbin/versions/' . $name . '/');
		$this->assertCount(1, $results);
		$name = $results[0]->getName();
		$this->assertEquals('test.txt.v', substr($name, 0, strlen('test.txt.v')));

		// check if versions are in trashbin for recipient
		$results = $this->rootView->getDirectoryContent($recipientUser . '/files_trashbin/versions');
		$this->assertCount(1, $results);
		$name = $results[0]->getName();
		$this->assertEquals('folder.d', substr($name, 0, strlen('folder.d')));

		// check if file versions are in trashbin for recipient
		$results = $this->rootView->getDirectoryContent($recipientUser . '/files_trashbin/versions/' . $name . '/');
		$this->assertCount(1, $results);
		$name = $results[0]->getName();
		$this->assertEquals('test.txt.v', substr($name, 0, strlen('test.txt.v')));

		// versions deleted
		$results = $this->rootView->getDirectoryContent($recipientUser . '/files_versions/share/folder/');
		$this->assertCount(0, $results);
	}

	/**
	 * Test that versions are not auto-trashed when moving a file between
	 * storages. This is because rename() between storages would call
	 * unlink() which should NOT trigger the version deletion logic.
	 */
	public function testKeepFileAndVersionsWhenMovingFileBetweenStorages(): void {
		$storage2 = new Temporary([]);
		Filesystem::mount($storage2, [], $this->user . '/files/substorage');

		// trigger a version (multiple would not work because of the expire logic)
		$this->userView->file_put_contents('test.txt', 'v1');

		$results = $this->rootView->getDirectoryContent($this->user . '/files_trashbin/files');
		$this->assertCount(0, $results);

		$results = $this->rootView->getDirectoryContent($this->user . '/files_versions/');
		$this->assertCount(1, $results);

		// move to another storage
		$this->userView->rename('test.txt', 'substorage/test.txt');
		$this->assertTrue($this->userView->file_exists('substorage/test.txt'));

		// rescan trash storage
		[$rootStorage,] = $this->rootView->resolvePath($this->user . '/files_trashbin');
		$rootStorage->getScanner()->scan('');

		// versions were moved too
		$results = $this->rootView->getDirectoryContent($this->user . '/files_versions/substorage');
		$this->assertCount(1, $results);

		// check that nothing got trashed by the rename's unlink() call
		$results = $this->rootView->getDirectoryContent($this->user . '/files_trashbin/files');
		$this->assertCount(0, $results);

		// check that versions were moved and not trashed
		$results = $this->rootView->getDirectoryContent($this->user . '/files_trashbin/versions/');
		$this->assertCount(0, $results);
	}

	/**
	 * Test that versions are not auto-trashed when moving a file between
	 * storages. This is because rename() between storages would call
	 * unlink() which should NOT trigger the version deletion logic.
	 */
	public function testKeepFileAndVersionsWhenMovingFolderBetweenStorages(): void {
		$storage2 = new Temporary([]);
		Filesystem::mount($storage2, [], $this->user . '/files/substorage');

		// trigger a version (multiple would not work because of the expire logic)
		$this->userView->file_put_contents('folder/inside.txt', 'v1');

		$results = $this->rootView->getDirectoryContent($this->user . '/files_trashbin/files');
		$this->assertCount(0, $results);

		$results = $this->rootView->getDirectoryContent($this->user . '/files_versions/folder/');
		$this->assertCount(1, $results);

		// move to another storage
		$this->userView->rename('folder', 'substorage/folder');
		$this->assertTrue($this->userView->file_exists('substorage/folder/inside.txt'));

		// rescan trash storage
		[$rootStorage,] = $this->rootView->resolvePath($this->user . '/files_trashbin');
		$rootStorage->getScanner()->scan('');

		// versions were moved too
		$results = $this->rootView->getDirectoryContent($this->user . '/files_versions/substorage/folder/');
		$this->assertCount(1, $results);

		// check that nothing got trashed by the rename's unlink() call
		$results = $this->rootView->getDirectoryContent($this->user . '/files_trashbin/files');
		$this->assertCount(0, $results);

		// check that versions were moved and not trashed
		$results = $this->rootView->getDirectoryContent($this->user . '/files_trashbin/versions/');
		$this->assertCount(0, $results);
	}

	/**
	 * Delete should fail if the source file can't be deleted.
	 */
	public function testSingleStorageDeleteFileFail(): void {
		/**
		 * @var Temporary&MockObject $storage
		 */
		$storage = $this->getMockBuilder(\OC\Files\Storage\Temporary::class)
			->setConstructorArgs([[]])
			->onlyMethods(['rename', 'unlink', 'moveFromStorage'])
			->getMock();

		$storage->expects($this->any())
			->method('rename')
			->willReturn(false);
		$storage->expects($this->any())
			->method('moveFromStorage')
			->willReturn(false);
		$storage->expects($this->any())
			->method('unlink')
			->willReturn(false);

		$cache = $storage->getCache();

		Filesystem::mount($storage, [], '/' . $this->user);
		$storage->mkdir('files');
		$this->userView->file_put_contents('test.txt', 'foo');
		$this->assertTrue($storage->file_exists('files/test.txt'));
		$this->assertFalse($this->userView->unlink('test.txt'));
		$this->assertTrue($storage->file_exists('files/test.txt'));
		$this->assertTrue($cache->inCache('files/test.txt'));

		// file should not be in the trashbin
		$results = $this->rootView->getDirectoryContent($this->user . '/files_trashbin/files/');
		$this->assertCount(0, $results);
	}

	/**
	 * Delete should fail if the source folder can't be deleted.
	 */
	public function testSingleStorageDeleteFolderFail(): void {
		/**
		 * @var Temporary&MockObject $storage
		 */
		$storage = $this->getMockBuilder(\OC\Files\Storage\Temporary::class)
			->setConstructorArgs([[]])
			->onlyMethods(['rename', 'unlink', 'rmdir'])
			->getMock();

		$storage->expects($this->any())
			->method('rmdir')
			->willReturn(false);

		$cache = $storage->getCache();

		Filesystem::mount($storage, [], '/' . $this->user);
		$storage->mkdir('files');
		$this->userView->mkdir('folder');
		$this->userView->file_put_contents('folder/test.txt', 'foo');
		$this->assertTrue($storage->file_exists('files/folder/test.txt'));
		$this->assertFalse($this->userView->rmdir('files/folder'));
		$this->assertTrue($storage->file_exists('files/folder'));
		$this->assertTrue($storage->file_exists('files/folder/test.txt'));
		$this->assertTrue($cache->inCache('files/folder'));
		$this->assertTrue($cache->inCache('files/folder/test.txt'));

		// file should not be in the trashbin
		$results = $this->rootView->getDirectoryContent($this->user . '/files_trashbin/files/');
		$this->assertCount(0, $results);
	}

	/**
	 * @dataProvider dataTestShouldMoveToTrash
	 */
	public function testShouldMoveToTrash(string $mountPoint, string $path, bool $userExists, bool $appDisablesTrash, bool $expected): void {
		$fileID = 1;
		$cache = $this->createMock(ICache::class);
		$cache->expects($this->any())->method('getId')->willReturn($fileID);
		$tmpStorage = $this->createMock(\OC\Files\Storage\Temporary::class);
		$tmpStorage->expects($this->any())->method('getCache')->willReturn($cache);
		$userManager = $this->getMockBuilder(IUserManager::class)
			->disableOriginalConstructor()->getMock();
		$userManager->expects($this->any())
			->method('userExists')->willReturn($userExists);
		$logger = $this->getMockBuilder(LoggerInterface::class)->getMock();
		$eventDispatcher = $this->createMock(IEventDispatcher::class);
		$rootFolder = $this->createMock(IRootFolder::class);
		$userFolder = $this->createMock(Folder::class);
		$node = $this->getMockBuilder(Node::class)->disableOriginalConstructor()->getMock();
		$trashManager = $this->createMock(ITrashManager::class);
		$event = $this->getMockBuilder(MoveToTrashEvent::class)->disableOriginalConstructor()->getMock();
		$event->expects($this->any())->method('shouldMoveToTrashBin')->willReturn(!$appDisablesTrash);

		$userFolder->expects($this->any())->method('getById')->with($fileID)->willReturn([$node]);
		$rootFolder->expects($this->any())->method('getById')->with($fileID)->willReturn([$node]);
		$rootFolder->expects($this->any())->method('getUserFolder')->willReturn($userFolder);

		$storage = $this->getMockBuilder(Storage::class)
			->setConstructorArgs(
				[
					['mountPoint' => $mountPoint, 'storage' => $tmpStorage],
					$trashManager,
					$userManager,
					$logger,
					$eventDispatcher,
					$rootFolder
				]
			)
			->onlyMethods(['createMoveToTrashEvent'])
			->getMock();

		$storage->expects($this->any())->method('createMoveToTrashEvent')->with($node)
			->willReturn($event);

		$this->assertSame($expected,
			$this->invokePrivate($storage, 'shouldMoveToTrash', [$path])
		);
	}

	public static function dataTestShouldMoveToTrash(): array {
		return [
			['/schiesbn/', '/files/test.txt', true, false, true],
			['/schiesbn/', '/files/test.txt', false, false, false],
			['/schiesbn/', '/test.txt', true, false, false],
			['/schiesbn/', '/test.txt', false, false, false],
			// other apps disables the trashbin
			['/schiesbn/', '/files/test.txt', true, true, false],
			['/schiesbn/', '/files/test.txt', false, true, false],
		];
	}

	/**
	 * Test that deleting a file doesn't error when nobody is logged in
	 */
	public function testSingleStorageDeleteFileLoggedOut(): void {
		$this->logout();

		if (!$this->userView->file_exists('test.txt')) {
			$this->markTestSkipped('Skipping since the current home storage backend requires the user to logged in');
		} else {
			$this->userView->unlink('test.txt');
			$this->addToAssertionCount(1);
		}
	}

	public function testTrashbinCollision(): void {
		$this->userView->file_put_contents('test.txt', 'foo');
		$this->userView->file_put_contents('folder/test.txt', 'bar');

		$timeFactory = $this->createMock(ITimeFactory::class);
		$timeFactory->method('getTime')
			->willReturn(1000);

		$lockingProvider = Server::get(ILockingProvider::class);

		$this->overwriteService(ITimeFactory::class, $timeFactory);

		$this->userView->unlink('test.txt');

		$this->assertTrue($this->rootView->file_exists('/' . $this->user . '/files_trashbin/files/test.txt.d1000'));

		/** @var \OC\Files\Storage\Storage $trashStorage */
		[$trashStorage, $trashInternalPath] = $this->rootView->resolvePath('/' . $this->user . '/files_trashbin/files/test.txt.d1000');

		/// simulate a concurrent delete
		$trashStorage->acquireLock($trashInternalPath, ILockingProvider::LOCK_EXCLUSIVE, $lockingProvider);

		$this->userView->unlink('folder/test.txt');

		$trashStorage->releaseLock($trashInternalPath, ILockingProvider::LOCK_EXCLUSIVE, $lockingProvider);

		$this->assertTrue($this->rootView->file_exists($this->user . '/files_trashbin/files/test.txt.d1001'));

		$this->assertEquals('foo', $this->rootView->file_get_contents($this->user . '/files_trashbin/files/test.txt.d1000'));
		$this->assertEquals('bar', $this->rootView->file_get_contents($this->user . '/files_trashbin/files/test.txt.d1001'));
	}

	public function testMoveFromStoragePreserveFileId(): void {
		$this->userView->file_put_contents('test.txt', 'foo');
		$fileId = $this->userView->getFileInfo('test.txt')->getId();

		$externalStorage = new TemporaryNoCross([]);
		$externalStorage->getScanner()->scan('');
		Filesystem::mount($externalStorage, [], '/' . $this->user . '/files/storage');

		$this->assertTrue($this->userView->rename('test.txt', 'storage/test.txt'));
		$this->assertTrue($externalStorage->file_exists('test.txt'));

		$this->assertEquals($fileId, $this->userView->getFileInfo('storage/test.txt')->getId());
	}
}
