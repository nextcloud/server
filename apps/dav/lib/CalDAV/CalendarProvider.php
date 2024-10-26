<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\CalDAV;

use OCP\Calendar\ICalendarProvider;
use OCP\IConfig;
use OCP\IL10N;
use Psr\Log\LoggerInterface;

class CalendarProvider implements ICalendarProvider {

	public function __construct(
		private CalDavBackend $calDavBackend,
		private IL10N $l10n,
		private IConfig $config,
		private LoggerInterface $logger,
	) {
	}

	public function getCalendars(string $principalUri, array $calendarUris = []): array {
		$calendarInfos = [];
		if (empty($calendarUris)) {
			$calendarInfos = $this->calDavBackend->getCalendarsForUser($principalUri);
		} else {
			foreach ($calendarUris as $calendarUri) {
				$calendarInfos[] = $this->calDavBackend->getCalendarByUri($principalUri, $calendarUri);
			}
		}

		$calendarInfos = array_filter($calendarInfos);

		$iCalendars = [];
		foreach ($calendarInfos as $calendarInfo) {
			$calendar = new Calendar($this->calDavBackend, $calendarInfo, $this->l10n, $this->config, $this->logger);
			$iCalendars[] = new CalendarImpl(
				$calendar,
				$calendarInfo,
				$this->calDavBackend,
			);
		}
		return $iCalendars;
	}
}
