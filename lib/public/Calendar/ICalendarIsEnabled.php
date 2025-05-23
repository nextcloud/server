<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Calendar;

/**
 * ICalendar Interface Extension
 *
 * @since 31.0.6
 */
interface ICalendarIsEnabled {
	
	/**
	 * Indicates whether the calendar is enabled
	 *
	 * @since 31.0.6
	 */
	public function isEnabled(): bool;

}
