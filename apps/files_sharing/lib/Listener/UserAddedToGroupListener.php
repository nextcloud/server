<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Sharing\Listener;

use OCA\Files_Sharing\AppInfo\Application;
use OCA\Files_Sharing\Notification\Notifier;
use OCP\Config\IUserConfig;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Group\Events\UserAddedEvent;
use OCP\IConfig;
use OCP\IUser;
use OCP\Notification\IManager as INotificationManager;
use OCP\Share\IManager;
use OCP\Share\IShare;

/** @template-implements IEventListener<UserAddedEvent> */
class UserAddedToGroupListener implements IEventListener {
	private const int SHARES_PER_PAGE = 50;

	public function __construct(
		private readonly IConfig $config,
		private readonly IUserConfig $userConfig,
		private readonly IManager $shareManager,
		private readonly INotificationManager $notificationManager,
	) {
	}

	#[\Override]
	public function handle(Event $event): void {
		if (!($event instanceof UserAddedEvent)) {
			return;
		}

		if ($this->hasAutoAccept($event->getUser())) {
			$this->handleAutoAccept($event);
		} else {
			$this->handleAcceptNotification($event);
		}
	}

	private function hasAutoAccept(IUser $user): bool {
		// If shares have to be accepted by default, then auto accept is disabled by default
		$defaultAcceptSystemConfig = !$this->config->getSystemValueBool('sharing.enable_share_accept', false);
		$acceptDefault = $this->userConfig->getValueBool($user->getUID(), Application::APP_ID, 'default_accept', $defaultAcceptSystemConfig);
		return (!$this->config->getSystemValueBool('sharing.force_share_accept', false) && $acceptDefault);
	}

	/**
	 * Handles auto-accepting shares for a user that has been added to a group.
	 */
	private function handleAutoAccept(UserAddedEvent $event): void {
		$user = $event->getUser();
		$group = $event->getGroup();

		// Get all group shares this user has access to now to filter later
		$shares = $this->shareManager->getSharedWith($user->getUID(), IShare::TYPE_GROUP, null, -1);

		foreach ($shares as $share) {
			// If this is not the new group we can skip it
			if ($share->getSharedWith() !== $group->getGID()) {
				continue;
			}

			// Accept the share if needed
			$this->shareManager->acceptShare($share, $user->getUID());
		}
	}

	/**
	 * Notifies a user about the group shares they gained access to by being
	 * added to a group.
	 */
	private function handleAcceptNotification(UserAddedEvent $event): void {
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
				$notification->setApp(Application::APP_ID)
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
