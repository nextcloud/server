<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Listener;

use OCA\DAV\CardDAV\CardDavBackend;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Federation\Events\TrustedServerRemovedEvent;

/** @template-implements IEventListener<TrustedServerRemovedEvent> */
class TrustedServerRemovedListener implements IEventListener {
	public function __construct(
		private CardDavBackend $cardDavBackend,
	) {
	}

	public function handle(Event $event): void {
		if (!$event instanceof TrustedServerRemovedEvent) {
			return;
		}
		$addressBookUri = $event->getUrlHash();
		$addressBook = $this->cardDavBackend->getAddressBooksByUri('principals/system/system', $addressBookUri);
		if (!is_null($addressBook)) {
			$this->cardDavBackend->deleteAddressBook($addressBook['id']);
		}
	}
}
