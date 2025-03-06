<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Calendar;

/**
 * ICalendar Interface Extension to import data
 *
 * @since 32.0.0
 */
interface ICalendarImport {

	/**
	 * Import objects
	 *
	 * @since 32.0.0
	 *
	 * @param CalendarImportOptions $options
	 * @param callable $generator<CalendarImportOptions>: Generator<\Sabre\VObject\Component\VCalendar>
	 *
	 * @return array<string,array<string,string|array<string>>>
	 */
	public function import(CalendarImportOptions $options, callable $generator): array;

}
