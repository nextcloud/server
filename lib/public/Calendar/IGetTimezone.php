<?php
/*
 * *
 *  * dav App
 *  *
 *  * @copyright 2023 Anna Larch <anna.larch@gmx.net>
 *  *
 *  * @author Anna Larch <anna.larch@gmx.net>
 *  *
 *  * This library is free software; you can redistribute it and/or
 *  * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 *  * License as published by the Free Software Foundation; either
 *  * version 3 of the License, or any later version.
 *  *
 *  * This library is distributed in the hope that it will be useful,
 *  * but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *  *
 *  * You should have received a copy of the GNU Affero General Public
 *  * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *  *
 *
 */
namespace OCP\Calendar;

use OCP\Calendar\Exceptions\CalendarException;
use OCP\Calendar\ICalendar;
use Sabre\VObject\Component\VTimeZone;

interface IGetTimezone extends ICalendar {
	/**
	 * Get the calendar timezone as a string
	 * i.e. Europe/Vienna
	 * as set in the VTIMEZONE->TZID for a calendar
	 *
	 * @since 26.0.0
	 */
	public function getCalendarTimezoneString(): ?string;
}

