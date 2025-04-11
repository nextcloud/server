<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Listener;

use OCA\DAV\CardDAV\PhotoCache;
use OCA\DAV\Events\CardDeletedEvent;
use OCA\DAV\Events\CardUpdatedEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

/** @template-implements IEventListener<CardUpdatedEvent|CardDeletedEvent> */
class ClearPhotoCacheListener implements IEventListener {
	public function __construct(
		private PhotoCache $photoCache,
	) {
	}

	public function handle(Event $event): void {
		if ($event instanceof CardUpdatedEvent || $event instanceof CardDeletedEvent) {
			$cardData = $event->getCardData();

			$this->photoCache->delete($event->getAddressBookId(), $cardData['uri']);
		}
	}
}
