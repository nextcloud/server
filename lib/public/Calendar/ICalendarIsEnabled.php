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
 * @since 32.0.0
 */
interface ICalendarIsEnabled {

	/**
	 * Indicates whether the calendar is enabled
	 *
	 * @since 32.0.0
	 */
	public function isEnabled(): bool;

}
