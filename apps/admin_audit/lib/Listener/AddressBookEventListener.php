<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AdminAudit\Listener;

use OCA\AdminAudit\Actions\Action;
use OCA\AdminAudit\Operation;
use OCA\DAV\Events\AddressBookCreatedEvent;
use OCA\DAV\Events\AddressBookDeletedEvent;
use OCA\DAV\Events\AddressBookShareUpdatedEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use Override;

/**
 * @template-implements IEventListener<AddressBookCreatedEvent|AddressBookDeletedEvent|AddressBookShareUpdatedEvent>
 */
class AddressBookEventListener extends Action implements IEventListener {
	#[Override]
	public function handle(Event $event): void {
		if ($event instanceof AddressBookCreatedEvent) {
			$this->log(Operation::AddressBookCreated, 'Address book "%s" of "%s" was created with URI "%s"',
				[
					'addressBookId' => $event->getAddressBookId(),
					'owner' => $event->getAddressBookData()['principaluri'] ?? null,
					'uri' => $event->getAddressBookData()['uri'] ?? null,
				],
				['addressBookId', 'owner', 'uri']
			);
		} elseif ($event instanceof AddressBookDeletedEvent) {
			$this->log(Operation::AddressBookDeleted, 'Address book "%s" of "%s" was deleted, removing the shares with "%s"',
				[
					'addressBookId' => $event->getAddressBookId(),
					'owner' => $event->getAddressBookData()['principaluri'] ?? null,
					'sharees' => CalendarEventListener::implodeShareHrefs($event->getShares()),
				],
				['addressBookId', 'owner', 'sharees']
			);
		} elseif ($event instanceof AddressBookShareUpdatedEvent) {
			$this->log(Operation::AddressBookShareUpdated, 'Shares of address book "%s" of "%s" were updated, added: "%s", removed: "%s"',
				[
					'addressBookId' => $event->getAddressBookId(),
					'owner' => $event->getAddressBookData()['principaluri'] ?? null,
					'added' => CalendarEventListener::implodeShareHrefs($event->getAdded()),
					'removed' => implode(', ', $event->getRemoved()),
				],
				['addressBookId', 'owner', 'added', 'removed']
			);
		}
	}
}
