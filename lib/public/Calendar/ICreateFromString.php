<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Calendar;

use OCP\Calendar\Exceptions\CalendarException;

/**
 * Extends the current ICalendar interface
 * to add a public write method
 *
 * @since 23.0.0
 */
interface ICreateFromString extends ICalendar {
	/**
	 * Create an event in this calendar from an ICS string.
	 *
	 * @param string $name the file name - needs to contain the .ics ending
	 * @param string $calendarData a string containing a valid VEVENT ics
	 *
	 * @throws CalendarException
	 *
	 * @since 23.0.0
	 *
	 */
	public function createFromString(string $name, string $calendarData): void;

	/**
	 * Create an event in this calendar from an ICS string using a minimal CalDAV server.
	 * Usually, the createFromString() method should be preferred.
	 *
	 * However, in some cases it is useful to not set up a full CalDAV server.
	 * Missing features include no iMIP plugin, no invitation emails amongst others.
	 *
	 * @param string $name the file name - needs to contain the .ics ending
	 * @param string $calendarData a string containing a valid VEVENT ics
	 *
	 * @throws CalendarException
	 *
	 * @since 32.0.0
	 */
	public function createFromStringMinimal(string $name, string $calendarData): void;
}
