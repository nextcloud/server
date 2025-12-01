<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Robin Appelman <robin@icewind.nl>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Sharing\Listener;

use OC\User\LazyUser;
use OCA\Files_Sharing\MountPointVerifier;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IGroupManager;
use OCP\IUserManager;
use OCP\Share\Events\ShareCreatedEvent;
use OCP\Share\Events\UserAddedToShareEvent;
use OCP\Share\IShare;

/** @template-implements IEventListener<ShareCreatedEvent|UserAddedToShareEvent> */
class ResolveFileConflictListener implements IEventListener {
	public function __construct(
		private readonly MountPointVerifier $mountPointVerifier,
		private readonly IUserManager $userManager,
		private readonly IGroupManager $groupManager,
	) {
	}

	public function handle(Event $event): void {
		if ($event instanceof ShareCreatedEvent) {
			$this->addedShare($event->getShare());
		} elseif ($event instanceof UserAddedToShareEvent) {
			$this->mountPointVerifier->verifyMountPoint($event->getUser(), $event->getShare());
		}
	}

	private function addedShare(IShare $share) {
		foreach ($this->getUsersForShare($share) as $user) {
			$this->mountPointVerifier->verifyMountPoint($user, $share);
		}
	}

	private function getUsersForShare(IShare $share): \Iterator {
		if ($share->getShareType() == IShare::TYPE_USER) {
			yield new LazyUser($share->getSharedWith(), $this->userManager);
		} elseif ($share->getShareType() == IShare::TYPE_GROUP) {
			$group = $this->groupManager->get($share->getSharedWith());
			yield from $group->getUsers();
		} elseif ($share->getShareType() == IShare::TYPE_CIRCLE) {
			// todo
		}
	}
}
