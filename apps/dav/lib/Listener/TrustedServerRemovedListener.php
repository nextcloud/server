<?php

declare(strict_types=1);

/**
 * @copyright 2022 Carl Schwan <carl@carlschwan.eu>
 *
 * @author Carl Schwan <carl@carlschwan.eu>
 *
 * @license AGPL-3.0-or-later
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
namespace OCA\DAV\Listener;

use OCA\DAV\CardDAV\CardDavBackend;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Federation\Events\TrustedServerRemovedEvent;

class TrustedServerRemovedListener implements IEventListener {
	private CardDavBackend $cardDavBackend;

	public function __construct(CardDavBackend $cardDavBackend) {
		$this->cardDavBackend = $cardDavBackend;
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
