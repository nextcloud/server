<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\CalDAV;

use Sabre\VObject\Component\VCalendar;

class CalendarObjectEtagHelper {

	/**
	 * md5 of the calendar data with DTSTAMP removed from all components
	 */
	public static function computeWithoutDtstamp(VCalendar $vObject): string {
		$vObject = clone $vObject;
		foreach ($vObject->getComponents() as $component) {
			unset($component->DTSTAMP);
		}
		return md5($vObject->serialize());
	}
}
