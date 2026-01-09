<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Robin Appelman <robin@icewind.nl>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Sharing\Listener;

use OCA\Files_Sharing\MountProvider;
use OCA\Files_Sharing\ShareTargetValidator;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\Config\IUserMountCache;
use OCP\Files\Storage\IStorageFactory;
use OCP\Group\Events\UserAddedEvent;
use OCP\Group\Events\UserRemovedEvent;
use OCP\IUser;
use OCP\Share\Events\ShareCreatedEvent;
use OCP\Share\Events\ShareDeletedEvent;
use OCP\Share\IManager;

/**
 * Listen to various events that can change what shares a user has access to
 *
 * @template-implements IEventListener<UserAddedEvent|UserRemovedEvent|ShareCreatedEvent|ShareDeletedEvent>
 */
class SharesUpdatedListener implements IEventListener {
	public function __construct(
		private readonly IManager $shareManager,
		private readonly IUserMountCache $userMountCache,
		private readonly MountProvider $shareMountProvider,
		private readonly IStorageFactory $storageFactory,
		private readonly ShareTargetValidator $shareTargetValidator,
	) {
	}

	public function handle(Event $event): void {
		if ($event instanceof UserAddedEvent || $event instanceof UserRemovedEvent) {
			$this->updateForUser($event->getUser());
		}
		if ($event instanceof ShareCreatedEvent || $event instanceof ShareDeletedEvent) {
			foreach ($this->shareManager->getUsersForShare($event->getShare()) as $user) {
				$this->updateForUser($user);
			}
		}
	}

	private function updateForUser(IUser $user): void {
		$cachedMounts = $this->userMountCache->getMountsForUser($user);

		$shares = $this->shareMountProvider->getSuperSharesForUser($user);

		$foundUpdate = count($shares) !== count($cachedMounts);
		foreach ($shares as &$share) {
			[$parentShare, $groupedShares] = $share;
			$mountPoint = '/' . $user->getUID() . '/files/' . $parentShare->getTarget();
			$mountKey = $parentShare->getNodeId() . '::' . $mountPoint;
			if (!isset($cachedMounts[$mountKey])) {
				$foundUpdate = true;
				$this->shareTargetValidator->verifyMountPoint($user, $parentShare, $cachedMounts, $groupedShares);
			}
		}

		if ($foundUpdate) {
			$mounts = $this->shareMountProvider->getMountsFromSuperShares($user, $shares, $this->storageFactory);
			$this->userMountCache->registerMounts($user, $mounts, [MountProvider::class]);
		}
	}
}
