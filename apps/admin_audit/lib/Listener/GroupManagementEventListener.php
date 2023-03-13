<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Thomas Citharel <nextcloud@tcit.fr>
 *
 * @author Thomas Citharel <nextcloud@tcit.fr>
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
namespace OCA\AdminAudit\Listener;

use OCA\AdminAudit\Actions\Action;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Group\Events\GroupCreatedEvent;
use OCP\Group\Events\GroupDeletedEvent;
use OCP\Group\Events\UserAddedEvent;
use OCP\Group\Events\UserRemovedEvent;

/**
 * @template-implements UserManagementEventListener<UserAddedEvent|UserRemovedEvent|GroupCreatedEvent|GroupDeletedEvent>
 */
class GroupManagementEventListener extends Action implements IEventListener {
	public function handle(Event $event): void {
		if ($event instanceof UserAddedEvent) {
			$this->userAdded($event);
		} elseif ($event instanceof UserRemovedEvent) {
			$this->userRemoved($event);
		} elseif ($event instanceof GroupCreatedEvent) {
			$this->groupCreated($event);
		} elseif ($event instanceof GroupDeletedEvent) {
			$this->groupDeleted($event);
		}
	}

	private function userAdded(UserAddedEvent $event): void {
		$this->log('User "%s" added to group "%s"',
			[
				'group' => $event->getGroup()->getGID(),
				'user' => $event->getUser()->getUID()
			],
			[
				'user', 'group'
			]
		);
	}

	private function userRemoved(UserRemovedEvent $event): void {
		$this->log('User "%s" removed from group "%s"',
			[
				'group' => $event->getGroup()->getGID(),
				'user' => $event->getUser()->getUID()
			],
			[
				'user', 'group'
			]
		);
	}

	private function groupCreated(GroupCreatedEvent $event): void {
		$this->log('Group created: "%s"',
			[
				'group' => $event->getGroup()->getGID()
			],
			[
				'group'
			]
		);
	}

	private function groupDeleted(GroupDeletedEvent $event): void {
		$this->log('Group deleted: "%s"',
			[
				'group' => $event->getGroup()->getGID()
			],
			[
				'group'
			]
		);
	}
}
