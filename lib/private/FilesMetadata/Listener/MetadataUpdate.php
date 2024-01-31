<?php

declare(strict_types=1);
/**
 * @copyright 2023 Maxence Lange <maxence@artificial-owl.com>
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
		private LoggerInterface $logger
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
