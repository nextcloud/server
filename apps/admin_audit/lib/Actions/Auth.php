<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2016 Lukas Reschke <lukas@statuscode.ch>
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
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
namespace OCA\AdminAudit\Actions;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\User\Events\BeforeUserLoggedInEvent;
use OCP\User\Events\UserLoggedInEvent;
use OCP\User\Events\UserLoggedOutEvent;

/**
 * Class Auth logs all auth related actions
 *
 * @package OCA\AdminAudit\Actions
 */
class Auth extends Action implements IEventListener {
	public function handle(Event $event): void {
		if ($event instanceof BeforeUserLoggedInEvent) {
			$this->loginAttempt(['uid' => $event->getUsername()]);
		}

		if ($event instanceof UserLoggedInEvent) {
			$this->loginAttempt(['uid' => $event->getLoginName()]);
		}

		if ($event instanceof UserLoggedOutEvent) {
			$user = $event->getUser();
			if ($user) {
				$this->logout($user->getUID());
			}
		}
	}

	public function loginAttempt(array $params): void {
		$this->log(
			'Login attempt: "%s"',
			$params,
			[
				'uid',
			],
			true
		);
	}

	public function loginSuccessful(array $params): void {
		$this->log(
			'Login successful: "%s"',
			$params,
			[
				'uid',
			],
			true
		);
	}

	public function logout(string $userId): void {
		$this->log(
			'Logout occurred for "%s"',
			['uid' => $userId],
			['uid'],
			true
		);
	}
}
