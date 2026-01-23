<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Robin Appelman <robin@icewind.nl>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Sharing\Listener;

use OC\Files\FileInfo;
use OCA\Files_Sharing\Event\UserShareAccessUpdatedEvent;
use OCA\Files_Sharing\MountProvider;
use OCA\Files_Sharing\ShareTargetValidator;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\Config\ICachedMountInfo;
use OCP\Files\Config\IUserMountCache;
use OCP\Files\Storage\IStorageFactory;
use OCP\Group\Events\UserAddedEvent;
use OCP\Group\Events\UserRemovedEvent;
use OCP\IUser;
use OCP\Share\Events\BeforeShareDeletedEvent;
use OCP\Share\Events\ShareCreatedEvent;
use OCP\Share\Events\ShareTransferredEvent;
use OCP\Share\IManager;
use OCP\Share\IShare;

/**
 * Listen to various events that can change what shares a user has access to
 *
 * @template-implements IEventListener<UserAddedEvent|UserRemovedEvent|ShareCreatedEvent|ShareTransferredEvent|BeforeShareDeletedEvent|UserShareAccessUpdatedEvent>
 */
class SharesUpdatedListener implements IEventListener {
	private array $inUpdate = [];

	public function __construct(
		private readonly IManager $shareManager,
		private readonly IUserMountCache $userMountCache,
		private readonly MountProvider $shareMountProvider,
		private readonly ShareTargetValidator $shareTargetValidator,
		private readonly IStorageFactory $storageFactory,
	) {
	}
	public function handle(Event $event): void {
		if ($event instanceof UserShareAccessUpdatedEvent) {
			foreach ($event->getUsers() as $user) {
				$this->updateForUser($user, true);
			}
		}
		if ($event instanceof UserAddedEvent || $event instanceof UserRemovedEvent) {
			$this->updateForUser($event->getUser(), true);
		}
		if ($event instanceof ShareCreatedEvent || $event instanceof ShareTransferredEvent) {
			$share = $event->getShare();
			$shareTarget = $share->getTarget();
			foreach ($this->shareManager->getUsersForShare($share) as $user) {
				if ($share->getSharedBy() !== $user->getUID()) {
					$this->updateForShare($user, $share);
					// Share target validation might have changed the target, restore it for the next user
					$share->setTarget($shareTarget);
				}
			}
		}
		if ($event instanceof BeforeShareDeletedEvent) {
			foreach ($this->shareManager->getUsersForShare($event->getShare()) as $user) {
				$this->updateForUser($user, false, [$event->getShare()]);
			}
		}
	}

	private function updateForUser(IUser $user, bool $verifyMountPoints, array $ignoreShares = []): void {
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

	private function updateForShare(IUser $user, IShare $share): void {
		$cachedMounts = $this->userMountCache->getMountsForUser($user);
		$mountPoints = array_map(fn (ICachedMountInfo $mount) => $mount->getMountPoint(), $cachedMounts);
		$mountsByPath = array_combine($mountPoints, $cachedMounts);

		$target = $this->shareTargetValidator->verifyMountPoint($user, $share, $mountsByPath, [$share]);
		$mountPoint = '/' . $user->getUID() . '/files/' . trim($target, '/') . '/';

		$fileInfo = $share->getNode();
		if (!$fileInfo instanceof FileInfo) {
			throw new \Exception("share node is the wrong fileinfo");
		}
		$this->userMountCache->addMount($user, $mountPoint, $fileInfo->getData(), MountProvider::class);
	}
}
