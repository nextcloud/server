<?php

declare(strict_types=1);

/**
 * @copyright 2021 Anna Larch <anna.larch@gmx.net>
 *
 * @author Anna Larch <anna.larch@gmx.net>
 *
 * @license GNU AGPL version 3 or any later version
 *
 *
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

/**
 * This interface defines a lazy loading mechanism for
 * calendars for Public Consumption
 *
 * @since 23.0.0
 */
interface ICalendarProvider {
	/**
	 * @param string $principalUri URI of the principal
	 * @param string[] $calendarUris optionally specify which calendars to load, or all if this array is empty
	 * @return ICalendar[]
	 * @since 23.0.0
	 */
	public function getCalendars(string $principalUri, array $calendarUris = []): array;
}
