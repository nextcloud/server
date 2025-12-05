<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Sharing\Listener;

use OC\Share20\DefaultShareProvider;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\EventDispatcher\IEventListener;
use OCP\Group\Events\UserRemovedEvent;
use OCP\Share\Events\UserRemovedFromShareEvent;

/** @template-implements IEventListener<UserRemovedEvent> */
class UserRemovedFromGroupListener implements IEventListener {

	public function __construct(
		private readonly IEventDispatcher $eventDispatcher,
		private readonly DefaultShareProvider $shareProvider,
	) {
	}

	public function handle(Event $event): void {
		if (!($event instanceof UserRemovedEvent)) {
			return;
		}

		$user = $event->getUser();
		$group = $event->getGroup();

		$shares = $this->shareProvider->getSharedWithGroup($group->getGID());

		foreach ($shares as $share) {
			$this->eventDispatcher->dispatchTyped(new UserRemovedFromShareEvent($share, $user));
		}
	}
}
