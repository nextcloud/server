<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Calendar;

/**
 * ICalendar Interface Extension
 *
 * @since 36.0.0
 */
interface ICalendarIsTransparent {

	/**
	 * Indicates whether the calendar is transparent, meaning its events
	 * do not make the owner busy (RFC 6638 schedule-calendar-transp)
	 *
	 * @since 36.0.0
	 */
	public function isTransparent(): bool;

}
