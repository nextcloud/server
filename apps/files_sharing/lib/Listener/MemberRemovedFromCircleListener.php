<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Sharing\Listener;

use OCA\Circles\Events\CircleMemberRemovedEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\EventDispatcher\IEventListener;
use OCP\IUserManager;
use OCP\Share\Events\UserRemovedFromShareEvent;
use OCP\Share\IManager;
use OCP\Share\IShare;

/** @template-implements IEventListener<CircleMemberRemovedEvent> */
class MemberRemovedFromCircleListener extends CircleListenerBase implements IEventListener {

	public function __construct(
		private readonly IManager $shareManager,
		private readonly IEventDispatcher $eventDispatcher,
		private readonly IUserManager $userManager,
	) {
		parent::__construct($this->userManager);
	}

	public function handle(Event $event): void {
		if (!($event instanceof CircleMemberRemovedEvent)) {
			return;
		}

		$circle = $event->getCircle();

		$circleMembers = iterator_to_array($this->usersFromCircle($circle));
		// todo: add a way to get shares by circle id
		if (count($circleMembers)) {
			$user = current($circleMembers);
			// get the shares from a user still in the circle
			$shares = $this->shareManager->getSharedWith($user->getUID(), IShare::TYPE_CIRCLE, null, -1);
		} else {
			// if nobody is in the circle anymore we current have to go through all shares
			// todo: add a way to get shares by group id
			$shares = $this->shareManager->getAllShares();
		}

		foreach ($shares as $share) {
			// If this is not the new group we can skip it
			if ($share->getShareType() === IShare::TYPE_CIRCLE && $share->getSharedWith() !== $circle->getSingleId()) {
				continue;
			}

			foreach ($this->usersFromMember($event->getMember()) as $user) {
				if (!isset($circleMembers[$user->getUID()])) {
					$this->eventDispatcher->dispatchTyped(new UserRemovedFromShareEvent($share, $user));
				}
			}
		}
	}
}
