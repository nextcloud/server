<?php

declare(strict_types=1);

/**
 * @copyright 2021 Anna Larch <anna.larch@gmx.net>
 *
 * @author Anna Larch <anna.larch@gmx.net>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCP\Calendar;

use DateTimeImmutable;

/**
 * Build a flexible, extendable query to the CalDAV backend
 *
 * @since 23.0.0
 */
interface ICalendarQuery {
	/**
	 * @since 24.0.0
	 */
	public const SEARCH_PROPERTY_CATEGORIES = 'CATEGORIES';

	/**
	 * @since 24.0.0
	 */
	public const SEARCH_PROPERTY_COMMENT = 'COMMENT';

	/**
	 * @since 24.0.0
	 */
	public const SEARCH_PROPERTY_DESCRIPTION = 'DESCRIPTION';

	/**
	 * @since 24.0.0
	 */
	public const SEARCH_PROPERTY_LOCATION = 'LOCATION';

	/**
	 * @since 24.0.0
	 */
	public const SEARCH_PROPERTY_RESOURCES = 'RESOURCES';

	/**
	 * @since 24.0.0
	 */
	public const SEARCH_PROPERTY_STATUS = 'STATUS';

	/**
	 * @since 24.0.0
	 */
	public const SEARCH_PROPERTY_SUMMARY = 'SUMMARY';

	/**
	 * @since 24.0.0
	 */
	public const SEARCH_PROPERTY_ATTENDEE = 'ATTENDEE';

	/**
	 * @since 24.0.0
	 */
	public const SEARCH_PROPERTY_CONTACT = 'CONTACT';

	/**
	 * @since 24.0.0
	 */
	public const SEARCH_PROPERTY_ORGANIZER = 'ORGANIZER';

	/**
	 * Limit the results to the calendar uri(s)
	 *
	 * @since 23.0.0
	 */
	public function addSearchCalendar(string $calendarUri): void;

	/**
	 * Search the property values
	 *
	 * @since 23.0.0
	 */
	public function setSearchPattern(string $pattern): void;

	/**
	 * Define the property name(s) to search for
	 *
	 * Note: Nextcloud only indexes *some* properties. You can not search for
	 *       arbitrary properties.
	 *
	 * @param string $value any of the ICalendarQuery::SEARCH_PROPERTY_* values
	 * @psalm-param ICalendarQuery::SEARCH_PROPERTY_* $value
	 *
	 * @since 23.0.0
	 */
	public function addSearchProperty(string $value): void;

	/**
	 * @since 23.0.0
	 */
	public function addType(string $value): void;

	/**
	 * @since 23.0.0
	 */
	public function setTimerangeStart(DateTimeImmutable $startTime): void;

	/**
	 * @since 23.0.0
	 */
	public function setTimerangeEnd(DateTimeImmutable $endTime): void;

	/**
	 * @since 23.0.0
	 */
	public function setLimit(int $limit): void;

	/**
	 * @since 23.0.0
	 */
	public function setOffset(int $offset): void;
}
