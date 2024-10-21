<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Calendar;

/**
 * ICalendar Interface Extension
 *
 * @since 31.0.0
 */
interface ICalendarIsShared {
	
	/**
	 * Indicates whether the calendar is shared with the current user
	 *
	 * @since 31.0.0
	 */
	public function isShared(): bool;

}
