<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\ContactsInteraction;

use OCA\ContactsInteraction\AppInfo\Application;
use OCA\ContactsInteraction\Db\RecentContactMapper;
use OCA\DAV\CardDAV\Integration\ExternalAddressBook;
use OCA\DAV\CardDAV\Integration\IAddressBookProvider;
use OCP\IL10N;

class AddressBookProvider implements IAddressBookProvider {

	public function __construct(
		private RecentContactMapper $mapper,
		private IL10N $l10n,
	) {
	}

	/**
	 * @inheritDoc
	 */
	public function getAppId(): string {
		return Application::APP_ID;
	}

	/**
	 * @inheritDoc
	 */
	public function fetchAllForAddressBookHome(string $principalUri): array {
		return [
			new AddressBook($this->mapper, $this->l10n, $principalUri)
		];
	}

	/**
	 * @inheritDoc
	 */
	public function hasAddressBookInAddressBookHome(string $principalUri, string $uri): bool {
		return $uri === AddressBook::URI;
	}

	/**
	 * @inheritDoc
	 */
	public function getAddressBookInAddressBookHome(string $principalUri, string $uri): ?ExternalAddressBook {
		if ($uri === AddressBook::URI) {
			return new AddressBook($this->mapper, $this->l10n, $principalUri);
		}

		return null;
	}
}
