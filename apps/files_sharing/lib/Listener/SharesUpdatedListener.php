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
use OCP\Files\Storage\IStorageFactory;
use OCP\Group\Events\UserAddedEvent;
use OCP\Group\Events\UserRemovedEvent;
use OCP\IUser;
use OCP\Share\Events\BeforeShareDeletedEvent;
use OCP\Share\Events\ShareCreatedEvent;
use OCP\Share\IManager;

/**
 * Listen to various events that can change what shares a user has access to
 *
 * @template-implements IEventListener<UserAddedEvent|UserRemovedEvent|ShareCreatedEvent|BeforeShareDeletedEvent|UserShareAccessUpdatedEvent>
 */
class SharesUpdatedListener implements IEventListener {
	public function __construct(
		private readonly IManager $shareManager,
		private readonly IUserMountCache $userMountCache,
		private readonly MountProvider $shareMountProvider,
		private readonly ShareTargetValidator $shareTargetValidator,
		private readonly IStorageFactory $storageFactory,
	) {
	}

	public function handle(Event $event): void {
		if ($event instanceof UserAddedEvent || $event instanceof UserRemovedEvent || $event instanceof UserShareAccessUpdatedEvent) {
			$this->updateForUser($event->getUser(), true);
		}
		if ($event instanceof ShareCreatedEvent) {
			foreach ($this->shareManager->getUsersForShare($event->getShare()) as $user) {
				$this->updateForUser($user, true);
			}
		}
		if ($event instanceof BeforeShareDeletedEvent) {
			foreach ($this->shareManager->getUsersForShare($event->getShare()) as $user) {
				$this->updateForUser($user, false, [$event->getShare()]);
			}
		}
	}

	private function updateForUser(IUser $user, bool $verifyMountPoints, array $ignoreShares = []): void {
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
	}
}
