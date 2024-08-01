<?php

/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud GmbH.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\CalDAV;

class Plugin extends \Sabre\CalDAV\Plugin {
	public const SYSTEM_CALENDAR_ROOT = 'system-calendars';

	/**
	 * Returns the path to a principal's calendar home.
	 *
	 * The return url must not end with a slash.
	 * This function should return null in case a principal did not have
	 * a calendar home.
	 *
	 * @param string $principalUrl
	 * @return string|null
	 */
	public function getCalendarHomeForPrincipal($principalUrl) {
		if (strrpos($principalUrl, 'principals/users', -strlen($principalUrl)) !== false) {
			[, $principalId] = \Sabre\Uri\split($principalUrl);
			return self::CALENDAR_ROOT . '/' . $principalId;
		}
		if (strrpos($principalUrl, 'principals/calendar-resources', -strlen($principalUrl)) !== false) {
			[, $principalId] = \Sabre\Uri\split($principalUrl);
			return self::SYSTEM_CALENDAR_ROOT . '/calendar-resources/' . $principalId;
		}
		if (strrpos($principalUrl, 'principals/calendar-rooms', -strlen($principalUrl)) !== false) {
			[, $principalId] = \Sabre\Uri\split($principalUrl);
			return self::SYSTEM_CALENDAR_ROOT . '/calendar-rooms/' . $principalId;
		}
	}
}
