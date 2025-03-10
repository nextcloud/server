<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\CardDAV;

use OCA\DAV\Db\PropertyMapper;
use OCP\Contacts\IManager;
use OCP\IL10N;
use OCP\IURLGenerator;

class ContactsManager {
	/** @var CardDavBackend  */
	private $backend;

	/** @var IL10N  */
	private $l10n;

	/** @var PropertyMapper  */
	private $propertyMapper;

	/**
	 * ContactsManager constructor.
	 *
	 * @param CardDavBackend $backend
	 * @param IL10N $l10n
	 */
	public function __construct(CardDavBackend $backend, IL10N $l10n, PropertyMapper $propertyMapper) {
		$this->backend = $backend;
		$this->l10n = $l10n;
		$this->propertyMapper = $propertyMapper;
	}

	/**
	 * @param IManager $cm
	 * @param string $userId
	 * @param IURLGenerator $urlGenerator
	 */
	public function setupContactsProvider(IManager $cm, $userId, IURLGenerator $urlGenerator) {
		$addressBooks = $this->backend->getAddressBooksForUser("principals/users/$userId");
		$this->register($cm, $addressBooks, $urlGenerator, $userId);
		$this->setupSystemContactsProvider($cm, $userId, $urlGenerator);
	}

	/**
	 * @param IManager $cm
	 * @param ?string $userId
	 * @param IURLGenerator $urlGenerator
	 */
	public function setupSystemContactsProvider(IManager $cm, ?string $userId, IURLGenerator $urlGenerator) {
		$addressBooks = $this->backend->getAddressBooksForUser("principals/system/system");
		$this->register($cm, $addressBooks, $urlGenerator, $userId);
	}

	/**
	 * @param IManager $cm
	 * @param $addressBooks
	 * @param IURLGenerator $urlGenerator
	 * @param ?string $userId
	 */
	private function register(IManager $cm, $addressBooks, $urlGenerator, ?string $userId) {
		foreach ($addressBooks as $addressBookInfo) {
			$addressBook = new \OCA\DAV\CardDAV\AddressBook($this->backend, $addressBookInfo, $this->l10n);
			$cm->registerAddressBook(
				new AddressBookImpl(
					$addressBook,
					$addressBookInfo,
					$this->backend,
					$urlGenerator,
					$this->propertyMapper,
					$userId,
				)
			);
		}
	}
}
