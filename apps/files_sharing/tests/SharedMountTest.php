<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Sharing\Tests;

use OC\Files\Filesystem;
use OC\Files\View;
use OC\Memcache\ArrayCache;
use OCA\Files_Sharing\MountProvider;
use OCA\Files_Sharing\SharedMount;
use OCP\Constants;
use OCP\ICacheFactory;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\IUserManager;
use OCP\Server;
use OCP\Share\IShare;

/**
 * Class SharedMountTest
 */
#[\PHPUnit\Framework\Attributes\Group(name: 'SLOWDB')]
class SharedMountTest extends TestCase {

	/** @var IGroupManager */
	private $groupManager;

	/** @var IUserManager */
	private $userManager;

	private $folder2;

	protected function setUp(): void {
		parent::setUp();

		$this->folder = '/folder_share_storage_test';
		$this->folder2 = '/folder_share_storage_test2';

		$this->filename = '/share-api-storage.txt';


		$this->view->mkdir($this->folder);
		$this->view->mkdir($this->folder2);

		// save file with content
		$this->view->file_put_contents($this->filename, 'root file');
		$this->view->file_put_contents($this->folder . $this->filename, 'file in subfolder');
		$this->view->file_put_contents($this->folder2 . $this->filename, 'file in subfolder2');

		$this->groupManager = Server::get(IGroupManager::class);
		$this->userManager = Server::get(IUserManager::class);
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

		parent::tearDown();
	}

	/**
	 * test if the mount point moves up if the parent folder no longer exists
	 */
	public function testShareMountLoseParentFolder(): void {

		// share to user
		$share = $this->share(
			IShare::TYPE_USER,
			$this->folder,
			self::TEST_FILES_SHARING_API_USER1,
			self::TEST_FILES_SHARING_API_USER2,
			Constants::PERMISSION_ALL);
		$this->shareManager->acceptShare($share, self::TEST_FILES_SHARING_API_USER2);

		$share->setTarget('/foo/bar' . $this->folder);
		$this->shareManager->moveShare($share, self::TEST_FILES_SHARING_API_USER2);

		$share = $this->shareManager->getShareById($share->getFullId());
		$this->assertSame('/foo/bar' . $this->folder, $share->getTarget());

		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);
		// share should have moved up

		$share = $this->shareManager->getShareById($share->getFullId());
		$this->assertSame($this->folder, $share->getTarget());

