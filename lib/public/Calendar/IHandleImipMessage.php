<?php

declare(strict_types=1);
/**
 * @copyright 2022 Anna Larch <anna.larch@gmx.net>
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

use OCP\Calendar\Exceptions\CalendarException;

/**
 * Extends the current ICalendar interface
 * to add a public write method to handle
 * iMIP data
 *
 * @link https://www.rfc-editor.org/rfc/rfc6047
 *
 * @since 26.0.0
 */
interface IHandleImipMessage extends ICalendar {
	/**
	 * Handle an iMIP VEvent for validation and processing
	 *
	 * @since 26.0.0
	 *
	 * @throws CalendarException  on validation failure or calendar write error
	 */
	public function handleIMipMessage(string $name, string $calendarData): void;
}
