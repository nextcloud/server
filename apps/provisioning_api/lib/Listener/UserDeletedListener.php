<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Provisioning_API\Listener;

use OC\KnownUser\KnownUserService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\User\Events\UserDeletedEvent;

/** @template-implements IEventListener<UserDeletedEvent> */
class UserDeletedListener implements IEventListener {

	public function __construct(
		private KnownUserService $service,
	) {
	}

	public function handle(Event $event): void {
		if (!($event instanceof UserDeletedEvent)) {
			// Unrelated
			return;
		}

		$user = $event->getUser();

		// Delete all entries of this user
		$this->service->deleteKnownTo($user->getUID());

		// Delete all entries that other users know this user
		$this->service->deleteByContactUserId($user->getUID());
	}
}
