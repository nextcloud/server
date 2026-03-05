<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
	 * @throws CalendarException on validation failure or calendar write error
	 */
	public function handleIMipMessage(string $name, string $calendarData): void;
}
