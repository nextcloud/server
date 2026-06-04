<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Calendar;

/**
 * This interface defines a lazy loading mechanism for
 * calendars for Public Consumption
 *
 * @since 23.0.0
 */
interface ICalendarProvider {
	/**
	 * @param string $principalUri URI of the principal
	 * @param string[] $calendarUris optionally specify which calendars to load, or all if this array is empty
	 * @return ICalendar[]
	 * @since 23.0.0
	 */
	public function getCalendars(string $principalUri, array $calendarUris = []): array;
}
