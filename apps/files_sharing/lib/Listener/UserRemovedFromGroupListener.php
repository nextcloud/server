<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Sharing\Listener;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\EventDispatcher\IEventListener;
use OCP\Group\Events\UserRemovedEvent;
use OCP\Share\Events\UserRemovedFromShareEvent;
use OCP\Share\IManager;
use OCP\Share\IShare;

/** @template-implements IEventListener<UserRemovedEvent> */
class UserRemovedFromGroupListener implements IEventListener {

	public function __construct(
		private readonly IManager $shareManager,
		private readonly IEventDispatcher $eventDispatcher,
	) {
	}

	public function handle(Event $event): void {
		if (!($event instanceof UserRemovedEvent)) {
			return;
		}

		$user = $event->getUser();
		$group = $event->getGroup();

		// todo: add a way to get shares by group id
		$groupUsers = $group->getUsers();
		$groupUser = current($groupUsers);
		if ($groupUser) {
			// get the shares from a user still in the group
			$shares = $this->shareManager->getSharedWith($groupUser->getUID(), IShare::TYPE_GROUP, null, -1);
		} else {
			// if nobody is in the group anymore we current have to go through all shares
			$shares = $this->shareManager->getAllShares();
		}

		foreach ($shares as $share) {
			// If this is not the new group we can skip it
			if ($share->getShareType() === IShare::TYPE_GROUP && $share->getSharedWith() !== $group->getGID()) {
				continue;
			}

			$this->eventDispatcher->dispatchTyped(new UserRemovedFromShareEvent($share, $user));
		}
	}
}
