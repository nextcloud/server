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
use OCP\User\Events\PasswordUpdatedEvent;
use OCP\User\Events\UserChangedEvent;
use OCP\User\Events\UserCreatedEvent;
use OCP\User\Events\UserDeletedEvent;
use OCP\User\Events\UserIdAssignedEvent;
use OCP\User\Events\UserIdUnassignedEvent;

/**
 * @template-implements UserManagementEventListener<UserCreatedEvent|UserDeletedEvent|UserChangedEvent|PasswordUpdatedEvent|UserIdAssignedEvent|UserIdUnassignedEvent>
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
