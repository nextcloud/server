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

	/** @var CalDavBackend */
	private $calDavBackend;

	/** @var IL10N */
	private $l10n;

	/** @var IConfig */
	private $config;

	/** @var LoggerInterface */
	private $logger;

	public function __construct(CalDavBackend $calDavBackend, IL10N $l10n, IConfig $config, LoggerInterface $logger) {
		$this->calDavBackend = $calDavBackend;
		$this->l10n = $l10n;
		$this->config = $config;
		$this->logger = $logger;
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
