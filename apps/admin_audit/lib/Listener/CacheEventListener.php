<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AdminAudit\Listener;

use OCA\AdminAudit\Actions\Action;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\Cache\CacheEntryInsertedEvent;
use OCP\Files\Cache\CacheEntryRemovedEvent;

/**
 * @template-implements IEventListener<CacheEntryInsertedEvent|CacheEntryRemovedEvent>
 */
class CacheEventListener extends Action implements IEventListener {
	public function handle(Event $event): void {
		if ($event instanceof CacheEntryInsertedEvent) {
			$this->entryInserted($event);
		} elseif ($event instanceof CacheEntryRemovedEvent) {
			$this->entryRemoved($event);
		}
	}

	private function entryInserted(CacheEntryInsertedEvent $event): void {
		$this->log('Cache entry inserted for fileid "%1$d", path "%2$s" on storageid "%3$d"',
			[
				'fileid' => $event->getFileId(),
				'path' => $event->getPath(),
				'storageid' => $event->getStorageId(),
			],
			['fileid', 'path', 'storageid']
		);
	}

	private function entryRemoved(CacheEntryRemovedEvent $event): void {
		$this->log('Cache entry removed for fileid "%1$d", path "%2$s" on storageid "%3$d"',
			[
				'fileid' => $event->getFileId(),
				'path' => $event->getPath(),
				'storageid' => $event->getStorageId(),
			],
			['fileid', 'path', 'storageid']
		);
	}
}
