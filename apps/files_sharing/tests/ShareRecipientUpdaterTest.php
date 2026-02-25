<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Files_Sharing\Tests;

use OCA\Files_Sharing\MountProvider;
use OCA\Files_Sharing\ShareRecipientUpdater;
use OCA\Files_Sharing\ShareTargetValidator;
use OCP\Files\Cache\ICacheEntry;
use OCP\Files\Config\ICachedMountInfo;
use OCP\Files\Config\IUserMountCache;
use OCP\Files\Mount\IMountPoint;
use OCP\Files\Node;
use OCP\Files\Storage\IStorageFactory;
use OCP\IUser;
use OCP\Share\IShare;
use PHPUnit\Framework\MockObject\MockObject;
use Test\Traits\UserTrait;

class ShareRecipientUpdaterTest extends \Test\TestCase {
	use UserTrait;

	private IUserMountCache&MockObject $userMountCache;
	private MountProvider&MockObject $shareMountProvider;
	private ShareTargetValidator&MockObject $shareTargetValidator;
	private IStorageFactory&MockObject $storageFactory;
	private ShareRecipientUpdater $updater;

	protected function setUp(): void {
		parent::setUp();

		$this->userMountCache = $this->createMock(IUserMountCache::class);
		$this->shareMountProvider = $this->createMock(MountProvider::class);
		$this->shareTargetValidator = $this->createMock(ShareTargetValidator::class);
		$this->storageFactory = $this->createMock(IStorageFactory::class);

		$this->updater = new ShareRecipientUpdater(
			$this->userMountCache,
			$this->shareMountProvider,
			$this->shareTargetValidator,
			$this->storageFactory,
		);
	}

	public function testUpdateForShare() {
		$share = $this->createMock(IShare::class);
		$node = $this->createMock(Node::class);
		$cacheEntry = $this->createMock(ICacheEntry::class);
		$share->method('getNode')
			->willReturn($node);
		$node->method('getData')
			->willReturn($cacheEntry);
		$user1 = $this->createUser('user1', '');

		$this->userMountCache->method('getMountsForUser')
			->with($user1)
			->willReturn([]);

		$this->shareTargetValidator->method('verifyMountPoint')
			->with($user1, $share, [], [$share])
			->willReturn('/new-target');

		$this->userMountCache->expects($this->exactly(1))
			->method('addMount')
			->with($user1, '/user1/files/new-target/', $cacheEntry, MountProvider::class);

		$this->updater->updateForAddedShare($user1, $share);
	}

	/**
	 * @param IUser $user
	 * @param list<array{fileid: int, mount_point: string, provider: string}> $mounts
	 * @return void
	 */
	private function setCachedMounts(IUser $user, array $mounts) {
		$cachedMounts = array_map(function (array $mount): ICachedMountInfo {
			$cachedMount = $this->createMock(ICachedMountInfo::class);
			$cachedMount->method('getRootId')
				->willReturn($mount['fileid']);
			$cachedMount->method('getMountPoint')
				->willReturn($mount['mount_point']);
			$cachedMount->method('getMountProvider')
				->willReturn($mount['provider']);
			return $cachedMount;
		}, $mounts);
		$mountKeys = array_map(function (array $mount): string {
			return $mount['fileid'] . '::' . $mount['mount_point'];
		}, $mounts);

		$this->userMountCache->method('getMountsForUser')
			->with($user)
			->willReturn(array_combine($mountKeys, $cachedMounts));
	}

	public function testUpdateForUserAddedNoExisting() {
		$share = $this->createMock(IShare::class);
		$share->method('getTarget')
			->willReturn('/target');
		$share->method('getNodeId')
			->willReturn(111);
		$user1 = $this->createUser('user1', '');
		$newMount = $this->createMock(IMountPoint::class);

		$this->shareMountProvider->method('getSuperSharesForUser')
			->with($user1, [])
			->willReturn([[
				$share,
				[$share],
			]]);

		$this->shareMountProvider->method('getMountsFromSuperShares')
			->with($user1, [[
				$share,
				[$share],
			]], $this->storageFactory)
			->willReturn([$newMount]);

		$this->setCachedMounts($user1, []);

		$this->shareTargetValidator->method('verifyMountPoint')
			->with($user1, $share, [], [$share])
			->willReturn('/new-target');

		$this->userMountCache->expects($this->exactly(1))
			->method('registerMounts')
			->with($user1, [$newMount], [MountProvider::class]);

		$this->updater->updateForUser($user1);
	}

	public function testUpdateForUserNoChanges() {
		$share = $this->createMock(IShare::class);
		$share->method('getTarget')
			->willReturn('/target');
		$share->method('getNodeId')
			->willReturn(111);
		$user1 = $this->createUser('user1', '');

		$this->shareMountProvider->method('getSuperSharesForUser')
			->with($user1, [])
			->willReturn([[
				$share,
				[$share],
			]]);

		$this->setCachedMounts($user1, [
			['fileid' => 111, 'mount_point' => '/user1/files/target/', 'provider' => MountProvider::class],
		]);

		$this->shareTargetValidator->expects($this->never())
			->method('verifyMountPoint');

		$this->userMountCache->expects($this->never())
			->method('registerMounts');

		$this->updater->updateForUser($user1);
	}

	public function testUpdateForUserRemoved() {
		$share = $this->createMock(IShare::class);
		$share->method('getTarget')
			->willReturn('/target');
		$share->method('getNodeId')
			->willReturn(111);
		$user1 = $this->createUser('user1', '');

		$this->shareMountProvider->method('getSuperSharesForUser')
			->with($user1, [])
			->willReturn([]);

		$this->setCachedMounts($user1, [
			['fileid' => 111, 'mount_point' => '/user1/files/target/', 'provider' => MountProvider::class],
		]);

		$this->shareTargetValidator->expects($this->never())
			->method('verifyMountPoint');

		$this->userMountCache->expects($this->exactly(1))
			->method('registerMounts')
			->with($user1, [], [MountProvider::class]);

		$this->updater->updateForUser($user1);
	}

	public function testDeletedShare() {
		$share = $this->createMock(IShare::class);
		$share->method('getTarget')
			->willReturn('/target');
		$share->method('getNodeId')
			->willReturn(111);
		$user1 = $this->createUser('user1', '');

		$this->shareTargetValidator->expects($this->never())
			->method('verifyMountPoint');

		$this->userMountCache->expects($this->exactly(1))
			->method('removeMount')
			->with('/user1/files/target/');

		$this->updater->updateForDeletedShare($user1, $share);
	}
}
