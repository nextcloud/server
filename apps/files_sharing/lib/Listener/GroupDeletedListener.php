<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Sharing\Listener;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Group\Events\GroupDeletedEvent;
use OCP\Share\IManager as IShareManager;

/** @template-implements IEventListener<GroupDeletedEvent> */
class GroupDeletedListener implements IEventListener {
	public function __construct(
		private IShareManager $shareManager,
	) {
	}

	public function handle(Event $event): void {
		if (!($event instanceof GroupDeletedEvent)) {
			return;
		}
		$group = $event->getGroup();
		$this->shareManager->groupDeleted($group->getGID());
	}
}
