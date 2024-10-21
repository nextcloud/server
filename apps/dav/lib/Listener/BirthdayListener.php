<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Listener;

use OCA\DAV\CalDAV\BirthdayService;
use OCA\DAV\Events\CardCreatedEvent;
use OCA\DAV\Events\CardDeletedEvent;
use OCA\DAV\Events\CardUpdatedEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

/** @template-implements IEventListener<CardCreatedEvent|CardUpdatedEvent|CardDeletedEvent> */
class BirthdayListener implements IEventListener {
	public function __construct(
		private BirthdayService $birthdayService,
	) {
	}

	public function handle(Event $event): void {
		if ($event instanceof CardCreatedEvent || $event instanceof CardUpdatedEvent) {
			$cardData = $event->getCardData();

			$this->birthdayService->onCardChanged($event->getAddressBookId(), $cardData['uri'], $cardData['carddata']);
		}

		if ($event instanceof CardDeletedEvent) {
			$cardData = $event->getCardData();
			$this->birthdayService->onCardDeleted($event->getAddressBookId(), $cardData['uri']);
		}
	}
}
