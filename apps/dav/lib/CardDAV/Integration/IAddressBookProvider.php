<?php

declare(strict_types=1);

namespace OCA\DAV\CardDAV\Integration;

use Sabre\CardDAV\IAddressBook;

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
	 * Checks whether plugin has a calendar for a given principalUri and calendarUri
	 *
	 * @since 19.0.0
	 * @param string $principalUri E.g. principals/users/user1
	 * @param string $calendarUri E.g. personal
	 * @return bool True if calendar for principalUri and calendarUri exists, false otherwise
	 */
	public function hasAddressBookInCalendarHome(string $principalUri, string $calendarUri): bool;

	/**
	 * Fetches a calendar for a given principalUri and calendarUri
	 * Returns null if calendar does not exist
	 *
	 * @param string $principalUri E.g. principals/users/user1
	 * @param string $calendarUri E.g. personal
	 *
	 * @return ExternalAddressBook|null Calendar if it exists, null otherwise
	 *@since 19.0.0
	 */
	public function getAddressBookInCalendarHome(string $principalUri, string $calendarUri): ?ExternalAddressBook;

}
