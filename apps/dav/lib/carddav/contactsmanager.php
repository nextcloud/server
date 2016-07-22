<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\DAV\CardDAV;

use OCP\Contacts\IManager;

class ContactsManager {

	/**
	 * ContactsManager constructor.
	 *
	 * @param CardDavBackend $backend
	 */
	public function __construct(CardDavBackend $backend) {
		$this->backend = $backend;
	}

	/**
	 * @param IManager $cm
	 * @param string $userId
	 */
	public function setupContactsProvider(IManager $cm, $userId) {
		$addressBooks = $this->backend->getAddressBooksForUser("principals/users/$userId");
		$this->register($cm, $addressBooks);
		$addressBooks = $this->backend->getAddressBooksForUser("principals/system/system");
		$this->register($cm, $addressBooks);
	}

	/**
	 * @param IManager $cm
	 * @param $addressBooks
	 */
	private function register(IManager $cm, $addressBooks) {
		foreach ($addressBooks as $addressBookInfo) {
			$addressBook = new \OCA\DAV\CardDAV\AddressBook($this->backend, $addressBookInfo);
			$cm->registerAddressBook(
				new AddressBookImpl(
					$addressBook,
					$addressBookInfo,
					$this->backend
				)
			);
		}
	}

}
