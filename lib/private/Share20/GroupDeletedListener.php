<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Share20;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Group\Events\GroupDeletedEvent;
use OCP\Share\IManager;

/**
 * @template-implements IEventListener<GroupDeletedEvent>
 */
class GroupDeletedListener implements IEventListener {
	public function __construct(
		protected IManager $shareManager,
	) {
	}

	public function handle(Event $event): void {
		if (!$event instanceof GroupDeletedEvent) {
			return;
		}

		$this->shareManager->groupDeleted($event->getGroup()->getGID());
	}
}
