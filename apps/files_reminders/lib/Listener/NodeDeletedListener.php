<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FilesReminders\Listener;

use OCA\FilesReminders\Service\ReminderService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\Events\Node\NodeDeletedEvent;

/** @template-implements IEventListener<NodeDeletedEvent> */
class NodeDeletedListener implements IEventListener {
	public function __construct(
		private ReminderService $reminderService,
	) {
	}

	public function handle(Event $event): void {
		if (!($event instanceof NodeDeletedEvent)) {
			return;
		}

		$node = $event->getNode();
		$this->reminderService->removeAllForNode($node);
	}
}
