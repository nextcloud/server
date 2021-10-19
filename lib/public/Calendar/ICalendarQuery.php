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
