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
	 * @since 23.0.0
	 *
	 * @throws CalendarException
	 */
	public function createFromString(string $name, string $calendarData): void;
}
