<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Sharing\Notification;

use OCP\IGroupManager;
use OCP\Notification\IManager as INotificationManager;
use OCP\Notification\INotification;
use OCP\Share\Events\ShareCreatedEvent;
use OCP\Share\IShare;

class Listener {

	public function __construct(
		protected INotificationManager $notificationManager,
		protected IGroupManager $groupManager,
	) {
	}

	public function shareNotification(ShareCreatedEvent $event): void {
		$share = $event->getShare();
		$notification = $this->instantiateNotification($share);

		if ($share->getShareType() === IShare::TYPE_USER) {
			$notification->setSubject(Notifier::INCOMING_USER_SHARE)
				->setUser($share->getSharedWith());
			$this->notificationManager->notify($notification);
		} elseif ($share->getShareType() === IShare::TYPE_GROUP) {
			$notification->setSubject(Notifier::INCOMING_GROUP_SHARE);
			$group = $this->groupManager->get($share->getSharedWith());

			foreach ($group->getUsers() as $user) {
				if ($user->getUID() === $share->getShareOwner()
					|| $user->getUID() === $share->getSharedBy()) {
					continue;
				}

				$notification->setUser($user->getUID());
				$this->notificationManager->notify($notification);
			}
		}
	}

	/**
	 * @param IShare $share
	 * @return INotification
	 */
	protected function instantiateNotification(IShare $share): INotification {
		$notification = $this->notificationManager->createNotification();
		$notification
			->setApp('files_sharing')
			->setObject('share', $share->getFullId())
			->setDateTime($share->getShareTime());

		return $notification;
	}
}
