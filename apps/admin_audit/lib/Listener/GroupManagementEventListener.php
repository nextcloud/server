<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AdminAudit\Listener;

use OCA\AdminAudit\Actions\Action;
use OCA\AdminAudit\Operation;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Group\Events\GroupChangedEvent;
use OCP\Group\Events\GroupCreatedEvent;
use OCP\Group\Events\GroupDeletedEvent;
use OCP\Group\Events\SubAdminAddedEvent;
use OCP\Group\Events\SubAdminRemovedEvent;
use OCP\Group\Events\UserAddedEvent;
use OCP\Group\Events\UserRemovedEvent;

/**
 * @template-implements IEventListener<UserAddedEvent|UserRemovedEvent|GroupCreatedEvent|GroupDeletedEvent|GroupChangedEvent|SubAdminAddedEvent|SubAdminRemovedEvent>
 */
class GroupManagementEventListener extends Action implements IEventListener {
	#[\Override]
	public function handle(Event $event): void {
		if ($event instanceof UserAddedEvent) {
			$this->userAdded($event);
		} elseif ($event instanceof UserRemovedEvent) {
			$this->userRemoved($event);
		} elseif ($event instanceof GroupCreatedEvent) {
			$this->groupCreated($event);
		} elseif ($event instanceof GroupDeletedEvent) {
			$this->groupDeleted($event);
		} elseif ($event instanceof GroupChangedEvent) {
			$this->groupChanged($event);
		} elseif ($event instanceof SubAdminAddedEvent) {
			$this->subAdminAdded($event);
		} elseif ($event instanceof SubAdminRemovedEvent) {
			$this->subAdminRemoved($event);
		}
	}

	private function userAdded(UserAddedEvent $event): void {
		$this->log(Operation::GroupMemberAdded, 'User "%s" added to group "%s"',
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
		$this->log(Operation::GroupMemberRemoved, 'User "%s" removed from group "%s"',
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
		$this->log(Operation::GroupCreated, 'Group created: "%s"',
			[
				'group' => $event->getGroup()->getGID()
			],
			[
				'group'
			]
		);
	}

	private function groupDeleted(GroupDeletedEvent $event): void {
		$this->log(Operation::GroupDeleted, 'Group deleted: "%s"',
			[
				'group' => $event->getGroup()->getGID()
			],
			[
				'group'
			]
		);
	}

	private function groupChanged(GroupChangedEvent $event): void {
		if ($event->getFeature() !== 'displayName') {
			return;
		}

		$this->log(Operation::GroupDisplayNameChanged, 'Display name of group "%s" was changed',
			[
				'group' => $event->getGroup()->getGID()
			],
			[
				'group'
			]
		);
	}

	private function subAdminAdded(SubAdminAddedEvent $event): void {
		$this->log(Operation::SubAdminAdded, 'User "%s" was added as subadmin of group "%s"',
			[
				'user' => $event->getUser()->getUID(),
				'group' => $event->getGroup()->getGID()
			],
			[
				'user', 'group'
			]
		);
	}

	private function subAdminRemoved(SubAdminRemovedEvent $event): void {
		$this->log(Operation::SubAdminRemoved, 'User "%s" was removed as subadmin of group "%s"',
			[
				'user' => $event->getUser()->getUID(),
				'group' => $event->getGroup()->getGID()
			],
			[
				'user', 'group'
			]
		);
	}
}
