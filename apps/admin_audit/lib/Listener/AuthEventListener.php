<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AdminAudit\Listener;

use OCA\AdminAudit\Actions\Action;
use OCP\Authentication\Events\AnyLoginFailedEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\User\Events\BeforeUserLoggedInEvent;
use OCP\User\Events\BeforeUserLoggedOutEvent;
use OCP\User\Events\UserLoggedInEvent;
use OCP\User\Events\UserLoggedInWithCookieEvent;

/**
 * @template-implements IEventListener<BeforeUserLoggedInEvent|UserLoggedInWithCookieEvent|UserLoggedInEvent|BeforeUserLoggedOutEvent|AnyLoginFailedEvent>
 */
class AuthEventListener extends Action implements IEventListener {
	public function handle(Event $event): void {
		if ($event instanceof BeforeUserLoggedInEvent) {
			$this->beforeUserLoggedIn($event);
		} elseif ($event instanceof UserLoggedInWithCookieEvent || $event instanceof UserLoggedInEvent) {
			$this->userLoggedIn($event);
		} elseif ($event instanceof BeforeUserLoggedOutEvent) {
			$this->beforeUserLogout($event);
		} elseif ($event instanceof AnyLoginFailedEvent) {
			$this->anyLoginFailed($event);
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

	private function anyLoginFailed(AnyLoginFailedEvent $event): void {
		$this->log(
			'Login failed: "%s"',
			[
				'loginName' => $event->getLoginName()
			],
			[
				'loginName',
			],
			true
		);
	}
}
