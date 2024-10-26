<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
 * @template-implements IEventListener<UserAddedEvent|UserRemovedEvent|GroupCreatedEvent|GroupDeletedEvent>
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
