<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Maxence Lange <maxence@artificial-owl.com>
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
use OCP\Files\Events\Node\NodeDeletedEvent;
use OCP\FilesMetadata\IFilesMetadataManager;

/**
 * Handle file deletion event and remove stored metadata related to the deleted file
 */
class MetadataDelete implements IEventListener {
	public function __construct(
		private IFilesMetadataManager $filesMetadataManager,
	) {
	}

	/**
	 * @param Event $event
	 */
	public function handle(Event $event): void {
		if (!($event instanceof NodeDeletedEvent)) {
			return;
		}

		try {
			$nodeId = (int)$event->getNode()->getId();
			if ($nodeId > 0) {
				$this->filesMetadataManager->deleteMetadata($nodeId);
			}
		} catch (Exception $e) {
		}
	}
}
