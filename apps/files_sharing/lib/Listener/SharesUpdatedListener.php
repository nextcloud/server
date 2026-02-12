<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Robin Appelman <robin@icewind.nl>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Sharing\Listener;

use OCA\Files_Sharing\Event\UserShareAccessUpdatedEvent;
use OCA\Files_Sharing\ShareRecipientUpdater;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Group\Events\UserAddedEvent;
use OCP\Group\Events\UserRemovedEvent;
use OCP\Share\Events\BeforeShareDeletedEvent;
use OCP\Share\Events\ShareCreatedEvent;
use OCP\Share\Events\ShareTransferredEvent;
use OCP\Share\IManager;

/**
 * Listen to various events that can change what shares a user has access to
 *
 * @template-implements IEventListener<UserAddedEvent|UserRemovedEvent|ShareCreatedEvent|ShareTransferredEvent|BeforeShareDeletedEvent|UserShareAccessUpdatedEvent>
 */
class SharesUpdatedListener implements IEventListener {
	public function __construct(
		private readonly IManager $shareManager,
		private readonly ShareRecipientUpdater $shareUpdater,
	) {
	}
	public function handle(Event $event): void {
		if ($event instanceof UserShareAccessUpdatedEvent) {
			foreach ($event->getUsers() as $user) {
				$this->shareUpdater->updateForUser($user, true);
			}
		}
		if ($event instanceof UserAddedEvent || $event instanceof UserRemovedEvent) {
			$this->shareUpdater->updateForUser($event->getUser(), true);
		}
		if ($event instanceof ShareCreatedEvent || $event instanceof ShareTransferredEvent) {
			$share = $event->getShare();
			$shareTarget = $share->getTarget();
			foreach ($this->shareManager->getUsersForShare($share) as $user) {
				if ($share->getSharedBy() !== $user->getUID()) {
					$this->shareUpdater->updateForShare($user, $share);
					// Share target validation might have changed the target, restore it for the next user
					$share->setTarget($shareTarget);
				}
			}
		}
		if ($event instanceof BeforeShareDeletedEvent) {
			foreach ($this->shareManager->getUsersForShare($event->getShare()) as $user) {
				$this->shareManager->updateForUser($user, false, [$event->getShare()]);
			}
		}
	}
}
