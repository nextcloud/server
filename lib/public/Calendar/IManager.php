<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Calendar;

use DateTimeInterface;
use OCP\IUser;

/**
 * This class provides access to the Nextcloud CalDAV backend.
 * Use this class exclusively if you want to access calendars.
 *
 * Events/Journals/Todos in general will be expressed as an array of key-value-pairs.
 * The keys will match the property names defined in https://tools.ietf.org/html/rfc5545
 *
 * [
 *   'id' => 123,
 *   'type' => 'VEVENT',
 *   'calendar-key' => 42,
 *   'objects' => [
 *     [
 *       'SUMMARY' => ['FooBar', []],
 *       'DTSTART' => ['20171001T123456', ['TZID' => 'EUROPE/BERLIN']],
 *       'DURATION' => ['P1D', []],
 * 	     'ATTENDEE' => [
 *         ['mailto:bla@blub.com', ['CN' => 'Mr. Bla Blub']]
 *       ],
 *       'VALARM' => [
 * 	       [
 *           'TRIGGER' => ['19980101T050000Z', ['VALUE' => DATE-TIME]]
 *         ]
 *       ]
 *     ],
 *   ]
 * ]
 *
 * @since 13.0.0
 */
interface IManager {
	/**
	 * This function is used to search and find objects within the user's calendars.
	 * In case $pattern is empty all events/journals/todos will be returned.
	 *
	 * @param string $pattern which should match within the $searchProperties
	 * @param array $searchProperties defines the properties within the query pattern should match
	 * @param array $options - optional parameters:
	 *                       ['timerange' => ['start' => new DateTime(...), 'end' => new DateTime(...)]]
	 * @param integer|null $limit - limit number of search results
	 * @param integer|null $offset - offset for paging of search results
	 * @return array an array of events/journals/todos which are arrays of arrays of key-value-pairs
	 * @since 13.0.0
	 * @deprecated 23.0.0 use \OCP\Calendar\IManager::searchForPrincipal
	 */
	public function search($pattern, array $searchProperties = [], array $options = [], $limit = null, $offset = null);

	/**
	 * Check if calendars are available
	 *
	 * @return bool true if enabled, false if not
	 * @since 13.0.0
	 * @deprecated 23.0.0
	 */
	public function isEnabled();

	/**
	 * Registers a calendar
	 *
	 * @param ICalendar $calendar
	 * @return void
	 * @since 13.0.0
	 * @deprecated 23.0.0 use \OCP\AppFramework\Bootstrap\IRegistrationContext::registerCalendarProvider
	 */
	public function registerCalendar(ICalendar $calendar);

	/**
	 * Unregisters a calendar
	 *
	 * @param ICalendar $calendar
	 * @return void
	 * @since 13.0.0
	 * @deprecated 23.0.0
	 */
	public function unregisterCalendar(ICalendar $calendar);

	/**
	 * In order to improve lazy loading a closure can be registered which will be called in case
	 * calendars are actually requested
	 *
	 * @param \Closure $callable
	 * @return void
	 * @since 13.0.0
	 * @deprecated 23.0.0 use \OCP\AppFramework\Bootstrap\IRegistrationContext::registerCalendarProvider
	 */
	public function register(\Closure $callable);

	/**
	 * @return ICalendar[]
	 * @since 13.0.0
	 * @deprecated 23.0.0 use \OCP\Calendar\IManager::getCalendarsForPrincipal
	 */
	public function getCalendars();

	/**
	 * removes all registered calendar instances
	 *
	 * @return void
	 * @since 13.0.0
	 * @deprecated 23.0.0
	 */
	public function clear();

	/**
	 * @param string $principalUri URI of the principal
	 * @param string[] $calendarUris optionally specify which calendars to load, or all if this array is empty
	 *
	 * @return ICalendar[]
	 * @since 23.0.0
	 */
	public function getCalendarsForPrincipal(string $principalUri, array $calendarUris = []): array;

	/**
	 * Query a principals calendar(s)
	 *
	 * @param ICalendarQuery $query
	 * @return array[]
	 * @since 23.0.0
	 */
	public function searchForPrincipal(ICalendarQuery $query): array;

	/**
	 * Build a new query for searchForPrincipal
	 *
	 * @return ICalendarQuery
	 * @since 23.0.0
	 */
	public function newQuery(string $principalUri) : ICalendarQuery;

	/**
	 * Handles a iMip message
	 *
	 * @param array{absent?: "create", recipient?: string} $options
	 *
	 * @throws \OCP\DB\Exception
	 *
	 * @since 32.0.0
	 */
	public function handleIMip(string $userId, string $message, array $options = []): bool;

	/**
	 * Handle a iMip REQUEST message
	 *
	 * @since 31.0.0
	 */
	public function handleIMipRequest(string $principalUri, string $sender, string $recipient, string $calendarData): bool;

	/**
	 * Handle a iMip REPLY message
	 *
	 * @since 25.0.0
	 */
	public function handleIMipReply(string $principalUri, string $sender, string $recipient, string $calendarData): bool;

	/**
	 * Handle a iMip CANCEL message
	 *
	 * @since 25.0.0
	 */
	public function handleIMipCancel(string $principalUri, string $sender, ?string $replyTo, string $recipient, string $calendarData): bool;

	/**
	 * Create a new event builder instance. Please have a look at its documentation and the
	 * \OCP\Calendar\ICreateFromString interface on how to use it.
	 *
	 * @since 31.0.0
	 */
	public function createEventBuilder(): ICalendarEventBuilder;

	/**
	 * Check the availability of the given organizer and attendees in the given time range.
	 *
	 * @since 31.0.0
	 *
	 * @param IUser $organizer The organizing user from whose perspective to do the availability check.
	 * @param string[] $attendees Email addresses of attendees to check for (with or without a "mailto:" prefix). Only users on this instance can be checked. The rest will be silently ignored.
	 * @return IAvailabilityResult[] Availabilities of the organizer and all attendees which are also users on this instance. As such, the array might not contain an entry for each given attendee.
	 */
	public function checkAvailability(
		DateTimeInterface $start,
		DateTimeInterface $end,
		IUser $organizer,
		array $attendees,
	): array;
}
