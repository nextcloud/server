<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\CalDAV\Federation;

use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\CalDAV\Calendar;
use OCP\IConfig;
use OCP\IL10N;
use Psr\Log\LoggerInterface;
use Sabre\CalDAV\Backend;

class FederatedCalendar extends Calendar {
	public function __construct(
		Backend\BackendInterface $caldavBackend,
		$calendarInfo,
		IL10N $l10n,
		IConfig $config,
		LoggerInterface $logger,
		private readonly FederatedCalendarMapper $federatedCalendarMapper,
	) {
		parent::__construct($caldavBackend, $calendarInfo, $l10n, $config, $logger);
	}

	public function delete() {
		$this->federatedCalendarMapper->deleteById($this->getResourceId());
	}

	protected function getCalendarType(): int {
		return CalDavBackend::CALENDAR_TYPE_FEDERATED;
	}
}
