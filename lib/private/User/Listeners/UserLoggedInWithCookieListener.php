<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\User\Listeners;

use OC\User\LastInteractiveLogin;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\User\Events\UserLoggedInWithCookieEvent;

/**
 * @template-implements IEventListener<UserLoggedInWithCookieEvent>
 */
class UserLoggedInWithCookieListener implements IEventListener {
	public function __construct(
		private LastInteractiveLogin $lastInteractiveLogin,
	) {
	}

	#[\Override]
	public function handle(Event $event): void {
		if (!($event instanceof UserLoggedInWithCookieEvent)) {
			return;
		}

		$this->lastInteractiveLogin->record($event->getUser());
	}
}
