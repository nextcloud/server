<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\CalDAV;

use Sabre\VObject\Reader;

class PublicCalendarObject extends CalendarObject {

	/**
	 * @inheritdoc
	 */
	public function get() {
		$data = parent::get();

		if (!$this->isShared()) {
			return $data;
		}

		$vObject = Reader::read($data);

		// remove VAlarms if calendar is shared read-only
		if (!$this->canWrite()) {
			$this->removeVAlarms($vObject);
		}

		// shows as busy if event is declared confidential or external confidential
		if (in_array($this->objectData['classification'], [CalDavBackend::CLASSIFICATION_CONFIDENTIAL, CalDavBackend::CLASSIFICATION_PUBLISHED_CONFIDENTIAL], true)) {
			$this->createConfidentialObject($vObject);
		}

		return $vObject->serialize();
	}

	/**
	 * public calendars are always shared
	 * @return bool
	 */
	protected function isShared() {
		return true;
	}
}
