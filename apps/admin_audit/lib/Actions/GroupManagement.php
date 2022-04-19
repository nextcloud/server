<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2016 Bjoern Schiessle <bjoern@schiessle.org>
 *
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Roger Szabo <roger.szabo@web.de>
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
namespace OCA\AdminAudit\Actions;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Group\Events\GroupCreatedEvent;
use OCP\Group\Events\GroupDeletedEvent;
use OCP\Group\Events\UserAddedEvent;
use OCP\Group\Events\UserRemovedEvent;
use OCP\IGroup;
use OCP\IUser;

/**
 * Class GroupManagement logs all group manager related events
 *
 * @package OCA\AdminAudit\Actions
 */
class GroupManagement extends Action implements IEventListener {
	public function handle(Event $event): void {
		if ($event instanceof UserAddedEvent) {
			$this->addUser($event->getGroup(), $event->getUser());
		} elseif ($event instanceof UserRemovedEvent) {
			$this->removeUser($event->getGroup(), $event->getUser());
		} elseif ($event instanceof GroupCreatedEvent) {
			$this->createGroup($event->getGroup());
		} elseif ($event instanceof GroupDeletedEvent) {
			$this->deleteGroup($event->getGroup());
		}
	}

	/**
	 * Log add user to group event
	 */
	public function addUser(IGroup $group, IUser $user): void {
		$this->log('User "%s" added to group "%s"',
			[
				'group' => $group->getGID(),
				'user' => $user->getUID()
			],
			[
				'user', 'group'
			]
		);
	}

	/**
	 * Log remove user from group event
	 */
	public function removeUser(IGroup $group, IUser $user): void {
		$this->log('User "%s" removed from group "%s"',
			[
				'group' => $group->getGID(),
				'user' => $user->getUID()
			],
			[
				'user', 'group'
			]
		);
	}

	/**
	 * Log create group to group event
	 */
	public function createGroup(IGroup $group): void {
		$this->log('Group created: "%s"',
			[
				'group' => $group->getGID()
			],
			[
				'group'
			]
		);
	}

	/**
	 * Log delete group to group event
	 */
	public function deleteGroup(IGroup $group): void {
		$this->log('Group deleted: "%s"',
			[
				'group' => $group->getGID()
			],
			[
				'group'
			]
		);
	}
}
