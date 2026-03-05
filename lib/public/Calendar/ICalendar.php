<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Calendar;

use DateTimeInterface;

/**
 * Interface ICalendar
 *
 * @since 13.0.0
 *
 * @psalm-type CalendarSearchOptions = array{
 *     timerange?: array{start?: DateTimeInterface, end?: DateTimeInterface},
 *     uid?: string,
 *     types?: string[],
 * }
 */
interface ICalendar {
	/**
	 * @return string defining the technical unique key
	 * @since 13.0.0
	 */
	public function getKey(): string;

	/**
	 * In comparison to getKey() this function returns a unique uri within the scope of the principal
	 * @since 24.0.0
	 */
	public function getUri(): string;

	/**
	 * In comparison to getKey() this function returns a human readable (maybe translated) name
	 * @return null|string
	 * @since 13.0.0
	 */
	public function getDisplayName(): ?string;

	/**
	 * Calendar color
	 * @return null|string
	 * @since 13.0.0
	 */
	public function getDisplayColor(): ?string;

	/**
	 * Search the current calendar for matching events.
	 *
	 * This method searches for events in the calendar that match a given pattern within specified properties.
	 * The search is case-insensitive. It supports optional parameters such as a time range, limit, and offset.
	 * The results are sorted by start date, with the closest events appearing first.
	 *
	 * @param string $pattern A string to search for within the events. The search is done case-insensitive.
	 * @param array $searchProperties Defines the properties within which the pattern should match.
	 * @param array $options Optional parameters for the search:
	 *                       - 'timerange' element that can have 'start' (DateTimeInterface), 'end' (DateTimeInterface), or both.
	 *                       - 'uid' element to look for events with a given uid.
	 *                       - 'types' element to only return events for a given type (e.g. VEVENT or VTODO)
	 * @psalm-param CalendarSearchOptions $options
	 * @param int|null $limit Limit the number of search results.
	 * @param int|null $offset For paging of search results.
	 * @return array An array of events/journals/todos which are arrays of key-value-pairs. The events are sorted by start date (closest first, furthest last).
	 *
	 * Implementation Details:
	 *
	 * An event can consist of many sub-events, typically the case for events with recurrence rules. On a database level,
	 * there's only one event stored (with a matching first occurrence and last occurrence timestamp). Expanding an event
	 * into sub-events is done on the backend level. Using limit, offset, and timerange comes with some drawbacks.
	 * When asking the database for events, the result is ordered by the primary key to guarantee a stable order.
	 * After expanding the events into sub-events, they are sorted by the date (closest to furthest).
	 *
	 * Usage Examples:
	 *
	 * 1) Find 7 events within the next two weeks:
	 *
	 * $dateTime = (new DateTimeImmutable())->setTimestamp($this->timeFactory->getTime());
	 * $inTwoWeeks = $dateTime->add(new DateInterval('P14D'));
	 *
	 * $calendar->search(
	 *     '',
	 *     [],
	 *     ['timerange' => ['start' => $dateTime, 'end' => $inTwoWeeks]],
	 *     7
	 * );
	 *
	 * Note: When combining timerange and limit, it's possible that the expected outcome is not in the order you would expect.
	 *
	 * Example: Create 7 events for tomorrow, starting from 11:00, 30 minutes each. Then create an 8th event for tomorrow at 10:00.
	 * The above code will list the event at 11:00 first, missing the event at 10:00. The reason is the ordering by the primary key
	 * and expanding on the backend level. This is a technical limitation. The easiest workaround is to fetch more events
	 * than you actually need, with the downside of needing more resources.
	 *
	 * Related:
	 * - https://github.com/nextcloud/server/pull/45222
	 * - https://github.com/nextcloud/server/issues/53002
	 *
	 * 2) Find all events where the location property contains the string 'Berlin':
	 *
	 * $calendar->search(
	 *     'Berlin',
	 *     ['LOCATION']
	 * );
	 *
	 * @since 13.0.0
	 */
	public function search(string $pattern, array $searchProperties = [], array $options = [], ?int $limit = null, ?int $offset = null): array;

	/**
	 * @return int build up using {@see \OCP\Constants}
	 * @since 13.0.0
	 */
	public function getPermissions(): int;

	/**
	 * Indicates whether the calendar is in the trash bin
	 *
	 * @since 26.0.0
	 */
	public function isDeleted(): bool;
}
