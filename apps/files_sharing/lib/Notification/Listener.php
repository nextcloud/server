<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Sharing\Notification;

use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\Notification\IManager as INotificationManager;
use OCP\Notification\INotification;
use OCP\Share\Events\ShareCreatedEvent;
use OCP\Share\IManager as IShareManager;
use OCP\Share\IShare;
use Symfony\Component\EventDispatcher\GenericEvent;

class Listener {

	public function __construct(
		protected INotificationManager $notificationManager,
		protected IShareManager $shareManager,
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
	 * @param GenericEvent $event
	 */
	public function userAddedToGroup(GenericEvent $event): void {
		/** @var IGroup $group */
		$group = $event->getSubject();
		/** @var IUser $user */
		$user = $event->getArgument('user');

		$offset = 0;
		while (true) {
			$shares = $this->shareManager->getSharedWith($user->getUID(), IShare::TYPE_GROUP, null, 50, $offset);
			if (empty($shares)) {
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

				$notification = $this->instantiateNotification($share);
				$notification->setSubject(Notifier::INCOMING_GROUP_SHARE)
					->setUser($user->getUID());
				$this->notificationManager->notify($notification);
			}
			$offset += 50;
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
