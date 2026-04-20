<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Sharing;

use OCP\Files\Config\ICachedMountInfo;
use OCP\Files\Config\IUserMountCache;
use OCP\Files\Storage\IStorageFactory;
use OCP\IUser;
use OCP\Share\Exceptions\ShareNotFound;
use OCP\Share\IManager;
use OCP\Share\IShare;

class ShareRecipientUpdater {
	private array $inUpdate = [];

	public function __construct(
		private readonly IUserMountCache $userMountCache,
		private readonly MountProvider $shareMountProvider,
		private readonly ShareTargetValidator $shareTargetValidator,
		private readonly IStorageFactory $storageFactory,
		private readonly IManager $shareManager,
	) {
	}

	/**
	 * Validate all received shares for a user
	 */
	public function updateForUser(IUser $user): void {
		// prevent recursion
		if ($this->isInUpdate($user)) {
			return;
		}
		$this->inUpdate[$user->getUID()] = true;

		$cachedMounts = $this->userMountCache->getMountsForUser($user);
		$shareMounts = array_filter($cachedMounts, fn (ICachedMountInfo $mount) => $mount->getMountProvider() === MountProvider::class);
		$mountPoints = array_map(fn (ICachedMountInfo $mount) => $mount->getMountPoint(), $cachedMounts);
		$mountsByPath = array_combine($mountPoints, $cachedMounts);

		$shares = $this->shareMountProvider->getSuperSharesForUser($user);

		// the share mounts have changed if either the number of shares doesn't matched the number of share mounts
		// or there is a share for which we don't have a mount yet.
		$mountsChanged = count($shares) !== count($shareMounts);
		foreach ($shares as $share) {
			[$parentShare, $groupedShares] = $share;
			$mountPoint = $this->getMountPointFromTarget($user, $parentShare->getTarget());
			$mountKey = $parentShare->getNodeId() . '::' . $mountPoint;
			if (!isset($cachedMounts[$mountKey])) {
				$mountsChanged = true;
				$this->shareTargetValidator->verifyMountPoint($user, $parentShare, fn ($path) => $mountsByPath[$path] ?? null, $groupedShares);
			}
		}

		if ($mountsChanged) {
			$newMounts = $this->shareMountProvider->getMountsFromSuperShares($user, $shares, $this->storageFactory);
			$this->userMountCache->registerMounts($user, $newMounts, [MountProvider::class]);
		}

		unset($this->inUpdate[$user->getUID()]);
	}

	public function isInUpdate(IUser $user): bool {
		return isset($this->inUpdate[$user->getUID()]);
	}

	/**
	 * Validate a single received share for a user
	 */
	public function updateForAddedShare(IUser $user, IShare $share): void {
		$target = $this->shareTargetValidator->verifyMountPoint($user, $share, fn ($path) => $this->userMountCache->getMountAtPath($user, $path), [$share]);
		$mountPoint = $this->getMountPointFromTarget($user, $target);

		$this->userMountCache->addMount($user, $mountPoint, $share->getNode()->getData(), MountProvider::class);
	}

	private function getMountPointFromTarget(IUser $user, string $target): string {
		return '/' . $user->getUID() . '/files/' . trim($target, '/') . '/';
	}

	/**
	 * Process a single deleted share for a user
	 */
	public function updateForDeletedShare(IUser $user, IShare $share): void {
		try {
			$userShare = $this->shareManager->getShareById($share->getFullId(), $user->getUID());
			$this->userMountCache->removeMount($this->getMountPointFromTarget($user, $userShare->getTarget()), $user);
		} catch (ShareNotFound) {
			// user doesn't actually have access to the share
		}
	}

	/**
	 * Process a single moved share for a user
	 */
	public function updateForMovedShare(IUser $user, IShare $share): void {
		$originalTarget = $share->getOriginalTarget();
		if ($originalTarget != null) {
			$newMountPoint = $this->getMountPointFromTarget($user, $share->getTarget());
			$oldMountPoint = $this->getMountPointFromTarget($user, $originalTarget);
			$this->userMountCache->removeMount($oldMountPoint, $user);
			$this->userMountCache->addMount($user, $newMountPoint, $share->getNode()->getData(), MountProvider::class);
		} else {
			$this->updateForUser($user);
		}
	}
}
