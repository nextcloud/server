<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\FilesMetadata\Listener;

use Exception;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\Cache\CacheEntriesRemovedEvent;
use OCP\FilesMetadata\IFilesMetadataManager;
use Psr\Log\LoggerInterface;

/**
 * Handle file deletion event and remove stored metadata related to the deleted file
 *
 * @template-implements IEventListener<CacheEntriesRemovedEvent>
 */
class MetadataDelete implements IEventListener {
	public function __construct(
		private IFilesMetadataManager $filesMetadataManager,
		private LoggerInterface $logger,
	) {
	}

	public function handle(Event $event): void {
		if (!($event instanceof CacheEntriesRemovedEvent)) {
			return;
		}

		$entries = $event->getCacheEntryRemovedEvents();
		$storageToFileIds = [];

		foreach ($entries as $entry) {
			try {
				$storageToFileIds[$entry->getStorageId()] ??= [];
				$storageToFileIds[$entry->getStorageId()][] = $entry->getFileId();
			} catch (Exception $e) {
				$this->logger->warning('issue while running MetadataDelete', ['exception' => $e]);
			}
		}

		try {
			foreach ($storageToFileIds as $storageId => $fileIds) {
				$this->filesMetadataManager->deleteMetadataForFiles($storageId, $fileIds);
			}
		} catch (Exception $e) {
			$this->logger->warning('issue while running MetadataDelete', ['exception' => $e]);
		}
	}
}
