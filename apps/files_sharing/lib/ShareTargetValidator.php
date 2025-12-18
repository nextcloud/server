<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Robin Appelman <robin@icewind.nl>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Sharing;

use OC\Files\Filesystem;
use OC\Files\SetupManager;
use OC\Files\View;
use OCP\Cache\CappedMemoryCache;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\Config\ICachedMountInfo;
use OCP\Files\Mount\IMountManager;
use OCP\Files\Mount\IMountPoint;
use OCP\IUser;
use OCP\Share\Events\VerifyMountPointEvent;
use OCP\Share\IManager;
use OCP\Share\IShare;

/**
 * Validate that mount target is valid
 */
class ShareTargetValidator {
	private CappedMemoryCache $folderExistsCache;

	public function __construct(
		private readonly IManager $shareManager,
		private readonly IEventDispatcher $eventDispatcher,
		private readonly SetupManager $setupManager,
		private readonly IMountManager $mountManager,
	) {
		$this->folderExistsCache = new CappedMemoryCache();
	}

	private function getViewForUser(IUser $user): View {
		/**
		 * @psalm-suppress InternalClass
		 * @psalm-suppress InternalMethod
		 */
		return new View('/' . $user->getUID() . '/files');
	}

	/**
	 * check if the parent folder exists otherwise move the mount point up
	 *
	 * @param array<string, ICachedMountInfo> $allCachedMounts Other mounts for the user, indexed by path
	 * @param IShare[] $childShares
	 * @return string
	 */
	public function verifyMountPoint(
		IUser $user,
		IShare &$share,
		array $allCachedMounts,
		array $childShares,
	): string {
		$mountPoint = basename($share->getTarget());
		$parent = dirname($share->getTarget());

		$recipientView = $this->getViewForUser($user);
		$event = new VerifyMountPointEvent($share, $recipientView, $parent);
		$this->eventDispatcher->dispatchTyped($event);
		$parent = $event->getParent();

		/** @psalm-suppress InternalMethod */
		$absoluteParent = $recipientView->getAbsolutePath($parent);
		$this->setupManager->setupForPath($absoluteParent);
		$parentMount = $this->mountManager->find($absoluteParent);

		$cached = $this->folderExistsCache->get($parent);
		if ($cached) {
			$parentExists = $cached;
		} else {
			$parentCache = $parentMount->getStorage()->getCache();
			$parentExists = $parentCache->inCache($parentMount->getInternalPath($absoluteParent));
			$this->folderExistsCache->set($parent, $parentExists);
		}
		if (!$parentExists) {
			$parent = Helper::getShareFolder($recipientView, $user->getUID());
			/** @psalm-suppress InternalMethod */
			$absoluteParent = $recipientView->getAbsolutePath($parent);
		}

		$newAbsoluteMountPoint = $this->generateUniqueTarget(
			Filesystem::normalizePath($absoluteParent . '/' . $mountPoint),
			$parentMount,
			$allCachedMounts,
		);

		/** @psalm-suppress InternalMethod */
		$newMountPoint = $recipientView->getRelativePath($newAbsoluteMountPoint);
		if ($newMountPoint === null) {
			return $share->getTarget();
		}

		if ($newMountPoint !== $share->getTarget()) {
			$this->updateFileTarget($user, $newMountPoint, $share, $childShares);
		}

		return $newMountPoint;
	}


	/**
	 * @param ICachedMountInfo[] $allCachedMounts
	 */
	private function generateUniqueTarget(string $absolutePath, IMountPoint $parentMount, array $allCachedMounts): string {
		$pathInfo = pathinfo($absolutePath);
		$ext = isset($pathInfo['extension']) ? '.' . $pathInfo['extension'] : '';
		$name = $pathInfo['filename'];
		$dir = $pathInfo['dirname'];

		$i = 2;
		$parentCache = $parentMount->getStorage()->getCache();
		$internalPath = $parentMount->getInternalPath($absolutePath);
		while ($parentCache->inCache($internalPath) || isset($allCachedMounts[$absolutePath . '/'])) {
			$absolutePath = Filesystem::normalizePath($dir . '/' . $name . ' (' . $i . ')' . $ext);
			$internalPath = $parentMount->getInternalPath($absolutePath);
			$i++;
		}

		return $absolutePath;
	}

	/**
	 * update fileTarget in the database if the mount point changed
	 *
	 * @param IShare[] $childShares
	 */
	private function updateFileTarget(IUser $user, string $newPath, IShare &$share, array $childShares) {
		$share->setTarget($newPath);

		foreach ($childShares as $tmpShare) {
			$tmpShare->setTarget($newPath);
			$this->shareManager->moveShare($tmpShare, $user->getUID());
		}
	}
}
