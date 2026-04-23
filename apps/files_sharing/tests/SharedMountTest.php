<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Sharing\Tests;

use OC\Files\Filesystem;
use OCA\Files_Sharing\SharedMount;
use OCA\Files_Sharing\SharedStorage;
use OCP\Constants;
use OCP\DB\QueryBuilder\IQueryBuilder;
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
	private IGroupManager $groupManager;
	private IUserManager $userManager;

	private string $folder2;

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
	 * Simulate the ownCloud migration scenario: a TYPE_GROUP share whose `accepted`
	 * column was set to STATUS_ACCEPTED by the SetAcceptedStatus repair step, but
	 * which has no per-user USERGROUP subshare (OC never created them).
	 *
	 * On first rename, DefaultShareProvider::move() must create the USERGROUP subshare
	 * with accepted = STATUS_ACCEPTED so MountProvider does not skip it on the next
	 * login, causing the file to appear to vanish.
	 */
	public function testMoveOwncloudMigratedGroupShareRemainsVisibleAfterRename(): void {
		$testGroup = $this->groupManager->createGroup('testGroupOC');
		$user1 = $this->userManager->get(self::TEST_FILES_SHARING_API_USER1);
		$user2 = $this->userManager->get(self::TEST_FILES_SHARING_API_USER2);
		$testGroup->addUser($user1);
		$testGroup->addUser($user2);

		// Create a group share without going through the NC acceptance flow,
		// then directly set accepted = STATUS_ACCEPTED on the GROUP row to mimic
		// the state left by SetAcceptedStatus after an OC→NC migration.
		$share = $this->share(
			IShare::TYPE_GROUP,
			$this->filename,
			self::TEST_FILES_SHARING_API_USER1,
			'testGroupOC',
			Constants::PERMISSION_READ | Constants::PERMISSION_UPDATE | Constants::PERMISSION_SHARE
		);

		$db = Server::get(IDBConnection::class);

		// Remove any USERGROUP subshares the acceptance flow may have created,
		// then stamp the GROUP row as STATUS_ACCEPTED — this is the OC migration state.
		$db->getQueryBuilder()
			->delete('share')
			->where($db->getQueryBuilder()->expr()->eq('share_type', $db->getQueryBuilder()->createNamedParameter(IShare::TYPE_USERGROUP, IQueryBuilder::PARAM_INT)))
			->andWhere($db->getQueryBuilder()->expr()->eq('parent', $db->getQueryBuilder()->createNamedParameter((int)$share->getId(), IQueryBuilder::PARAM_INT)))
			->executeStatement();

		$db->getQueryBuilder()
			->update('share')
			->set('accepted', $db->getQueryBuilder()->createNamedParameter(IShare::STATUS_ACCEPTED, IQueryBuilder::PARAM_INT))
			->where($db->getQueryBuilder()->expr()->eq('id', $db->getQueryBuilder()->createNamedParameter((int)$share->getId(), IQueryBuilder::PARAM_INT)))
			->executeStatement();

		// Log in as the recipient; the share should be visible via the GROUP row.
		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);
		$this->assertTrue(Filesystem::file_exists($this->filename), 'Share should be visible before rename');

		// Rename — this triggers move() which must create the USERGROUP subshare
		// with accepted = STATUS_ACCEPTED (not the default STATUS_PENDING).
		$renamed = Filesystem::rename($this->filename, $this->filename . '_oc_renamed');
		$this->assertTrue($renamed, 'Rename should succeed');
		$this->assertTrue(Filesystem::file_exists($this->filename . '_oc_renamed'));

		// Re-login to flush the mount cache and force MountProvider to rebuild from DB.
		// If the USERGROUP subshare was created with accepted = STATUS_PENDING, the
		// share would be skipped here and the file would appear to vanish.
		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);
		$this->assertTrue(
			Filesystem::file_exists($this->filename . '_oc_renamed'),
			'Share must still be visible after re-login — USERGROUP subshare must have accepted = STATUS_ACCEPTED'
		);

		// Cleanup
		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);
		$this->shareManager->deleteShare($share);
		$testGroup->removeUser($user1);
		$testGroup->removeUser($user2);
		$this->groupManager->get('testGroupOC')?->delete();
	}

	/**
	 * When updateFileTarget() throws — e.g. Manager::moveShare() rejects an
	 * OC-migrated group share because the original group no longer exists —
	 * moveMount() must return false so View::rename() propagates the failure
	 * cleanly rather than silently corrupting the virtual filesystem state.
	 */
	public function testMoveMountReturnsFalseWhenUpdateFileTargetThrows(): void {
		$storage = $this->createMock(SharedStorage::class);
		$storage->method('getShare')->willReturn($this->createMock(IShare::class));

		$mount = new SharedMountWithFailingUpdate($storage);

		$result = $mount->moveMount('/' . self::TEST_FILES_SHARING_API_USER2 . '/files/newname');

		$this->assertFalse($result);
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
}

class DummyTestClassSharedMount extends SharedMount {
	public function __construct($storage, $mountpoint, $arguments = null, $loader = null) {
		// noop
	}

	public function stripUserFilesPathDummy($path) {
		return $this->stripUserFilesPath($path);
	}
}

/**
 * SharedMount subclass whose updateFileTarget() always throws, used to verify
 * that moveMount() returns false and does not silently corrupt VFS state.
 */
class SharedMountWithFailingUpdate extends SharedMount {
	public function __construct(SharedStorage $storage) {
		$this->storage = $storage;
		// skip parent constructor — only moveMount() is under test
	}

	protected function updateFileTarget($newPath, &$share): void {
		throw new \Exception('Simulated failure: group no longer exists');
	}
}
