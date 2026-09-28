<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\CardDAV;

use OCA\DAV\Db\PropertyMapper;
use OCP\Contacts\IManager;
use OCP\IAppConfig;
use OCP\IL10N;
use OCP\IURLGenerator;

class ContactsManager {
	/**
	 * ContactsManager constructor.
	 *
	 * @param CardDavBackend $backend
	 * @param IL10N $l10n
	 */
	public function __construct(
		private CardDavBackend $backend,
		private IL10N $l10n,
		private PropertyMapper $propertyMapper,
		private IAppConfig $appConfig,
	) {
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
		$systemAddressBookExposed = $this->appConfig->getValueBool('dav', 'system_addressbook_exposed', true);
		if (!$systemAddressBookExposed) {
			return;
		}

		$addressBooks = $this->backend->getAddressBooksForUser('principals/system/system');
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
			$addressBook = new AddressBook($this->backend, $addressBookInfo, $this->l10n);
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