		//cleanup
		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);
		$this->shareManager->deleteShare($share);
		$this->view->unlink($this->folder);
	}

	public function testDeleteParentOfMountPoint(): void {
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

	public function testMoveSharedFile(): void {
		$share = $this->share(
			IShare::TYPE_USER,
			$this->filename,
			self::TEST_FILES_SHARING_API_USER1,
			self::TEST_FILES_SHARING_API_USER2,
			Constants::PERMISSION_READ | Constants::PERMISSION_UPDATE | Constants::PERMISSION_SHARE
		);

		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);

		Filesystem::rename($this->filename, $this->filename . '_renamed');

		$this->assertTrue(Filesystem::file_exists($this->filename . '_renamed'));
		$this->assertFalse(Filesystem::file_exists($this->filename));

		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);
		$this->assertTrue(Filesystem::file_exists($this->filename));
		$this->assertFalse(Filesystem::file_exists($this->filename . '_renamed'));

		// rename back to original name
		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);
		Filesystem::rename($this->filename . '_renamed', $this->filename);
		$this->assertFalse(Filesystem::file_exists($this->filename . '_renamed'));
		$this->assertTrue(Filesystem::file_exists($this->filename));

		//cleanup
		$this->shareManager->deleteShare($share);
	}

	/**
	 * share file with a group if a user renames the file the filename should not change
	 * for the other users
	 */
	public function testMoveGroupShare(): void {
		$testGroup = $this->groupManager->createGroup('testGroup');
		$user1 = $this->userManager->get(self::TEST_FILES_SHARING_API_USER1);
		$user2 = $this->userManager->get(self::TEST_FILES_SHARING_API_USER2);
		$user3 = $this->userManager->get(self::TEST_FILES_SHARING_API_USER3);
		$testGroup->addUser($user1);
		$testGroup->addUser($user2);
		$testGroup->addUser($user3);

		$fileinfo = $this->view->getFileInfo($this->filename);
		$share = $this->share(
			IShare::TYPE_GROUP,
			$this->filename,
			self::TEST_FILES_SHARING_API_USER1,
			'testGroup',
			Constants::PERMISSION_READ | Constants::PERMISSION_UPDATE | Constants::PERMISSION_SHARE
		);
		$this->shareManager->acceptShare($share, $user1->getUID());
		$this->shareManager->acceptShare($share, $user2->getUID());
		$this->shareManager->acceptShare($share, $user3->getUID());

		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);

		$this->assertTrue(Filesystem::file_exists($this->filename));

		Filesystem::rename($this->filename, 'newFileName');

		$this->assertTrue(Filesystem::file_exists('newFileName'));
		$this->assertFalse(Filesystem::file_exists($this->filename));

		self::loginHelper(self::TEST_FILES_SHARING_API_USER3);
		$this->assertTrue(Filesystem::file_exists($this->filename));
		$this->assertFalse(Filesystem::file_exists('newFileName'));

		self::loginHelper(self::TEST_FILES_SHARING_API_USER3);
		$this->assertTrue(Filesystem::file_exists($this->filename));
		$this->assertFalse(Filesystem::file_exists('newFileName'));

		//cleanup
		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);
		$this->shareManager->deleteShare($share);
		$testGroup->removeUser($user1);
		$testGroup->removeUser($user2);
		$testGroup->removeUser($user3);
	}

	/**
	 * @param string $path
	 * @param string $expectedResult
	 * @param bool $exception if a exception is expected
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataProviderTestStripUserFilesPath')]
	public function testStripUserFilesPath($path, $expectedResult, $exception): void {
		$testClass = new DummyTestClassSharedMount(null, null);
		try {
			$result = $testClass->stripUserFilesPathDummy($path);
			$this->assertSame($expectedResult, $result);
		} catch (\Exception $e) {
			if ($exception) {
				$this->assertSame(10, $e->getCode());
			} else {
				$this->assertTrue(false, 'Exception caught, but expected: ' . $expectedResult);
			}
		}
	}

	public static function dataProviderTestStripUserFilesPath() {
		return [
			['/user/files/foo.txt', '/foo.txt', false],
			['/user/files/folder/foo.txt', '/folder/foo.txt', false],
			['/data/user/files/foo.txt', null, true],
			['/data/user/files/', null, true],
			['/files/foo.txt', null, true],
			['/foo.txt', null, true],
		];
	}

	/**
	 * If the permissions on a group share are upgraded be sure to still respect
	 * removed shares by a member of that group
	 */
	public function testPermissionUpgradeOnUserDeletedGroupShare(): void {
		$testGroup = $this->groupManager->createGroup('testGroup');
		$user1 = $this->userManager->get(self::TEST_FILES_SHARING_API_USER1);
		$user2 = $this->userManager->get(self::TEST_FILES_SHARING_API_USER2);
		$user3 = $this->userManager->get(self::TEST_FILES_SHARING_API_USER3);
		$testGroup->addUser($user1);
		$testGroup->addUser($user2);
		$testGroup->addUser($user3);

		$connection = Server::get(IDBConnection::class);

		// Share item with group
		$fileinfo = $this->view->getFileInfo($this->folder);
		$share = $this->share(
			IShare::TYPE_GROUP,
			$this->folder,
			self::TEST_FILES_SHARING_API_USER1,
			'testGroup',
			Constants::PERMISSION_READ
		);
		$this->shareManager->acceptShare($share, $user1->getUID());
		$this->shareManager->acceptShare($share, $user2->getUID());
		$this->shareManager->acceptShare($share, $user3->getUID());

		// Login as user 2 and verify the item exists
		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);
		$this->assertTrue(Filesystem::file_exists($this->folder));
		$result = $this->shareManager->getShareById($share->getFullId(), self::TEST_FILES_SHARING_API_USER2);
		$this->assertNotEmpty($result);
		$this->assertEquals(Constants::PERMISSION_READ, $result->getPermissions());

		// Delete the share
		$this->assertTrue(Filesystem::rmdir($this->folder));
		$this->assertFalse(Filesystem::file_exists($this->folder));

		// Verify we do not get a share
		$result = $this->shareManager->getShareById($share->getFullId(), self::TEST_FILES_SHARING_API_USER2);
		$this->assertEquals(0, $result->getPermissions());

		// Login as user 1 again and change permissions
		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);
		$share->setPermissions(Constants::PERMISSION_ALL);
		$share = $this->shareManager->updateShare($share);

		// Login as user 2 and verify
		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);
		$this->assertFalse(Filesystem::file_exists($this->folder));
		$result = $this->shareManager->getShareById($share->getFullId(), self::TEST_FILES_SHARING_API_USER2);
		$this->assertEquals(0, $result->getPermissions());

		$this->shareManager->deleteShare($share);

		//cleanup
		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);
		$testGroup->removeUser($user1);
		$testGroup->removeUser($user2);
		$testGroup->removeUser($user3);
	}

	/**
	 * test if the mount point gets renamed if a folder exists at the target
	 */
	public function testShareMountOverFolder(): void {
		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);
		$this->view2->mkdir('bar');

		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);

		// share to user
		$share = $this->share(
			IShare::TYPE_USER,
			$this->folder,
			self::TEST_FILES_SHARING_API_USER1,
			self::TEST_FILES_SHARING_API_USER2,
			Constants::PERMISSION_ALL);
		$this->shareManager->acceptShare($share, self::TEST_FILES_SHARING_API_USER2);

		$share->setTarget('/bar');
		$this->shareManager->moveShare($share, self::TEST_FILES_SHARING_API_USER2);

		$share = $this->shareManager->getShareById($share->getFullId());

		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);
		// share should have been moved

		$share = $this->shareManager->getShareById($share->getFullId());
		$this->assertSame('/bar (2)', $share->getTarget());

		//cleanup
		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);
		$this->shareManager->deleteShare($share);
		$this->view->unlink($this->folder);
	}

	/**
	 * test if the mount point gets renamed if another share exists at the target
	 */
	public function testShareMountOverShare(): void {
		// create a shared cache
		$caches = [];
		$cacheFactory = $this->createMock(ICacheFactory::class);
		$cacheFactory->method('createLocal')
			->willReturnCallback(function (string $prefix) use (&$caches) {
				if (!isset($caches[$prefix])) {
					$caches[$prefix] = new ArrayCache($prefix);
				}
				return $caches[$prefix];
			});
		$cacheFactory->method('createDistributed')
			->willReturnCallback(function (string $prefix) use (&$caches) {
				if (!isset($caches[$prefix])) {
					$caches[$prefix] = new ArrayCache($prefix);
				}
				return $caches[$prefix];
			});

		// hack to overwrite the cache factory, we can't use the proper "overwriteService" since the mount provider is created before this test is called
		$mountProvider = Server::get(MountProvider::class);
		$reflectionClass = new \ReflectionClass($mountProvider);
		$reflectionCacheFactory = $reflectionClass->getProperty('cacheFactory');
		$reflectionCacheFactory->setValue($mountProvider, $cacheFactory);

		// share to user
		$share = $this->share(
			IShare::TYPE_USER,
			$this->folder,
			self::TEST_FILES_SHARING_API_USER1,
			self::TEST_FILES_SHARING_API_USER2,
			Constants::PERMISSION_ALL);
		$this->shareManager->acceptShare($share, self::TEST_FILES_SHARING_API_USER2);

		$share->setTarget('/foobar');
		$this->shareManager->moveShare($share, self::TEST_FILES_SHARING_API_USER2);


		// share to user
		$share2 = $this->share(
			IShare::TYPE_USER,
			$this->folder2,
			self::TEST_FILES_SHARING_API_USER1,
			self::TEST_FILES_SHARING_API_USER2,
			Constants::PERMISSION_ALL);
		$this->shareManager->acceptShare($share2, self::TEST_FILES_SHARING_API_USER2);

		$share2->setTarget('/foobar');
		$this->shareManager->moveShare($share2, self::TEST_FILES_SHARING_API_USER2);

		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);
		// one of the shares should have been moved

		$share = $this->shareManager->getShareById($share->getFullId());
		$share2 = $this->shareManager->getShareById($share2->getFullId());

		// we don't know or care which share got the "(2)" just that one of them did
		$this->assertNotEquals($share->getTarget(), $share2->getTarget());
		$this->assertSame('/foobar', min($share->getTarget(), $share2->getTarget()));
		$this->assertSame('/foobar (2)', max($share->getTarget(), $share2->getTarget()));

		//cleanup
		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);
		$this->shareManager->deleteShare($share);
		$this->view->unlink($this->folder);
	}
}

class DummyTestClassSharedMount extends SharedMount {
	public function __construct($storage, $mountpoint, $arguments = null, $loader = null) {
		// noop
	}

	public function stripUserFilesPathDummy($path) {
		return $this->stripUserFilesPath($path);
	}
}
