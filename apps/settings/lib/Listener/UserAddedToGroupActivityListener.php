<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\Listener;

use OC\Group\Manager;
use OCA\Settings\Activity\GroupProvider;
use OCP\Activity\IManager;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Group\Events\UserAddedEvent;
use OCP\IUser;
use OCP\IUserSession;

/** @template-implements IEventListener<UserAddedEvent> */
class UserAddedToGroupActivityListener implements IEventListener {

	public function __construct(
		private Manager $groupManager,
		private IManager $activityManager,
		private IUserSession $userSession,
	) {
	}

	public function handle(Event $event): void {
		if (!($event instanceof UserAddedEvent)) {
			return;
		}

		$user = $event->getUser();
		$group = $event->getGroup();

		$subAdminManager = $this->groupManager->getSubAdmin();
		$usersToNotify = $subAdminManager->getGroupsSubAdmins($group);
		$usersToNotify[] = $user;


		$event = $this->activityManager->generateEvent();
		$event->setApp('settings')
			->setType('group_settings');

		$actor = $this->userSession->getUser();
		if ($actor instanceof IUser) {
			$event->setAuthor($actor->getUID())
				->setSubject(GroupProvider::ADDED_TO_GROUP, [
					'user' => $user->getUID(),
					'group' => $group->getGID(),
					'actor' => $actor->getUID(),
				]);
		} else {
			$event->setSubject(GroupProvider::ADDED_TO_GROUP, [
				'user' => $user->getUID(),
				'group' => $group->getGID(),
			]);
		}

		foreach ($usersToNotify as $userToNotify) {
			$event->setAffectedUser($userToNotify->getUID());
			$this->activityManager->publish($event);
		}
	}
}
