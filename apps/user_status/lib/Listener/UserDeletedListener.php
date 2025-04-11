<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\UserStatus\Listener;

use OCA\UserStatus\Service\StatusService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\User\Events\UserDeletedEvent;

/**
 * Class UserDeletedListener
 *
 * @package OCA\UserStatus\Listener
 * @template-implements IEventListener<UserDeletedEvent>
 */
class UserDeletedListener implements IEventListener {

	/**
	 * UserDeletedListener constructor.
	 *
	 * @param StatusService $service
	 */
	public function __construct(
		private StatusService $service,
	) {
	}


	/**
	 * @inheritDoc
	 */
	public function handle(Event $event): void {
		if (!($event instanceof UserDeletedEvent)) {
			// Unrelated
			return;
		}

		$user = $event->getUser();
		$this->service->removeUserStatus($user->getUID());
	}
}
