<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\CalDAV;

use Sabre\DAV\Exception\MethodNotAllowed;

/**
 * Class CachedSubscriptionObject
 *
 * @package OCA\DAV\CalDAV
 * @property CalDavBackend $caldavBackend
 */
class CachedSubscriptionObject extends \Sabre\CalDAV\CalendarObject {

	/**
	 * @inheritdoc
	 */
	public function get() {
		// Pre-populating the 'calendardata' is optional, if we don't have it
		// already we fetch it from the backend.
		if (!isset($this->objectData['calendardata'])) {
			$this->objectData = $this->caldavBackend->getCalendarObject($this->calendarInfo['id'], $this->objectData['uri'], CalDavBackend::CALENDAR_TYPE_SUBSCRIPTION);
		}

		return $this->objectData['calendardata'];
	}

	/**
	 * @param resource|string $calendarData
	 * @return string
	 * @throws MethodNotAllowed
	 */
	public function put($calendarData) {
		throw new MethodNotAllowed('Creating objects in a cached subscription is not allowed');
	}

	/**
	 * @throws MethodNotAllowed
	 */
	public function delete() {
		throw new MethodNotAllowed('Deleting objects in a cached subscription is not allowed');
	}
}
