<?php
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\CalDAV\Integration;

/**
 * Interface ICalendarProvider
 *
 * @package OCA\DAV\CalDAV\Integration
 * @since 19.0.0
 */
interface ICalendarProvider {

	/**
	 * Provides the appId of the plugin
	 *
	 * @since 19.0.0
	 * @return string AppId
	 */
	public function getAppId(): string;

	/**
	 * Fetches all calendars for a given principal uri
	 *
	 * @since 19.0.0
	 * @param string $principalUri E.g. principals/users/user1
	 * @return ExternalCalendar[] Array of all calendars
	 */
	public function fetchAllForCalendarHome(string $principalUri): array;

	/**
	 * Checks whether plugin has a calendar for a given principalUri and calendarUri
	 *
	 * @since 19.0.0
	 * @param string $principalUri E.g. principals/users/user1
	 * @param string $calendarUri E.g. personal
	 * @return bool True if calendar for principalUri and calendarUri exists, false otherwise
	 */
	public function hasCalendarInCalendarHome(string $principalUri, string $calendarUri): bool;

	/**
	 * Fetches a calendar for a given principalUri and calendarUri
	 * Returns null if calendar does not exist
	 *
	 * @since 19.0.0
	 * @param string $principalUri E.g. principals/users/user1
	 * @param string $calendarUri E.g. personal
	 * @return ExternalCalendar|null Calendar if it exists, null otherwise
	 */
	public function getCalendarInCalendarHome(string $principalUri, string $calendarUri): ?ExternalCalendar;
}
