<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC;

use OCP\Constants;
use OCP\Contacts\IManager;
use OCP\IAddressBook;

class ContactsManager implements IManager {
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

	public function isEnabled(): bool {
		return !empty($this->addressBooks) || !empty($this->addressBookLoaders);
	}

	public function registerAddressBook(IAddressBook $addressBook) {
		$this->addressBooks[$addressBook->getKey()] = $addressBook;
	}

	public function unregisterAddressBook(IAddressBook $addressBook) {
		unset($this->addressBooks[$addressBook->getKey()]);
	}

	public function getUserAddressBooks(): array {
		$this->loadAddressBooks();
		return $this->addressBooks;
	}

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
