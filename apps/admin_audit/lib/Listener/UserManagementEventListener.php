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
	#[\Override]
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
			Operation::UserCreated,
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
			Operation::UserDeleted,
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
				$enabled = $event->getValue() === true;
				$this->log(
					$enabled ? Operation::UserEnabled : Operation::UserDisabled,
					$enabled ? 'User enabled: "%s"' : 'User disabled: "%s"',
					['user' => $event->getUser()->getUID()],
					[
						'user',
					]
				);
				break;
			case 'eMailAddress':
				$this->log(
					Operation::UserEmailChanged,
					'Email address changed for user %s',
					['user' => $event->getUser()->getUID()],
					[
						'user',
					]
				);
				break;
			case 'displayName':
				$this->log(
					Operation::UserDisplayNameChanged,
					'Display name changed for user %s',
					['user' => $event->getUser()->getUID()],
					[
						'user',
					]
				);
				break;
			case 'quota':
				$this->log(
					Operation::UserQuotaChanged,
					'Quota of user "%s" was changed to "%s"',
					[
						'user' => $event->getUser()->getUID(),
						'quota' => (string)$event->getValue(),
					],
					[
						'user',
						'quota',
					]
				);
				break;
			case 'managers':
				$this->log(
					Operation::UserManagersChanged,
					'Managers of user "%s" were changed to "%s"',
					[
						'user' => $event->getUser()->getUID(),
						'managers' => implode(', ', (array)$event->getValue()),
					],
					[
						'user',
						'managers',
					]
				);
				break;
		}
	}

	private function passwordUpdated(PasswordUpdatedEvent $event): void {
		$this->log(
			Operation::UserPasswordChanged,
			'Password of user "%s" has been changed',
			[
				'user' => $event->getUser()->getUID(),
				'backend' => $event->getUser()->getBackendClassName(),
			],
			[
				'user',
			]
		);
	}

	/**
	 * Log assignments of users (typically user backends)
	 */
	private function userIdAssigned(UserIdAssignedEvent $event): void {
		$this->log(
			Operation::UserIdAssigned,
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
			Operation::UserIdUnassigned,
			'UserID unassigned: "%s"',
			[ 'uid' => $event->getUserId() ],
			[ 'uid' ]
		);
	}
}
