<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Tobia De Koninck <tobia@ledfan.be>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\DAV\CardDAV;

use OCP\Contacts\IManager;
use OCP\IL10N;
use OCP\IURLGenerator;

class ContactsManager {
	private CardDavBackend $backend;
	private IL10N $l10n;

	public function __construct(CardDavBackend $backend, IL10N $l10n) {
		$this->backend = $backend;
		$this->l10n = $l10n;
	}

	public function setupContactsProvider(IManager $cm, string $userId, IURLGenerator $urlGenerator) {
		$addressBooks = $this->backend->getAddressBooksForUser("principals/users/$userId");
		$this->register($cm, $addressBooks, $urlGenerator);
		$this->setupSystemContactsProvider($cm, $urlGenerator);
	}

	public function setupSystemContactsProvider(IManager $cm, IURLGenerator $urlGenerator) {
		$addressBooks = $this->backend->getAddressBooksForUser("principals/system/system");
		$this->register($cm, $addressBooks, $urlGenerator);
	}

	private function register(IManager $cm, array $addressBooks, IURLGenerator $urlGenerator) {
		foreach ($addressBooks as $addressBookInfo) {
			$addressBook = new AddressBook($this->backend, $addressBookInfo, $this->l10n);
			$cm->registerAddressBook(
				new AddressBookImpl(
					$addressBook,
					$addressBookInfo,
					$this->backend,
					$urlGenerator
				)
			);
		}
	}
}
