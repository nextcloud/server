<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Calendar;

use OCP\Calendar\Exceptions\CalendarException;
use Sabre\VObject\Component\VCalendar;

/**
 * ICalendar Interface Extension
 *
 * @since 32.0.0
 */
interface ICalendarHandleImip extends ICalendar {
	/**
	 * Handle an iMIP VEvent for validation and processing
	 *
	 * @since 32.0.0
	 *
	 * @throws CalendarException on validation failure or calendar write error
	 */
	public function handleIMip(string $message): void;
}
