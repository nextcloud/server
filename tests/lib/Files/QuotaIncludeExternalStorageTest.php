<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace Test\Files;

use OC\Files\Cache\FileAccess;
use OC\Files\Config\MountProviderCollection;
use OC\Files\Filesystem;
use OC\Files\Mount\HomeMountPoint;
use OC\Files\SetupManager;
use OC\Files\Storage\Home;
use OC\Files\Storage\StorageFactory;
use OC\Files\Storage\Temporary;
use OC\Files\Storage\Wrapper\Quota;
use OC\Files\View;
use OC\Share20\ShareDisableChecker;
use OCP\App\IAppManager;
use OCP\Diagnostics\IEventLogger;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\Config\IUserMountCache;
use OCP\Files\Mount\IMountManager;
use OCP\Files\NotFoundException;
use OCP\Files\Storage\IStorageFactory;
use OCP\IAppConfig;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\ITempManager;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Lockdown\ILockdownManager;
use OCP\Server;
use Psr\Log\LoggerInterface;
use Test\TestCase;
use Test\Traits\UserTrait;

/**
 * Tests the usage measured for a quota when `quota_include_external_storage`
 * is enabled and the files belong to another user than the one of the session.
 */
#[\PHPUnit\Framework\Attributes\Group('DB')]
class QuotaIncludeExternalStorageTest extends TestCase {
	use UserTrait;

	private IUser $owner;
	private IUser $otherUser;
	private mixed $previousIncludeExternalStorage;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$ownerUid = $this->getUniqueID('quota_owner_');
		$otherUid = $this->getUniqueID('quota_other_');
		$this->createUser($ownerUid, $ownerUid);
		$this->createUser($otherUid, $otherUid);

		$userManager = Server::get(IUserManager::class);
		$this->owner = $userManager->get($ownerUid);
		$this->otherUser = $userManager->get($otherUid);

		$this->previousIncludeExternalStorage = Server::get(IConfig::class)->getSystemValue('quota_include_external_storage', null);

		Filesystem::tearDown();
		\OC_User::setUserId($otherUid);
		Filesystem::init($otherUid, '/' . $otherUid . '/files');

		// Complete the setup for both users so that the lazy setup triggered
		// while looking up mounts keeps the mounts installed by the tests.
		$setupManager = Server::get(SetupManager::class);
		$setupManager->setupForUser($this->owner);
		$setupManager->setupForUser($this->otherUser);

