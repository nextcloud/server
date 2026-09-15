<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Sharing\Listener;

use OCA\Files_Sharing\Notification\Notifier;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Group\Events\UserAddedEvent;
use OCP\Notification\IManager as INotificationManager;
use OCP\Share\IManager as IShareManager;
use OCP\Share\IShare;

/**
 * Notifies a user about the group shares they gained access to by being
 * added to a group.
 *
 * @template-implements IEventListener<UserAddedEvent>
 */
class UserAddedToGroupNotificationListener implements IEventListener {
	private const SHARES_PER_PAGE = 50;

	public function __construct(
		private INotificationManager $notificationManager,
		private IShareManager $shareManager,
	) {
	}

	#[\Override]
	public function handle(Event $event): void {
		if (!($event instanceof UserAddedEvent)) {
			return;
		}

		$user = $event->getUser();
		$group = $event->getGroup();

		$offset = 0;
		while (true) {
			$shares = $this->shareManager->getSharedWith($user->getUID(), IShare::TYPE_GROUP, null, self::SHARES_PER_PAGE, $offset);
			if ($shares === []) {
				break;
			}

			foreach ($shares as $share) {
				if ($share->getSharedWith() !== $group->getGID()) {
					continue;
				}

				if ($user->getUID() === $share->getShareOwner()
					|| $user->getUID() === $share->getSharedBy()) {
					continue;
				}

				$notification = $this->notificationManager->createNotification();
				$notification->setApp('files_sharing')
					->setObject('share', $share->getFullId())
					->setDateTime($share->getShareTime())
					->setSubject(Notifier::INCOMING_GROUP_SHARE)
					->setUser($user->getUID());
				$this->notificationManager->notify($notification);
			}

			$offset += self::SHARES_PER_PAGE;
		}
	}
}
