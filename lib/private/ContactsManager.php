<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Joas Schilling <coding@schilljs.com>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Thomas Citharel <nextcloud@tcit.fr>
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
namespace OC;

use OCP\Constants;
use OCP\Contacts\IManager;
use OCP\IAddressBook;

class ContactsManager implements IManager {
	/**
	 * This function is used to search and find contacts within the users address books.
	 * In case $pattern is empty all contacts will be returned.
	 *
	 * @param string $pattern which should match within the $searchProperties
	 * @param array $searchProperties defines the properties within the query pattern should match
	 * @param array $options = array() to define the search behavior
	 * 	- 'types' boolean (since 15.0.0) If set to true, fields that come with a TYPE property will be an array
	 *    example: ['id' => 5, 'FN' => 'Thomas Tanghus', 'EMAIL' => ['type => 'HOME', 'value' => 'g@h.i']]
	 * 	- 'escape_like_param' - If set to false wildcards _ and % are not escaped
	 * 	- 'limit' - Set a numeric limit for the search results
	 * 	- 'offset' - Set the offset for the limited search results
	 * 	- 'enumeration' - (since 23.0.0) Whether user enumeration on system address book is allowed
	 * 	- 'fullmatch' - (since 23.0.0) Whether matching on full detail in system address book is allowed
	 * 	- 'strict_search' - (since 23.0.0) Whether the search pattern is full string or partial search
	 * @psalm-param array{types?: bool, escape_like_param?: bool, limit?: int, offset?: int, enumeration?: bool, fullmatch?: bool, strict_search?: bool} $options
	 * @return array an array of contacts which are arrays of key-value-pairs
	 */
	public function search($pattern, $searchProperties = [], $options = []) {
		$this->loadAddressBooks();
		$result = [];
		foreach ($this->addressBooks as $addressBook) {
			$searchOptions = $options;
			$strictSearch = array_key_exists('strict_search', $options) && $options['strict_search'] === true;

			if ($addressBook->isSystemAddressBook()) {
				$enumeration = !\array_key_exists('enumeration', $options) || $options['enumeration'] !== false;
				$fullMatch = !\array_key_exists('fullmatch', $options) || $options['fullmatch'] !== false;

				if (!$enumeration && !$fullMatch) {
					// No access to system address book AND no full match allowed
					continue;
				}

				if ($strictSearch) {
					$searchOptions['wildcard'] = false;
				} else {
					$searchOptions['wildcard'] = $enumeration;
				}
			} else {
				$searchOptions['wildcard'] = !$strictSearch;
			}

			$r = $addressBook->search($pattern, $searchProperties, $searchOptions);
			$contacts = [];
			foreach ($r as $c) {
				$c['addressbook-key'] = $addressBook->getKey();
				$contacts[] = $c;
			}
			$result = array_merge($result, $contacts);
		}

		return $result;
	}

	/**
	 * This function can be used to delete the contact identified by the given id
	 *
	 * @param int $id the unique identifier to a contact
	 * @param string $addressBookKey identifier of the address book in which the contact shall be deleted
	 * @return bool successful or not
	 */
	public function delete($id, $addressBookKey) {
		$addressBook = $this->getAddressBook($addressBookKey);
		if (!$addressBook) {
			return false;
		}

		if ($addressBook->getPermissions() & Constants::PERMISSION_DELETE) {
			return $addressBook->delete($id);
		}

		return false;
	}

	/**
	 * This function is used to create a new contact if 'id' is not given or not present.
	 * Otherwise the contact will be updated by replacing the entire data set.
	 *
	 * @param array $properties this array if key-value-pairs defines a contact
	 * @param string $addressBookKey identifier of the address book in which the contact shall be created or updated
	 * @return ?array representing the contact just created or updated
	 */
	public function createOrUpdate($properties, $addressBookKey) {
		$addressBook = $this->getAddressBook($addressBookKey);
		if (!$addressBook) {
			return null;
		}

		if ($addressBook->getPermissions() & Constants::PERMISSION_CREATE) {
			return $addressBook->createOrUpdate($properties);
		}

		return null;
	}

	/**
	 * Check if contacts are available (e.g. contacts app enabled)
	 *
	 * @return bool true if enabled, false if not
	 */
	public function isEnabled(): bool {
		return !empty($this->addressBooks) || !empty($this->addressBookLoaders);
	}

	/**
	 * @param IAddressBook $addressBook
	 */
	public function registerAddressBook(IAddressBook $addressBook) {
		$this->addressBooks[$addressBook->getKey()] = $addressBook;
	}

	/**
	 * @param IAddressBook $addressBook
	 */
	public function unregisterAddressBook(IAddressBook $addressBook) {
		unset($this->addressBooks[$addressBook->getKey()]);
	}

	/**
	 * Return a list of the user's addressbooks
	 *
	 * @return IAddressBook[]
	 * @since 16.0.0
	 */
	public function getUserAddressBooks(): array {
		$this->loadAddressBooks();
		return $this->addressBooks;
	}

	/**
	 * removes all registered address book instances
	 */
	public function clear() {
		$this->addressBooks = [];
		$this->addressBookLoaders = [];
	}

	/**
	 * @var IAddressBook[] which holds all registered address books
	 */
	private $addressBooks = [];

	/**
	 * @var \Closure[] to call to load/register address books
	 */
	private $addressBookLoaders = [];

	/**
	 * In order to improve lazy loading a closure can be registered which will be called in case
	 * address books are actually requested
	 *
	 * @param \Closure $callable
	 */
	public function register(\Closure $callable) {
		$this->addressBookLoaders[] = $callable;
	}

	/**
	 * Get (and load when needed) the address book for $key
	 */
	protected function getAddressBook(string $addressBookKey): ?IAddressBook {
		$this->loadAddressBooks();
		if (!array_key_exists($addressBookKey, $this->addressBooks)) {
			return null;
		}

		return $this->addressBooks[$addressBookKey];
	}

	/**
	 * Load all address books registered with 'register'
	 */
	protected function loadAddressBooks() {
		foreach ($this->addressBookLoaders as $callable) {
			$callable($this);
		}
		$this->addressBookLoaders = [];
	}
}
