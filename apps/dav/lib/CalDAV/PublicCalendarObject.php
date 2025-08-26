<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\CalDAV;

class PublicCalendarObject extends CalendarObject {

	/**
	 * public calendars are always shared
	 * @return bool
	 */
	protected function isShared() {
		return true;
	}
}
