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
 * @since 30.0.12
 */
interface ICalendarIsEnabled {
	
	/**
	 * Indicates whether the calendar is enabled
	 *
	 * @since 30.0.12
	 */
	public function isEnabled(): bool;

}
