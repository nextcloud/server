<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\CardDAV\Integration;

/**
 * @since 19.0.0
 */
interface IAddressBookProvider {

	/**
	 * Provides the appId of the plugin
	 *
	 * @since 19.0.0
	 * @return string AppId
	 */
	public function getAppId(): string;

	/**
	 * Fetches all address books for a given principal uri
	 *
	 * @since 19.0.0
	 * @param string $principalUri E.g. principals/users/user1
	 * @return ExternalAddressBook[] Array of all address books
	 */
	public function fetchAllForAddressBookHome(string $principalUri): array;

	/**
	 * Checks whether plugin has an address book for a given principalUri and URI
	 *
	 * @since 19.0.0
	 * @param string $principalUri E.g. principals/users/user1
	 * @param string $uri E.g. personal
	 * @return bool True if address book for principalUri and URI exists, false otherwise
	 */
	public function hasAddressBookInAddressBookHome(string $principalUri, string $uri): bool;

	/**
	 * Fetches an address book for a given principalUri and URI
	 * Returns null if address book does not exist
	 *
	 * @param string $principalUri E.g. principals/users/user1
	 * @param string $uri E.g. personal
	 *
	 * @return ExternalAddressBook|null address book if it exists, null otherwise
	 *@since 19.0.0
	 */
	public function getAddressBookInAddressBookHome(string $principalUri, string $uri): ?ExternalAddressBook;
}
