<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Robin Appelman <robin@icewind.nl>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Sharing\Listener;

use OCA\Files_Sharing\AppInfo\Application;
use OCA\Files_Sharing\Config\ConfigLexicon;
use OCA\Files_Sharing\Event\UserShareAccessUpdatedEvent;
use OCA\Files_Sharing\ShareRecipientUpdater;
use OCP\Config\IUserConfig;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Group\Events\UserAddedEvent;
use OCP\Group\Events\UserRemovedEvent;
use OCP\IAppConfig;
use OCP\IUser;
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
	/**
	 * for how many users do we update the share date immediately,
	 * before just marking the other users when we know the relevant share
	 */
	private int $cutOffMarkAllSingleShare;
	/**
	 * for how many users do we update the share date immediately,
	 * before just marking the other users when the relevant shares are unknown
	 */
	private int $cutOffMarkAllShares ;

	private int $updatedCount = 0;

	public function __construct(
		private readonly IManager $shareManager,
		private readonly ShareRecipientUpdater $shareUpdater,
		private readonly IUserConfig $userConfig,
		IAppConfig $appConfig,
	) {
		$this->cutOffMarkAllSingleShare = $appConfig->getValueInt(Application::APP_ID, ConfigLexicon::UPDATE_SINGLE_CUTOFF, 10);
		$this->cutOffMarkAllShares = $appConfig->getValueInt(Application::APP_ID, ConfigLexicon::UPDATE_ALL_CUTOFF, 3);
	}

	public function handle(Event $event): void {
		if ($event instanceof UserShareAccessUpdatedEvent) {
			foreach ($event->getUsers() as $user) {
				$this->updateOrMarkUser($user, true);
			}
		}
		if ($event instanceof UserAddedEvent || $event instanceof UserRemovedEvent) {
			$this->updateOrMarkUser($event->getUser(), true);
		}
		if ($event instanceof ShareCreatedEvent || $event instanceof ShareTransferredEvent) {
			$share = $event->getShare();
			$shareTarget = $share->getTarget();
			foreach ($this->shareManager->getUsersForShare($share) as $user) {
				if ($share->getSharedBy() !== $user->getUID()) {
					$this->updatedCount++;
					if ($this->updatedCount <= $this->cutOffMarkAllSingleShare) {
						$this->shareUpdater->updateForShare($user, $share);
						// Share target validation might have changed the target, restore it for the next user
						$share->setTarget($shareTarget);
					} else {
						$this->markUserForRefresh($user);
					}
				}
			}
		}
		if ($event instanceof BeforeShareDeletedEvent) {
			foreach ($this->shareManager->getUsersForShare($event->getShare()) as $user) {
				$this->updateOrMarkUser($user, false, [$event->getShare()]);
			}
		}
	}

	private function updateOrMarkUser(IUser $user, bool $verifyMountPoints, array $ignoreShares = []): void {
		$this->updatedCount++;
		if ($this->updatedCount <= $this->cutOffMarkAllShares) {
			$this->shareUpdater->updateForUser($user, $verifyMountPoints, $ignoreShares);
		} else {
			$this->markUserForRefresh($user);
		}
	}

	private function markUserForRefresh(IUser $user): void {
		$this->userConfig->setValueBool($user->getUID(), Application::APP_ID, ConfigLexicon::USER_NEEDS_SHARE_REFRESH, true);
	}
}
