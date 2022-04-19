<?php

declare(strict_types=1);

/**
 * @copyright 2022 Carl Schwan <carl@carlschwan.eu>
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


namespace OCA\Files_Trashbin\Listener;

use OCA\Files_Trashbin\Storage;
use OCA\Files_Trashbin\Trashbin;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\Events\BeforeFileSystemSetupEvent;
use OCP\Files\Events\Node\NodeWrittenEvent;
use OCP\User\Events\BeforeUserDeletedEvent;

/** @template-implements IEventListener<NodeWrittenEvent|BeforeUserDeletedEvent|BeforeFileSystemSetupEvent> */
class EventListener implements IEventListener {
	private ?string $userId;

	public function __construct(?string $userId = null) {
		$this->userId = $userId;
	}

	public function handle(Event $event): void {
		if ($event instanceof NodeWrittenEvent) {
			// Resize trash
			if (!empty($this->userId)) {
				Trashbin::resizeTrash($this->userId);
			}
		}

		// Clean up user specific settings if user gets deleted
		if ($event instanceof BeforeUserDeletedEvent) {
			Trashbin::deleteUser($event->getUser()->getUID());
		}

		if ($event instanceof BeforeFileSystemSetupEvent) {
			Storage::setupStorage();
		}
	}
}
