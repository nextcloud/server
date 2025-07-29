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
use OCP\Files\Events\Node\NodeCreatedEvent;
use OCP\Files\Events\Node\NodeWrittenEvent;
use OCP\FilesMetadata\IFilesMetadataManager;
use Psr\Log\LoggerInterface;

/**
 * Handle file creation/modification events and initiate a new event related to the created/edited file.
 * The generated new event is broadcast in order to obtain file related metadata from other apps.
 * metadata will be stored in database.
 *
 * @template-implements IEventListener<NodeCreatedEvent|NodeWrittenEvent>
 */
class MetadataUpdate implements IEventListener {
	public function __construct(
		private IFilesMetadataManager $filesMetadataManager,
		private LoggerInterface $logger,
	) {
	}

	/**
	 * @param Event $event
	 */
	public function handle(Event $event): void {
		if (!($event instanceof NodeWrittenEvent)) {
			return;
		}

		try {
			$this->filesMetadataManager->refreshMetadata($event->getNode());
		} catch (Exception $e) {
			$this->logger->warning('issue while running MetadataUpdate', ['exception' => $e]);
		}
	}
}
