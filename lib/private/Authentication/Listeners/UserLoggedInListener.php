<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Authentication\Listeners;

use OC\Authentication\Token\Manager;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\User\Events\PostLoginEvent;

/**
 * @template-implements IEventListener<\OCP\User\Events\PostLoginEvent>
 */
class UserLoggedInListener implements IEventListener {

	public function __construct(
		private Manager $manager,
	) {
	}

	public function handle(Event $event): void {
		if (!$event instanceof PostLoginEvent) {
			return;
		}

		// prevent setting an empty pw as result of pw-less-login
		if ($event->getPassword() === '') {
			return;
		}

		// If this is already a token login there is nothing to do
		if ($event->isTokenLogin()) {
			return;
		}

		$uid = $event->getUser()->getUID();
		$this->manager->updatePasswords($uid, $event->getPassword());
	}
}
