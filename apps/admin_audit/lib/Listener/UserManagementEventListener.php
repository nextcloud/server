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
use OCP\User\Events\PasswordUpdatedEvent;
use OCP\User\Events\UserChangedEvent;
use OCP\User\Events\UserCreatedEvent;
use OCP\User\Events\UserDeletedEvent;
use OCP\User\Events\UserIdAssignedEvent;
use OCP\User\Events\UserIdUnassignedEvent;

/**
 * @template-implements IEventListener<UserCreatedEvent|UserDeletedEvent|UserChangedEvent|PasswordUpdatedEvent|UserIdAssignedEvent|UserIdUnassignedEvent>
 */
class UserManagementEventListener extends Action implements IEventListener {
	public function handle(Event $event): void {
		if ($event instanceof UserCreatedEvent) {
			$this->userCreated($event);
		} elseif ($event instanceof UserDeletedEvent) {
			$this->userDeleted($event);
		} elseif ($event instanceof UserChangedEvent) {
			$this->userChanged($event);
		} elseif ($event instanceof PasswordUpdatedEvent) {
			$this->passwordUpdated($event);
		} elseif ($event instanceof UserIdAssignedEvent) {
			$this->userIdAssigned($event);
		} elseif ($event instanceof UserIdUnassignedEvent) {
			$this->userIdUnassigned($event);
		}
	}

	private function userCreated(UserCreatedEvent $event): void {
		$this->log(
			'User created: "%s"',
			[
				'uid' => $event->getUid()
			],
			[
				'uid',
			]
		);
	}

	private function userDeleted(UserDeletedEvent $event): void {
		$this->log(
			'User deleted: "%s"',
			[
				'uid' => $event->getUser()->getUID()
			],
			[
				'uid',
			]
		);
	}

	private function userChanged(UserChangedEvent $event): void {
		switch ($event->getFeature()) {
			case 'enabled':
				$this->log(
					$event->getValue() === true
						? 'User enabled: "%s"'
						: 'User disabled: "%s"',
					['user' => $event->getUser()->getUID()],
					[
						'user',
					]
				);
				break;
			case 'eMailAddress':
				$this->log(
					'Email address changed for user %s',
					['user' => $event->getUser()->getUID()],
					[
						'user',
					]
				);
				break;
		}
	}

	private function passwordUpdated(PasswordUpdatedEvent $event): void {
		if ($event->getUser()->getBackendClassName() === 'Database') {
			$this->log(
				'Password of user "%s" has been changed',
				[
					'user' => $event->getUser()->getUID(),
				],
				[
					'user',
				]
			);
		}
	}

	/**
	 * Log assignments of users (typically user backends)
	 */
	private function userIdAssigned(UserIdAssignedEvent $event): void {
		$this->log(
			'UserID assigned: "%s"',
			[ 'uid' => $event->getUserId() ],
			[ 'uid' ]
		);
	}

	/**
	 * Log unassignments of users (typically user backends, no data removed)
	 */
	private function userIdUnassigned(UserIdUnassignedEvent $event): void {
		$this->log(
			'UserID unassigned: "%s"',
			[ 'uid' => $event->getUserId() ],
			[ 'uid' ]
		);
	}
}
