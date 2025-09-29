<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Calendar;

use Generator;

/**
 * ICalendar Interface Extension to export data
 *
 * @since 32.0.0
 */
interface ICalendarExport {

	/**
	 * Export objects
	 *
	 * @since 32.0.0
	 *
	 * @param CalendarExportOptions|null $options
	 *
	 * @return Generator<\Sabre\VObject\Component\VCalendar>
	 */
	public function export(?CalendarExportOptions $options): Generator;

}
