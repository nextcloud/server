<?php

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files;

use OC\Files\Cache\CacheEntry;
use OC\Files\Cache\FileAccess;
use OC\Files\Config\MountProviderCollection;
use OC\Files\SetupManager;
use OC\Share20\ShareDisableChecker;
use OCP\App\IAppManager;
use OCP\Diagnostics\IEventLogger;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\Config\ICachedMountInfo;
use OCP\Files\Config\IMountProvider;
use OCP\Files\Config\IMountProviderArgs;
use OCP\Files\Config\IPartialMountProvider;
use OCP\Files\Config\IUserMountCache;
use OCP\Files\Mount\IMountManager;
use OCP\Files\Mount\IMountPoint;
use OCP\Files\Storage\IStorageFactory;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Lockdown\ILockdownManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Rule\InvokedCount;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class SetupManagerTest extends TestCase {

	/**
	 * @var (object&\PHPUnit\Framework\MockObject\MockObject)|IUserManager|(IUserManager&object&\PHPUnit\Framework\MockObject\MockObject)|(IUserManager&\PHPUnit\Framework\MockObject\MockObject)|\PHPUnit\Framework\MockObject\MockObject
	 */
	private IUserManager&MockObject $userManager;
	private IUserMountCache&MockObject $userMountCache;
	private ICache&MockObject $cache;
	private FileAccess&MockObject $fileAccess;
	private MountProviderCollection&MockObject $mountProviderCollection;
	private IMountManager&MockObject $mountManager;
	private SetupManager $setupManager;
	private IUser&MockObject $user;
	private string $userId;
	private string $path;
	private string $mountPoint;

	protected function setUp(): void {
		$eventLogger = $this->createMock(IEventLogger::class);
		$eventLogger->method('start');
		$eventLogger->method('end');

		$this->userManager = $this->createMock(IUserManager::class);
		$this->cache = $this->createMock(ICache::class);

		$this->userId = 'alice';
		$this->path = "/{$this->userId}/files/folder";
		$this->mountPoint = "{$this->path}/";

		$this->user = $this->createMock(IUser::class);
		$this->user->method('getUID')->willReturn($this->userId);
		$this->userManager->method('get')
			->with($this->userId)
			->willReturn($this->user);

		// avoid triggering full setup required check
		$this->cache->method('get')
			->with($this->userId)
			->willReturn(true);

		$this->mountProviderCollection = $this->createMock(MountProviderCollection::class);
		$this->mountManager = $this->createMock(IMountManager::class);
		$eventDispatcher = $this->createMock(IEventDispatcher::class);
		$eventDispatcher->method('addListener');
		$this->userMountCache = $this->createMock(IUserMountCache::class);
		$lockdownManager = $this->createMock(ILockdownManager::class);
		$userSession = $this->createMock(IUserSession::class);
		$cacheFactory = $this->createMock(ICacheFactory::class);
		$cacheFactory->expects($this->once())
			->method('createDistributed')
			->with('setupmanager::')
			->willReturn($this->cache);
		$logger = $this->createMock(LoggerInterface::class);
		$config = $this->createMock(IConfig::class);
		$config->method('getSystemValueBool')->willReturn(false);
		$shareDisableChecker = $this->createMock(ShareDisableChecker::class);
		$appManager = $this->createMock(IAppManager::class);
		$this->fileAccess = $this->createMock(FileAccess::class);

		$lockdownManager->method('canAccessFilesystem')->willReturn(true);

		$this->setupManager = new SetupManager(
			$eventLogger,
			$this->mountProviderCollection,
			$this->mountManager,
			$this->userManager,
			$eventDispatcher,
			$this->userMountCache,
			$lockdownManager,
			$userSession,
			$cacheFactory,
			$logger,
			$config,
			$shareDisableChecker,
			$appManager,
			$this->fileAccess,
		);
	}

	public function testTearDown(): void {
		$this->setupManager->tearDown();
	}

	/**
	 * Tests that a path is not set up twice for providers implementing
	 * IPartialMountProvider in setupForPath.
	 */
	public function testSetupForPathWithPartialProviderSkipsAlreadySetupPath(): void {
		$cachedMount = $this->getCachedMountInfo($this->mountPoint, 42);

		$this->userMountCache->expects($this->exactly(2))
			->method('getMountForPath')
			->with($this->user, $this->path)
			->willReturn($cachedMount);
		$this->userMountCache->expects($this->never())->method('registerMounts');
		$this->userMountCache->expects($this->never())->method('getMountsInPath');

		$this->fileAccess->expects($this->once())
			->method('getByFileId')
			->with(42)
			->willReturn($this->createMock(CacheEntry::class));

		$partialMount = $this->createMock(IMountPoint::class);

		$this->mountProviderCollection->expects($this->once())
			->method('getUserMountsFromProviderByPath')
			->with(
				SetupManagerTestPartialMountProvider::class,
				$this->path,
				false,
				$this->callback(function (array $args) use ($cachedMount) {
					$this->assertCount(1, $args);
					$this->assertInstanceOf(IMountProviderArgs::class, $args[0]);
					$this->assertSame($cachedMount, $args[0]->mountInfo);
					return true;
				})
			)
			->willReturn([$partialMount]);

		$homeMount = $this->createMock(IMountPoint::class);

		$this->mountProviderCollection->expects($this->once())
			->method('getHomeMountForUser')
			->willReturn($homeMount);

		$this->mountProviderCollection->expects($this->never())
			->method('getUserMountsForProviderClasses');

		$invokedCount = $this->exactly(2);
		$addMountExpectations = [
			1 => $homeMount,
			2 => $partialMount,
		];
		$this->mountManager->expects($invokedCount)
			->method('addMount')
			->willReturnCallback($this->getAddMountCheckCallback($invokedCount, $addMountExpectations));

		// setup called twice, provider should only be called once
		$this->setupManager->setupForPath($this->path, false);
		$this->setupManager->setupForPath($this->path, false);
	}

	/**
	 * Tests that providers that are not implementing IPartialMountProvider are
	 * not set up more than once by setupForPath.
	 */
	public function testSetupForPathWithNonPartialProviderSkipsAlreadySetupProvider(): void {
		$cachedMount = $this->getCachedMountInfo($this->mountPoint, 42,
			IMountProvider::class);

		$this->userMountCache->expects($this->exactly(2))
			->method('getMountForPath')
			->with($this->user, $this->path)
			->willReturn($cachedMount);

		$this->userMountCache->expects($this->once())->method('registerMounts');
		$this->userMountCache->expects($this->never())->method('getMountsInPath');

		$providerMount = $this->createMock(IMountPoint::class);

		$this->mountProviderCollection->expects($this->once())
			->method('getUserMountsForProviderClasses')
			->with($this->user, [IMountProvider::class])
			->willReturn([$providerMount]);

		$homeMount = $this->createMock(IMountPoint::class);

		$this->mountProviderCollection->expects($this->once())
			->method('getHomeMountForUser')
			->willReturn($homeMount);

		$invokedCount = $this->exactly(2);
		$addMountExpectations = [
			1 => $homeMount,
			2 => $providerMount,
		];
		$this->mountManager->expects($invokedCount)
			->method('addMount')
			->willReturnCallback($this->getAddMountCheckCallback($invokedCount, $addMountExpectations));

		// setup called twice, provider should only be called once
		$this->setupManager->setupForPath($this->path, false);
		$this->setupManager->setupForPath($this->path, false);
	}

	/**
	 * Tests that setupForPath does not instantiate already set up providers
	 * when called for the same path first with $withChildren set to true
	 * and then set to false.
	 */
	public function testSetupForPathWithChildrenAndNonPartialProviderSkipsAlreadySetupProvider(): void {
		$cachedMount = $this->getCachedMountInfo($this->mountPoint, 42, IMountProvider::class);
		$additionalCachedMount = $this->getCachedMountInfo($this->mountPoint . 'additional/', 43, SetupManagerTestFullMountProvider::class);

		$this->userMountCache->expects($this->exactly(2))
			->method('getMountForPath')
			->with($this->user, $this->path)
			->willReturn($cachedMount);

		$this->userMountCache->expects($this->once())->method('registerMounts');
		$this->userMountCache->expects($this->once())->method('getMountsInPath')
			->willReturn([$additionalCachedMount]);

		$mount = $this->createMock(IMountPoint::class);
		$additionalMount = $this->createMock(IMountPoint::class);

		$invokedCount = $this->exactly(2);
		$this->mountProviderCollection->expects($invokedCount)
			->method('getUserMountsForProviderClasses')
			->willReturnCallback(function (IUser $userArg, array $providersArg) use (
				$additionalMount,
				$mount,
				$invokedCount) {
				if ($invokedCount->numberOfInvocations() === 1) {
					$providers = [IMountProvider::class];
					$returnMounts = [$mount];
				} else {
					$providers = [SetupManagerTestFullMountProvider::class];
					$returnMounts = [$additionalMount];
				}

				$this->assertSame($this->user, $userArg);
				$this->assertSame($providersArg, $providers);

				return $returnMounts;
			});

		$homeMount = $this->createMock(IMountPoint::class);

		$this->mountProviderCollection->expects($this->once())
			->method('getHomeMountForUser')
			->willReturn($homeMount);

		$invokedCount = $this->exactly(3);
		$addMountExpectations = [
			1 => $homeMount,
			2 => $mount,
			3 => $additionalMount,
		];
		$this->mountManager->expects($invokedCount)
			->method('addMount')
			->willReturnCallback($this->getAddMountCheckCallback($invokedCount, $addMountExpectations));

		// setup called twice, provider should only be called once
		$this->setupManager->setupForPath($this->path, true);
		$this->setupManager->setupForPath($this->path, false);
	}

	/**
	 * Tests that setupForPath does not set up child mounts again if a parent
	 * was set up with $withChildren set to true.
	 */
	public function testSetupForPathWithChildrenAndPartialProviderSkipsIfParentAlreadySetup():	void {
		$childPath = "{$this->path}/child";
		$childMountPoint = "{$childPath}/";

		$cachedMount = $this->getCachedMountInfo($this->mountPoint, 42);
		$cachedChildMount = $this->getCachedMountInfo($childMountPoint, 43);

		$invokedCount = $this->exactly(3);
		$this->userMountCache->expects($invokedCount)
			->method('getMountForPath')
			->willReturnCallback(function (IUser $userArg, string $pathArg) use (
				$cachedChildMount,
				$cachedMount,
				$childPath,
				$invokedCount) {
				if ($invokedCount->numberOfInvocations() === 1) {
					$expectedPath = $this->path;
					$returnMount = $cachedMount;
				} else {
					$expectedPath = $childPath;
					$returnMount = $cachedChildMount;
				}

				$this->assertSame($this->user, $userArg);
				$this->assertSame($expectedPath, $pathArg);

				return $returnMount;
			});

		$this->userMountCache->expects($this->never())->method('registerMounts');
		$this->userMountCache->expects($this->exactly(2))
			->method('getMountsInPath')
			->willReturn([$cachedChildMount]);

		$this->fileAccess->expects($this->once())
			->method('getByFileId')
			->with(42)
			->willReturn($this->createMock(CacheEntry::class));

		$this->fileAccess->expects($this->once())
			->method('getByFileIds')
			->with([43])
			->willReturn([43 => $this->createMock(CacheEntry::class)]);

		$partialMount = $this->createMock(IMountPoint::class);
		$partialChildMount = $this->createMock(IMountPoint::class);

		$invokedCount = $this->exactly(2);
		$this->mountProviderCollection->expects($invokedCount)
			->method('getUserMountsFromProviderByPath')
			->willReturnCallback(function (
				string $providerClass,
				string $pathArg,
				bool $forChildren,
				array $mountProviderArgs,
			) use (
				$cachedChildMount,
				$partialMount,
				$partialChildMount,
				$cachedMount,
				$invokedCount
			) {
				$expectedPath = $this->path;
				if ($invokedCount->numberOfInvocations() === 1) {
					// call for the parent
					$expectedCachedMount = $cachedMount;
					$mountPoints = [$partialMount];
					$expectedForChildren = false;
				} else {
					// call for the children
					$expectedCachedMount = $cachedChildMount;
					$mountPoints = [$partialChildMount];
					$expectedForChildren = true;
				}

				$this->assertSame(SetupManagerTestPartialMountProvider::class, $providerClass);
				$this->assertSame($expectedPath, $pathArg);
				$this->assertSame($expectedForChildren, $forChildren);
				$this->assertCount(1, $mountProviderArgs);
				$this->assertInstanceOf(IMountProviderArgs::class, $mountProviderArgs[0]);
				$this->assertSame($expectedCachedMount, $mountProviderArgs[0]->mountInfo);

				return $mountPoints;
			});

		$homeMount = $this->createMock(IMountPoint::class);

		$this->mountProviderCollection->expects($this->once())
			->method('getHomeMountForUser')
			->willReturn($homeMount);

		$this->mountProviderCollection->expects($this->never())
			->method('getUserMountsForProviderClasses');

		$invokedCount = $this->exactly(3);
		$addMountExpectations = [
			1 => $homeMount,
			2 => $partialMount,
			3 => $partialChildMount,
		];
		$this->mountManager->expects($invokedCount)
			->method('addMount')
			->willReturnCallback($this->getAddMountCheckCallback($invokedCount, $addMountExpectations));

		// once the setup for a path has been done with children, setup for sub
		// paths should not create the same new mounts again
		$this->setupManager->setupForPath($this->path, true);
		$this->setupManager->setupForPath($childPath, false);
		$this->setupManager->setupForPath($childPath, true);
	}

	/**
	 * Tests that when called twice setupForPath does not set up mounts from
	 * providers implementing IPartialMountProviders or IMountProvider.
	 */
	public function testSetupForPathHandlesPartialAndFullProvidersWithChildren(): void {
		$parentPartialCachedMount = $this->getCachedMountInfo($this->mountPoint, 42);
		$childCachedPartialMount = $this->getCachedMountInfo("{$this->mountPoint}partial/", 43);
		$childCachedFullMount = $this->getCachedMountInfo("{$this->mountPoint}full/", 44, SetupManagerTestFullMountProvider::class);

		$this->userMountCache->expects($this->exactly(2))
			->method('getMountForPath')
			->with($this->user, $this->path)
			->willReturn($parentPartialCachedMount);
		$this->userMountCache->expects($this->exactly(2))
			->method('getMountsInPath')
			->with($this->user, $this->path)
			->willReturn([$childCachedPartialMount, $childCachedFullMount]);

		$homeMount = $this->createMock(IMountPoint::class);
		$parentPartialMount = $this->createMock(IMountPoint::class);
		$childPartialMount = $this->createMock(IMountPoint::class);
		$childFullProviderMount = $this->createMock(IMountPoint::class);

		$this->mountProviderCollection->expects($this->once())
			->method('getHomeMountForUser')
			->willReturn($homeMount);

		$this->userMountCache->expects($this->once())
			->method('registerMounts')
			->with(
				$this->user, [$childFullProviderMount],
				[SetupManagerTestFullMountProvider::class],
			);

		$this->fileAccess->expects($this->once())
			->method('getByFileId')
			->with(42)
			->willReturn($this->createMock(CacheEntry::class));
		$childMetadata = $this->createMock(CacheEntry::class);
		$this->fileAccess->expects($this->once())
			->method('getByFileIds')
			->with([43])
			->willReturn([43 => $childMetadata]);

		$invokedCount = $this->exactly(2);
		$this->mountProviderCollection->expects($invokedCount)
			->method('getUserMountsFromProviderByPath')
			->willReturnCallback(function (string $providerClass, string $pathArg, bool $forChildren, array $mountProviderArgs) use (
				$childCachedPartialMount,
				$childPartialMount,
				$parentPartialMount,
				$parentPartialCachedMount,
				$invokedCount) {
				$expectedPath = $this->path;
				if ($invokedCount->numberOfInvocations() === 1) {
					// call for the parent
					$expectedCachedMount = $parentPartialCachedMount;
					$mountPoints = [$parentPartialMount];
					$expectedForChildren = false;
				} else {
					// call for the children
					$expectedCachedMount = $childCachedPartialMount;
					$mountPoints = [$childPartialMount];
					$expectedForChildren = true;
				}

				$this->assertSame(SetupManagerTestPartialMountProvider::class, $providerClass);
				$this->assertSame($expectedPath, $pathArg);
				$this->assertSame($expectedForChildren, $forChildren);
				$this->assertCount(1, $mountProviderArgs);
				$this->assertInstanceOf(IMountProviderArgs::class, $mountProviderArgs[0]);
				$this->assertSame($expectedCachedMount, $mountProviderArgs[0]->mountInfo);

				return $mountPoints;
			});

		$this->mountProviderCollection->expects($this->once())
			->method('getUserMountsForProviderClasses')
			->with($this->user, [SetupManagerTestFullMountProvider::class])
			->willReturn([$childFullProviderMount]);

		$invokedCount = $this->exactly(4);
		$addMountExpectations = [
			1 => $homeMount,
			2 => $childFullProviderMount,
			3 => $parentPartialMount,
			4 => $childPartialMount,
		];
		$this->mountManager->expects($invokedCount)
			->method('addMount')
			->willReturnCallback($this->getAddMountCheckCallback($invokedCount, $addMountExpectations));

		// call twice to test that providers and mounts are only called once
		$this->setupManager->setupForPath($this->path, true);
		$this->setupManager->setupForPath($this->path, true);
	}

	public function testSetupForUserResetsUserPaths(): void {
		$cachedMount = $this->getCachedMountInfo($this->mountPoint, 42);

		$this->userMountCache->expects($this->once())
			->method('getMountForPath')
			->with($this->user, $this->path)
			->willReturn($cachedMount);
		$this->userMountCache->expects($this->never())
			->method('getMountsInPath');

		$this->fileAccess->expects($this->once())
			->method('getByFileId')
			->with(42)
			->willReturn($this->createMock(CacheEntry::class));

		$partialMount = $this->createMock(IMountPoint::class);

		$this->mountProviderCollection->expects($this->once())
			->method('getUserMountsFromProviderByPath')
			->with(
				SetupManagerTestPartialMountProvider::class,
				$this->path,
				false,
				$this->callback(function (array $args) use ($cachedMount) {
					$this->assertCount(1, $args);
					$this->assertInstanceOf(IMountProviderArgs::class,
						$args[0]);
					$this->assertSame($cachedMount, $args[0]->mountInfo);
					return true;
				})
			)
			->willReturn([$partialMount]);

		$homeMount = $this->createMock(IMountPoint::class);

		$this->mountProviderCollection->expects($this->once())
			->method('getHomeMountForUser')
			->willReturn($homeMount);
		$this->mountProviderCollection->expects($this->never())
			->method('getUserMountsForProviderClasses');

		$invokedCount = $this->exactly(2);
		$addMountExpectations = [
			1 => $homeMount,
			2 => $partialMount,
		];
		$this->mountManager->expects($invokedCount)
			->method('addMount')
			->willReturnCallback($this->getAddMountCheckCallback($invokedCount,
				$addMountExpectations));


		// setting up for $path but then for user should remove the setup path
		$this->setupManager->setupForPath($this->path, false);

		// note that only the mount known by SetupManrger is removed not the
		// home mount, because MountManager is mocked
		$this->mountManager->expects($this->once())
			->method('removeMount')
			->with($this->mountPoint);

		$this->setupManager->setupForUser($this->user);
	}

	/**
	 * Tests that after a path is setup by a
	 */
	public function testSetupForProviderResetsUserProviderPaths(): void {
		$cachedMount = $this->getCachedMountInfo($this->mountPoint, 42);

		$this->userMountCache->expects($this->once())
			->method('getMountForPath')
			->with($this->user, $this->path)
			->willReturn($cachedMount);
		$this->userMountCache->expects($this->never())
			->method('getMountsInPath');

		$this->fileAccess->expects($this->once())
			->method('getByFileId')
			->with(42)
			->willReturn($this->createMock(CacheEntry::class));

		$partialMount = $this->createMock(IMountPoint::class);
		$partialMount->expects($this->once())->method('getMountProvider')
			->willReturn(SetupManagerTestFullMountProvider::class);

		$this->mountProviderCollection->expects($this->once())
			->method('getUserMountsFromProviderByPath')
			->with(
				SetupManagerTestPartialMountProvider::class,
				$this->path,
				false,
				$this->callback(function (array $args) use ($cachedMount) {
					$this->assertCount(1, $args);
					$this->assertInstanceOf(IMountProviderArgs::class,
						$args[0]);
					$this->assertSame($cachedMount, $args[0]->mountInfo);
					return true;
				})
			)
			->willReturn([$partialMount]);

		$homeMount = $this->createMock(IMountPoint::class);

		$this->mountProviderCollection->expects($this->once())
			->method('getHomeMountForUser')
			->willReturn($homeMount);

		$invokedCount = $this->exactly(2);
		$addMountExpectations = [
			1 => $homeMount,
			2 => $partialMount,
		];
		$this->mountManager->expects($invokedCount)
			->method('addMount')
			->willReturnCallback($this->getAddMountCheckCallback($invokedCount,
				$addMountExpectations));
		$this->mountManager->expects($this->once())->method('getAll')
			->willReturn([$this->mountPoint => $partialMount]);

		// setting up for $path but then for user should remove the setup path
		$this->setupManager->setupForPath($this->path, false);

		// note that only the mount known by SetupManrger is removed not the
		// home mount, because MountManager is mocked
		$this->mountManager->expects($this->once())
			->method('removeMount')
			->with($this->mountPoint);

		$this->mountProviderCollection->expects($this->once())
			->method('getUserMountsForProviderClasses')
			->with($this->user, [SetupManagerTestFullMountProvider::class]);

		$this->setupManager->setupForProvider($this->path,
			[SetupManagerTestFullMountProvider::class]);
	}

	private function getAddMountCheckCallback(InvokedCount $invokedCount, $expectations): \Closure {
		return function (IMountPoint $actualMount) use ($invokedCount, $expectations) {
			$expectedMount = $expectations[$invokedCount->numberOfInvocations()] ?? null;
			$this->assertSame($expectedMount, $actualMount);
		};
	}

	public function getCachedMountInfo(string $mountPoint, int $rootId, string $providerClass = SetupManagerTestPartialMountProvider::class): ICachedMountInfo&MockObject {
		$cachedMount = $this->createMock(ICachedMountInfo::class);
		$cachedMount->method('getMountProvider')->willReturn($providerClass);
		$cachedMount->method('getMountPoint')->willReturn($mountPoint);
		$cachedMount->method('getRootId')->willReturn($rootId);

		return $cachedMount;
	}
}

class SetupManagerTestPartialMountProvider implements IPartialMountProvider {
	public function getMountsForUser(IUser $user, IStorageFactory $loader): array {
		return [];
	}

	public function getMountsForPath(string $path, bool $forChildren, array $mountProviderArgs, IStorageFactory $loader): array {
		return [];
	}
}

class SetupManagerTestFullMountProvider implements IMountProvider {
	public function getMountsForUser(IUser $user, IStorageFactory $loader): array {
		return [];
	}
}
