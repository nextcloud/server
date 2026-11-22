<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\CalDAV;

use Sabre\DAV\Exception as DavException;

class DefaultCalendarValidator {
	/**
	 * Check if a given Calendar node is suitable to be used as the default calendar for scheduling.
	 *
	 * @throws DavException If the calendar is not suitable to be used as the default calendar
	 */
	public function validateScheduleDefaultCalendar(Calendar $calendar): void {
		// Sanity checks for a calendar that should handle invitations
		if ($calendar->isSubscription()
			|| !$calendar->canWrite()
			|| $calendar->isShared()
			|| $calendar->isDeleted()) {
			throw new DavException('Calendar is a subscription, not writable, shared or deleted');
		}

		// Calendar must support VEVENTs
		$sCCS = '{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set';
		$calendarProperties = $calendar->getProperties([$sCCS]);
		if (isset($calendarProperties[$sCCS])) {
			$supportedComponents = $calendarProperties[$sCCS]->getValue();
		} else {
			$supportedComponents = ['VJOURNAL', 'VTODO', 'VEVENT'];
		}
		if (!in_array('VEVENT', $supportedComponents, true)) {
			throw new DavException('Calendar does not support VEVENT components');
		}
	}
}
