<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Group\Listeners;

use OC\Group\Manager;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Group\Events\BeforeGroupDeletedEvent;
use OCP\Group\Events\BeforeUserAddedEvent;
use OCP\Group\Events\BeforeUserRemovedEvent;
use OCP\Group\Events\GroupDeletedEvent;
use OCP\Group\Events\UserAddedEvent;
use OCP\Group\Events\UserRemovedEvent;

/**
 * Invalidates before the change for listeners of the current request, and after
 * it in case a concurrent request repopulated the distributed cache meanwhile.
 *
 * @template-implements IEventListener<BeforeUserAddedEvent|UserAddedEvent|BeforeUserRemovedEvent|UserRemovedEvent|BeforeGroupDeletedEvent|GroupDeletedEvent>
 */
class MembershipCacheListener implements IEventListener {
	public function __construct(
		private readonly Manager $groupManager,
	) {
	}

	#[\Override]
	public function handle(Event $event): void {
		if ($event instanceof BeforeUserAddedEvent
			|| $event instanceof UserAddedEvent
			|| $event instanceof BeforeUserRemovedEvent
			|| $event instanceof UserRemovedEvent) {
			$this->groupManager->invalidateUserGroups($event->getUser()->getUID());
		} elseif ($event instanceof BeforeGroupDeletedEvent || $event instanceof GroupDeletedEvent) {
			$this->groupManager->invalidateGroup($event->getGroup()->getGID());
		}
	}
}
