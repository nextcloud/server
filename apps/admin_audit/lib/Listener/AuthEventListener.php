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
use OCP\User\Events\BeforeUserLoggedInEvent;
use OCP\User\Events\BeforeUserLoggedOutEvent;
use OCP\User\Events\UserLoggedInEvent;
use OCP\User\Events\UserLoggedInWithCookieEvent;

/**
 * @template-implements UserManagementEventListener<BeforeUserLoggedInEvent|UserLoggedInWithCookieEvent|UserLoggedInEvent|BeforeUserLoggedOutEvent>
 */
class AuthEventListener extends Action implements IEventListener {
	public function handle(Event $event): void {
		if ($event instanceof BeforeUserLoggedInEvent) {
			$this->beforeUserLoggedIn($event);
		} elseif ($event instanceof UserLoggedInWithCookieEvent || $event instanceof UserLoggedInEvent) {
			$this->userLoggedIn($event);
		} elseif ($event instanceof BeforeUserLoggedOutEvent) {
			$this->beforeUserLogout($event);
		}
	}

	private function beforeUserLoggedIn(BeforeUserLoggedInEvent $event): void {
		$this->log(
			'Login attempt: "%s"',
			[
				'uid' => $event->getUsername()
			],
			[
				'uid',
			],
			true
		);
	}

	private function userLoggedIn(UserLoggedInWithCookieEvent|UserLoggedInEvent $event): void {
		$this->log(
			'Login successful: "%s"',
			[
				'uid' => $event->getUser()->getUID()
			],
			[
				'uid',
			],
			true
		);
	}

	private function beforeUserLogout(BeforeUserLoggedOutEvent $event): void {
		$this->log(
			'Logout occurred',
			[],
			[]
		);
	}
}
