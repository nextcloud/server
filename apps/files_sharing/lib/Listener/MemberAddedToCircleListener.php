<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Sharing\Listener;

use OCA\Circles\Events\AddingCircleMemberEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\EventDispatcher\IEventListener;
use OCP\IUserManager;
use OCP\Share\Events\UserAddedToShareEvent;
use OCP\Share\IManager;
use OCP\Share\IShare;

/** @template-implements IEventListener<AddingCircleMemberEvent> */
class MemberAddedToCircleListener extends CircleListenerBase implements IEventListener {

	public function __construct(
		private readonly IManager $shareManager,
		private readonly IEventDispatcher $eventDispatcher,
		private readonly IUserManager $userManager,
	) {
		parent::__construct($this->userManager);
	}

	public function handle(Event $event): void {
		if (!($event instanceof AddingCircleMemberEvent)) {
			return;
		}

		$users = $this->usersFromMember($event->getMember());
		$circle = $event->getCircle();

		$shares = null;
		foreach ($users as $user) {
			if ($shares === null) {
				// we only need to get the shares for one user, the shares we're looking for are common between all users
				// todo: add a way to get shares by circle id
				$shares = $this->shareManager->getSharedWith($user->getUID(), IShare::TYPE_CIRCLE, null, -1);
			}

			foreach ($shares as $share) {
				// If this is not the new circle we can skip it
				if ($share->getSharedWith() !== $circle->getSingleId()) {
					continue;
				}

				$this->eventDispatcher->dispatchTyped(new UserAddedToShareEvent($share, $user));
			}
		}
	}
}
