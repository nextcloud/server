<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AdminAudit\Listener;

use OCA\AdminAudit\Actions\Action;
use OCA\Files_Trashbin\Events\BeforeNodeDeletedEvent;
use OCA\Files_Trashbin\Events\NodeRestoredEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

/**
 * @template-implements IEventListener<BeforeNodeDeletedEvent|NodeRestoredEvent>
 */
class TrashbinEventListener extends Action implements IEventListener {

	public function handle(Event $event): void {
		if ($event instanceof BeforeNodeDeletedEvent) {
			$this->log('File "%s" deleted from trash bin.',
				['path' => $event->getSource()->getPath()], ['path']
			);
		} elseif ($event instanceof NodeRestoredEvent) {
			$this->log('File "%s" restored from trash bin.',
				['path' => $event->getTarget()->getPath()], ['path']
			);
		}
	}
}
