<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2022 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Härtl <jus@bitgrid.net>
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
 */

namespace OC\Collaboration\Reference\File;

use OC\Files\Node\NonExistingFile;
use OC\Files\Node\NonExistingFolder;
use OCP\Collaboration\Reference\IReferenceManager;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\Events\Node\NodeDeletedEvent;
use OCP\Share\Events\ShareCreatedEvent;
use OCP\Share\Events\ShareDeletedEvent;

/** @template-implements IEventListener<Event|NodeDeletedEvent|ShareDeletedEvent|ShareCreatedEvent> */
class FileReferenceEventListener implements IEventListener {
	private IReferenceManager $manager;

	public function __construct(IReferenceManager $manager) {
		$this->manager = $manager;
	}

	public static function register(IEventDispatcher $eventDispatcher): void {
		$eventDispatcher->addServiceListener(NodeDeletedEvent::class, FileReferenceEventListener::class);
		$eventDispatcher->addServiceListener(ShareDeletedEvent::class, FileReferenceEventListener::class);
		$eventDispatcher->addServiceListener(ShareCreatedEvent::class, FileReferenceEventListener::class);
	}

	/**
	 * @inheritDoc
	 */
	public function handle(Event $event): void {
		if ($event instanceof NodeDeletedEvent) {
			if ($event->getNode() instanceof NonExistingFolder || $event->getNode() instanceof NonExistingFile) {
				return;
			}

			$this->manager->invalidateCache((string)$event->getNode()->getId());
		}
		if ($event instanceof ShareDeletedEvent) {
			$this->manager->invalidateCache((string)$event->getShare()->getNodeId());
		}
		if ($event instanceof ShareCreatedEvent) {
			$this->manager->invalidateCache((string)$event->getShare()->getNodeId());
		}
	}
}