		$mountManager = Server::get(IMountManager::class);
		$mountManager->removeMount('/' . $ownerUid);
		$mountManager->removeMount('/' . $otherUid);
	}

	#[\Override]
	protected function tearDown(): void {
		$config = Server::get(IConfig::class);
		if ($this->previousIncludeExternalStorage === null) {
			$config->deleteSystemValue('quota_include_external_storage');
		} else {
			$config->setSystemValue('quota_include_external_storage', $this->previousIncludeExternalStorage);
		}
		\OC_Helper::reset();

		Filesystem::tearDown();
		\OC_User::setUserId('');

		parent::tearDown();
	}

	/**
	 * Mounts a scanned temporary storage holding a single file of $size bytes.
	 */
	private function mountStorageWithUsage(string $mountPoint, int $size): Temporary {
		$storage = new Temporary([]);
		$storage->file_put_contents('usage.bin', str_repeat('x', $size));
		$storage->getScanner()->scan('');
		Filesystem::mount($storage, [], $mountPoint);
		return $storage;
	}

	private function wrapInQuota(Temporary $storage, int $quota, string $mountPoint): Quota {
		$wrapped = new Quota([
			'storage' => $storage,
			'quota' => $quota,
			'include_external_storage' => true,
			'user' => $this->owner,
		]);
		Filesystem::mount($wrapped, [], $mountPoint);
		return $wrapped;
	}

	/**
	 * Builds a setup manager that registers its storage wrappers in
	 * $storageFactory with `quota_include_external_storage` enabled.
	 */
	private function createSetupManager(IStorageFactory $storageFactory): SetupManager {
		$config = $this->createMock(IConfig::class);
		$config->method('getSystemValue')
			->willReturnCallback(fn (string $key, mixed $default = '') => $key === 'quota_include_external_storage' ? true : $default);
		$config->method('getSystemValueBool')
			->willReturnCallback(fn (string $key, bool $default = false) => $default);

		$cacheFactory = $this->createMock(ICacheFactory::class);
		$cacheFactory->method('createDistributed')->willReturn($this->createMock(ICache::class));

		$mountManager = $this->createMock(IMountManager::class);
		$mountManager->method('getAll')->willReturn([]);

		$mountProviderCollection = $this->createMock(MountProviderCollection::class);
		$mountProviderCollection->method('getRootMounts')->willReturn([]);

		return new SetupManager(
			$this->createMock(IEventLogger::class),
			$mountProviderCollection,
			$mountManager,
			$this->createMock(IUserManager::class),
			$this->createMock(IEventDispatcher::class),
			$this->createMock(IUserMountCache::class),
			$this->createMock(ILockdownManager::class),
			$this->createMock(IUserSession::class),
			$cacheFactory,
			$this->createMock(LoggerInterface::class),
			$config,
			$this->createMock(ShareDisableChecker::class),
			$this->createMock(IAppManager::class),
			$this->createMock(FileAccess::class),
			$this->createMock(IAppConfig::class),
			$storageFactory,
		);
	}

	/**
	 * Writing into a share is accounted against the quota of the share owner,
	 * so the usage has to be read from the owner's home and not from the home
	 * of the session user performing the write.
	 */
	public function testFreeSpaceMeasuresStorageOwnerAndNotSessionUser(): void {
		$ownerRoot = '/' . $this->owner->getUID() . '/files';
		$ownerStorage = $this->mountStorageWithUsage($ownerRoot, 5);
		$quotaStorage = $this->wrapInQuota($ownerStorage, 100, $ownerRoot);

		$this->mountStorageWithUsage('/' . $this->otherUser->getUID() . '/files', 90);

		$this->assertEquals(95, $quotaStorage->free_space(''));
	}

	/**
	 * The session user being over the owner's quota must not make the share
	 * read-only for them.
	 */
	public function testFreeSpaceIsNotExhaustedBySessionUserUsage(): void {
		$ownerRoot = '/' . $this->owner->getUID() . '/files';
		$ownerStorage = $this->mountStorageWithUsage($ownerRoot, 5);
		$quotaStorage = $this->wrapInQuota($ownerStorage, 100, $ownerRoot);

		$this->mountStorageWithUsage('/' . $this->otherUser->getUID() . '/files', 150);

		$this->assertEquals(95, $quotaStorage->free_space(''));
	}

	/**
	 * External storages mounted in the owner's files count towards the owner's
	 * quota, which is what `quota_include_external_storage` enables.
	 */
	public function testFreeSpaceIncludesStorageMountedInOwnerHome(): void {
		$ownerRoot = '/' . $this->owner->getUID() . '/files';
		$ownerStorage = $this->mountStorageWithUsage($ownerRoot, 5);
		$quotaStorage = $this->wrapInQuota($ownerStorage, 100, $ownerRoot);

		$this->mountStorageWithUsage($ownerRoot . '/external', 20);

		$this->assertEquals(75, $quotaStorage->free_space(''));
	}

	/**
	 * Without a known user the wrapper reads the session's view, the only one it
	 * can resolve.
	 */
	public function testFreeSpaceFallsBackToSessionViewWithoutUser(): void {
		$otherRoot = '/' . $this->otherUser->getUID() . '/files';
		$storage = $this->mountStorageWithUsage($otherRoot, 30);

		$quotaStorage = new Quota([
			'storage' => $storage,
			'quota' => 100,
			'include_external_storage' => true,
		]);
		Filesystem::mount($quotaStorage, [], $otherRoot);

		$this->assertEquals(70, $quotaStorage->free_space(''));
	}

	/**
	 * The quota wrapper the setup manager installs on a home mount point has to
	 * measure the user of that home, which is what makes writes into shares of
	 * other users use the right usage.
	 */
	public function testSetupManagerWrapsHomeWithQuotaOfHomeUser(): void {
		$ownerUid = $this->owner->getUID();
		$ownerStorage = $this->mountStorageWithUsage('/' . $ownerUid . '/files', 5);
		$this->mountStorageWithUsage('/' . $this->otherUser->getUID() . '/files', 90);
		$this->owner->setQuota('100 B');

		$storageFactory = new StorageFactory();
		$this->createSetupManager($storageFactory)->setupRoot();
		$wrapped = $storageFactory->wrap(new HomeMountPoint($this->owner, $ownerStorage, '/' . $ownerUid), $ownerStorage);

		$this->assertTrue($wrapped->instanceOfStorage(Quota::class));
		$this->assertEquals(95, $wrapped->free_space('files'));
	}

	/**
	 * The storage info of another user's home reports the quota of that user,
	 * so the usage it reports has to be the one of that user as well.
	 */
	public function testStorageInfoOfOtherUsersHomeReportsUsageOfHomeUser(): void {
		$homeUid = $this->getUniqueID('quota_home_');
		$homeUser = $this->createMock(IUser::class);
		$homeUser->method('getUID')->willReturn($homeUid);
		$homeUser->method('getHome')->willReturn(Server::get(ITempManager::class)->getTemporaryFolder());
		$homeUser->method('getQuotaBytes')->willReturn(100);
		$homeUser->method('getDisplayName')->willReturn('Home user');

		$home = new Home(['user' => $homeUser]);
		$home->mkdir('files');
		$home->file_put_contents('files/usage.bin', str_repeat('x', 5));
		$home->getScanner()->scan('');
		Filesystem::mount($home, [], '/' . $homeUid);

		$this->mountStorageWithUsage('/' . $this->otherUser->getUID() . '/files', 90);

		Server::get(IConfig::class)->setSystemValue('quota_include_external_storage', true);
		\OC_Helper::reset();

		$rootInfo = (new View('/' . $homeUid . '/files'))->getFileInfo('');
		$storageInfo = \OC_Helper::getStorageInfo('', $rootInfo, true, false);

		$this->assertSame($homeUid, $storageInfo['owner']);
		$this->assertEquals(5, $storageInfo['used']);
		$this->assertEquals(95, $storageInfo['free']);
	}

	/**
	 * When a user's files root is not their home storage, e.g. an external storage
	 * mounted at `/`, the storage info of that root reports that user's quota and
	 * usage, not the ones of the session user asking for it.
	 */
	public function testStorageInfoOfNonHomeFilesRootReportsQuotaOfItsUser(): void {
		$ownerUid = $this->owner->getUID();
		$this->mountStorageWithUsage('/' . $ownerUid . '/files', 5);
		$this->mountStorageWithUsage('/' . $this->otherUser->getUID() . '/files', 90);
		$this->owner->setQuota('100 B');
		$this->otherUser->setQuota('1000 B');

		Server::get(IConfig::class)->setSystemValue('quota_include_external_storage', true);
		\OC_Helper::reset();

		$rootInfo = (new View('/' . $ownerUid . '/files'))->getFileInfo('');
		$storageInfo = \OC_Helper::getStorageInfo('', $rootInfo, true, false);

		$this->assertSame($ownerUid, $storageInfo['owner']);
		$this->assertEquals(100, $storageInfo['quota']);
		$this->assertEquals(5, $storageInfo['used']);
		$this->assertEquals(95, $storageInfo['free']);
	}

	/**
	 * A home storage whose user has no files root in the filesystem has no usage
	 * that could be reported.
	 */
	public function testStorageInfoOfHomeWithoutFilesRootThrows(): void {
		$homeUid = $this->getUniqueID('quota_home_');
		$homeUser = $this->createMock(IUser::class);
		$homeUser->method('getUID')->willReturn($homeUid);
		$homeUser->method('getHome')->willReturn(Server::get(ITempManager::class)->getTemporaryFolder());
		$homeUser->method('getQuotaBytes')->willReturn(100);

		$home = new Home(['user' => $homeUser]);
		$home->mkdir('files');
		$home->getScanner()->scan('');
		$mountPoint = '/' . $this->getUniqueID('quota_elsewhere_');
		Filesystem::mount($home, [], $mountPoint);

		Server::get(IConfig::class)->setSystemValue('quota_include_external_storage', true);
		\OC_Helper::reset();

		$rootInfo = (new View($mountPoint . '/files'))->getFileInfo('');

		$this->expectException(NotFoundException::class);
		\OC_Helper::getStorageInfo('', $rootInfo, true, false);
	}
}
