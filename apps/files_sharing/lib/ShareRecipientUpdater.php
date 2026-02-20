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
use OCP\Share\IShare;

class ShareRecipientUpdater {
	private array $inUpdate = [];

	public function __construct(
		private readonly IUserMountCache $userMountCache,
		private readonly MountProvider $shareMountProvider,
		private readonly ShareTargetValidator $shareTargetValidator,
		private readonly IStorageFactory $storageFactory,
	) {
	}

	/**
	 * Validate all received shares for a user
	 */
	public function updateForUser(IUser $user, bool $verifyMountPoints = true, array $ignoreShares = []): void {
		// prevent recursion
		if (isset($this->inUpdate[$user->getUID()])) {
			return;
		}
		$this->inUpdate[$user->getUID()] = true;

		$cachedMounts = $this->userMountCache->getMountsForUser($user);
		$shareMounts = array_filter($cachedMounts, fn (ICachedMountInfo $mount) => $mount->getMountProvider() === MountProvider::class);
		$mountPoints = array_map(fn (ICachedMountInfo $mount) => $mount->getMountPoint(), $cachedMounts);
		$mountsByPath = array_combine($mountPoints, $cachedMounts);

		$shares = $this->shareMountProvider->getSuperSharesForUser($user, $ignoreShares);

		// the share mounts have changed if either the number of shares doesn't matched the number of share mounts
		// or there is a share for which we don't have a mount yet.
		$mountsChanged = count($shares) !== count($shareMounts);
		foreach ($shares as &$share) {
			[$parentShare, $groupedShares] = $share;
			$mountPoint = '/' . $user->getUID() . '/files/' . trim($parentShare->getTarget(), '/') . '/';
			$mountKey = $parentShare->getNodeId() . '::' . $mountPoint;
			if (!isset($cachedMounts[$mountKey])) {
				$mountsChanged = true;
				if ($verifyMountPoints) {
					$this->shareTargetValidator->verifyMountPoint($user, $parentShare, $mountsByPath, $groupedShares);
				}
			}
		}

		if ($mountsChanged) {
			$newMounts = $this->shareMountProvider->getMountsFromSuperShares($user, $shares, $this->storageFactory);
			$this->userMountCache->registerMounts($user, $newMounts, [MountProvider::class]);
		}

		unset($this->inUpdate[$user->getUID()]);
	}

	/**
	 * Validate a single received share for a user
	 */
	public function updateForAddedShare(IUser $user, IShare $share): void {
		$cachedMounts = $this->userMountCache->getMountsForUser($user);
		$mountPoints = array_map(fn (ICachedMountInfo $mount) => $mount->getMountPoint(), $cachedMounts);
		$mountsByPath = array_combine($mountPoints, $cachedMounts);

		$target = $this->shareTargetValidator->verifyMountPoint($user, $share, $mountsByPath, [$share]);
		$mountPoint = '/' . $user->getUID() . '/files/' . trim($target, '/') . '/';

		$this->userMountCache->addMount($user, $mountPoint, $share->getNode()->getData(), MountProvider::class);
	}
}
