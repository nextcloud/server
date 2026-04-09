<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Collaboration\Reference\File;

use OCP\Collaboration\Reference\IReferenceManager;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\Events\Node\NodeDeletedEvent;
use OCP\Files\Events\Node\NodeRenamedEvent;
use OCP\Files\NotFoundException;
use OCP\Share\Events\ShareCreatedEvent;
use OCP\Share\Events\ShareDeletedEvent;
use Psr\Log\LoggerInterface;

/** @template-implements IEventListener<Event|NodeDeletedEvent|ShareDeletedEvent|ShareCreatedEvent> */
class FileReferenceEventListener implements IEventListener {
	public function __construct(
		private IReferenceManager $manager,
		private LoggerInterface $logger,
	) {
	}

	public static function register(IEventDispatcher $eventDispatcher): void {
		$eventDispatcher->addServiceListener(NodeDeletedEvent::class, FileReferenceEventListener::class);
		$eventDispatcher->addServiceListener(NodeRenamedEvent::class, FileReferenceEventListener::class);
		$eventDispatcher->addServiceListener(ShareDeletedEvent::class, FileReferenceEventListener::class);
		$eventDispatcher->addServiceListener(ShareCreatedEvent::class, FileReferenceEventListener::class);
	}

	/**
	 * @inheritDoc
	 */
	public function handle(Event $event): void {
		if ($event instanceof NodeDeletedEvent) {
			try {
				$this->manager->invalidateCache((string)$event->getNode()->getId());
			} catch (NotFoundException $e) {
				// Non existing node might not have an id
				$this->logger->debug('Could not invalidate reference cache for deleted node', ['exception' => $e]);
			}
		}
		if ($event instanceof NodeRenamedEvent) {
			$this->manager->invalidateCache((string)$event->getTarget()->getId());
		}
		if ($event instanceof ShareDeletedEvent) {
			$this->manager->invalidateCache((string)$event->getShare()->getNodeId());
		}
		if ($event instanceof ShareCreatedEvent) {
			$this->manager->invalidateCache((string)$event->getShare()->getNodeId());
		}
	}
}
