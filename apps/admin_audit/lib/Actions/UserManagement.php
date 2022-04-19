<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2016 Lukas Reschke <lukas@statuscode.ch>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Carl Schwan <carl@carlschwan.eu>
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
use OCP\IUser;
use OCP\User\Events\PasswordUpdatedEvent;
use OCP\User\Events\UserChangedEvent;
use OCP\User\Events\UserCreatedEvent;
use OCP\User\Events\UserDeletedEvent;
use OCP\User\Events\UserIdAssignedEvent;
use OCP\User\Events\UserIdUnassignedEvent;

/**
 * Class UserManagement logs all user management related actions.
 *
 * @package OCA\AdminAudit\Actions
 */
class UserManagement extends Action implements IEventListener {
	public function handle(Event $event): void {
		if ($event instanceof UserCreatedEvent) {
			$this->create($event->getUser()->getUID());
		} elseif ($event instanceof UserDeletedEvent) {
			$this->delete($event->getUser()->getUID());
		} elseif ($event instanceof UserChangedEvent) {
			$this->change($event);
		} elseif ($event instanceof UserIdAssignedEvent) {
			$this->assign($event->getUserId());
		} elseif ($event instanceof UserIdUnassignedEvent) {
			$this->unassign($event->getUserId());
		} elseif ($event instanceof PasswordUpdatedEvent) {
			$this->setPassword($event->getUser());
		}
	}

	/**
	 * Log creation of users
	 *
	 * @param array $params
	 */
	public function create(string $userId): void {
		$this->log(
			'User created: "%s"',
			['uid' => $userId],
			[
				'uid',
			]
		);
	}

	/**
	 * Log assignments of users (typically user backends)
	 */
	public function assign(string $userId): void {
		$this->log(
		'UserID assigned: "%s"',
			[ 'uid' => $userId ],
			[ 'uid' ]
		);
	}

	/**
	 * Log deletion of users
	 */
	public function delete(string $userId): void {
		$this->log(
			'User deleted: "%s"',
			['uid' => $userId],
			[
				'uid',
			]
		);
	}

	/**
	 * Log unassignments of users (typically user backends, no data removed)
	 *
	 * @param string $uid
	 */
	public function unassign(string $uid): void {
		$this->log(
			'UserID unassigned: "%s"',
			[ 'uid' => $uid ],
			[ 'uid' ]
		);
	}

	/**
	 * Log enabling of users
	 *
	 * @param array $params
	 */
	public function change(UserChangedEvent $changedEvent): void {
		switch ($changedEvent->getFeature()) {
			case 'enabled':
				$this->log(
					$changedEvent->getValue() === true
						? 'User enabled: "%s"'
						: 'User disabled: "%s"',
					['user' => $changedEvent->getUser()->getUID()],
					[
						'user',
					]
				);
				break;
			case 'eMailAddress':
				$this->log(
					'Email address changed for user %s',
					['user' => $changedEvent->getUser()->getUID()],
					[
						'user',
					]
				);
				break;
		}
	}

	/**
	 * Logs changing of the user scope
	 *
	 * @param IUser $user
	 */
	public function setPassword(IUser $user): void {
		if ($user->getBackendClassName() === 'Database') {
			$this->log(
				'Password of user "%s" has been changed',
				[
					'user' => $user->getUID(),
				],
				[
					'user',
				]
			);
		}
	}
}
