<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2021 Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\Settings\Listener;

use OC\Group\Manager;
use OCA\Settings\Activity\GroupProvider;
use OCP\Activity\IManager;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Group\Events\UserRemovedEvent;
use OCP\IUser;
use OCP\IUserSession;

/** @template-implements IEventListener<UserRemovedEvent> */
class UserRemovedFromGroupActivityListener implements IEventListener {

	/** @var Manager */
	private $groupManager;

	/** @var IManager */
	private $activityManager;

	/** @var IUserSession */
	private $userSession;

	public function __construct(
		Manager $groupManager,
		IManager $activityManager,
		IUserSession $userSession
	) {
		$this->groupManager = $groupManager;
		$this->activityManager = $activityManager;
		$this->userSession = $userSession;
	}

	public function handle(Event $event): void {
		if (!($event instanceof UserRemovedEvent)) {
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
				->setSubject(GroupProvider::REMOVED_FROM_GROUP, [
					'user' => $user->getUID(),
					'group' => $group->getGID(),
					'actor' => $actor->getUID(),
				]);
		} else {
			$event->setSubject(GroupProvider::REMOVED_FROM_GROUP, [
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
