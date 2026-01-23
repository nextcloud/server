<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Robin Appelman <robin@icewind.nl>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Sharing\Listener;

use OCA\Files_Sharing\Event\UserShareAccessUpdatedEvent;
use OCA\Files_Sharing\MountProvider;
use OCA\Files_Sharing\ShareTargetValidator;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\Config\ICachedMountInfo;
use OCP\Files\Config\IUserMountCache;
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
	) {
	}
	public function handle(Event $event): void {
		if ($event instanceof UserShareAccessUpdatedEvent) {
			foreach ($event->getUsers() as $user) {
				$this->updateForUser($user);
			}
		}
		if ($event instanceof UserAddedEvent || $event instanceof UserRemovedEvent) {
			$this->updateForUser($event->getUser());
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
	}

	private function updateForUser(IUser $user): void {
		// prevent recursion
		if (isset($this->inUpdate[$user->getUID()])) {
			return;
		}
		$this->inUpdate[$user->getUID()] = true;

		$cachedMounts = $this->userMountCache->getMountsForUser($user);
		$mountPoints = array_map(fn (ICachedMountInfo $mount) => $mount->getMountPoint(), $cachedMounts);
		$mountsByPath = array_combine($mountPoints, $cachedMounts);

		$shares = $this->shareMountProvider->getSuperSharesForUser($user);

		foreach ($shares as &$share) {
			[$parentShare, $groupedShares] = $share;
			$mountPoint = '/' . $user->getUID() . '/files/' . trim($parentShare->getTarget(), '/') . '/';
			$mountKey = $parentShare->getNodeId() . '::' . $mountPoint;
			if (!isset($cachedMounts[$mountKey])) {
				$this->shareTargetValidator->verifyMountPoint($user, $parentShare, $mountsByPath, $groupedShares);
			}
		}

		unset($this->inUpdate[$user->getUID()]);
	}

	private function updateForShare(IUser $user, IShare $share): void {
		if (isset($this->updatedUsers[$user->getUID()])) {
			return;
		}
		$this->updatedUsers[$user->getUID()] = true;

		$cachedMounts = $this->userMountCache->getMountsForUser($user);
		$mountPoints = array_map(fn (ICachedMountInfo $mount) => $mount->getMountPoint(), $cachedMounts);
		$mountsByPath = array_combine($mountPoints, $cachedMounts);

		$mountPoint = '/' . $user->getUID() . '/files/' . trim($share->getTarget(), '/') . '/';
		$mountKey = $share->getNodeId() . '::' . $mountPoint;
		if (!isset($cachedMounts[$mountKey])) {
			$this->shareTargetValidator->verifyMountPoint($user, $share, $mountsByPath, [$share]);
		}
	}
}
