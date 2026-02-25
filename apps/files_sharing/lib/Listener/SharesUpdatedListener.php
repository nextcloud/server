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
use Psr\Clock\ClockInterface;

/**
 * Listen to various events that can change what shares a user has access to
 *
 * @template-implements IEventListener<UserAddedEvent|UserRemovedEvent|ShareCreatedEvent|ShareTransferredEvent|BeforeShareDeletedEvent|UserShareAccessUpdatedEvent>
 */
class SharesUpdatedListener implements IEventListener {
	/**
	 * for how long do we update the share date immediately,
	 * before just marking the other users
	 */
	private float $cutOffMarkTime;

	/**
	 * The total amount of time we've spent so far processing updates
	 */
	private float $updatedTime = 0.0;

	public function __construct(
		private readonly IManager $shareManager,
		private readonly ShareRecipientUpdater $shareUpdater,
		private readonly IUserConfig $userConfig,
		private readonly ClockInterface $clock,
		IAppConfig $appConfig,
	) {
		$this->cutOffMarkTime = $appConfig->getValueFloat(Application::APP_ID, ConfigLexicon::UPDATE_CUTOFF_TIME, 3.0);
	}

	public function handle(Event $event): void {
		if ($event instanceof UserShareAccessUpdatedEvent) {
			foreach ($event->getUsers() as $user) {
				$this->updateOrMarkUser($user);
			}
		}
		if ($event instanceof UserAddedEvent || $event instanceof UserRemovedEvent) {
			$this->updateOrMarkUser($event->getUser());
		}
		if ($event instanceof ShareCreatedEvent || $event instanceof ShareTransferredEvent) {
			$share = $event->getShare();
			$shareTarget = $share->getTarget();
			foreach ($this->shareManager->getUsersForShare($share) as $user) {
				if ($share->getSharedBy() !== $user->getUID()) {
					$this->markOrRun($user, function () use ($user, $share) {
						$this->shareUpdater->updateForAddedShare($user, $share);
					});
					// Share target validation might have changed the target, restore it for the next user
					$share->setTarget($shareTarget);
				}
			}
		}
		if ($event instanceof BeforeShareDeletedEvent) {
			$share = $event->getShare();
			foreach ($this->shareManager->getUsersForShare($share) as $user) {
				$this->markOrRun($user, function () use ($user, $share) {
					$this->shareUpdater->updateForDeletedShare($user, $share);
				});
			}
		}
	}

	private function markOrRun(IUser $user, callable $callback): void {
		$start = floatval($this->clock->now()->format('U.u'));
		if ($this->cutOffMarkTime === -1.0 || $this->updatedTime < $this->cutOffMarkTime) {
			$callback();
		} else {
			$this->markUserForRefresh($user);
		}
		$end = floatval($this->clock->now()->format('U.u'));
		$this->updatedTime += $end - $start;
	}

	private function updateOrMarkUser(IUser $user): void {
		$this->markOrRun($user, function () use ($user) {
			$this->shareUpdater->updateForUser($user);
		});
	}

	private function markUserForRefresh(IUser $user): void {
		$this->userConfig->setValueBool($user->getUID(), Application::APP_ID, ConfigLexicon::USER_NEEDS_SHARE_REFRESH, true);
	}

	public function setCutOffMarkTime(float|int $cutOffMarkTime): void {
		$this->cutOffMarkTime = (float)$cutOffMarkTime;
	}
}
