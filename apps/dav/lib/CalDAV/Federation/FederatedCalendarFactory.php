<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\CalDAV\Federation;

use OCA\DAV\CalDAV\CalDavBackend;

class FederatedCalendarFactory {

	public function __construct(
		private readonly FederatedCalendarMapper $federatedCalendarMapper,
		private readonly FederatedCalendarSyncService $federatedCalendarService,
		private readonly CalDavBackend $caldavBackend,
	) {
	}

	public function createFederatedCalendar(array $calendarInfo): FederatedCalendar {
		return new FederatedCalendar(
			$this->federatedCalendarMapper,
			$this->federatedCalendarService,
			$this->caldavBackend,
			$calendarInfo,
		);
	}
}
