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
	 * For calendar-proxy group principals (e.g. principals/users/alice/calendar-proxy-write),
	 * this returns the calendar home of the principal owner (alice), so that CalDAV clients
	 * can discover and access the delegated calendar home correctly.
	 *
	 * @param string $principalUrl
	 * @return string|null
	 */
	#[\Override]
	public function getCalendarHomeForPrincipal($principalUrl) {
		// calendar-proxy group principals must resolve to the owner's calendar home
		if (str_ends_with($principalUrl, '/calendar-proxy-write') || str_ends_with($principalUrl, '/calendar-proxy-read')) {
			$ownerPrincipalUrl = substr($principalUrl, 0, strrpos($principalUrl, '/'));
			return $this->getCalendarHomeForPrincipal($ownerPrincipalUrl);
		}

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
