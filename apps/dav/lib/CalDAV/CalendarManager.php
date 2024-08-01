<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\CalDAV;

use OCP\Calendar\IManager;
use OCP\IConfig;
use OCP\IL10N;
use Psr\Log\LoggerInterface;

class CalendarManager {

	/** @var CalDavBackend */
	private $backend;

	/** @var IL10N */
	private $l10n;

	/** @var IConfig */
	private $config;

	/** @var LoggerInterface */
	private $logger;

	/**
	 * CalendarManager constructor.
	 *
	 * @param CalDavBackend $backend
	 * @param IL10N $l10n
	 * @param IConfig $config
	 */
	public function __construct(CalDavBackend $backend, IL10N $l10n, IConfig $config, LoggerInterface $logger) {
		$this->backend = $backend;
		$this->l10n = $l10n;
		$this->config = $config;
		$this->logger = $logger;
	}

	/**
	 * @param IManager $cm
	 * @param string $userId
	 */
	public function setupCalendarProvider(IManager $cm, $userId) {
		$calendars = $this->backend->getCalendarsForUser("principals/users/$userId");
		$this->register($cm, $calendars);
	}

	/**
	 * @param IManager $cm
	 * @param array $calendars
	 */
	private function register(IManager $cm, array $calendars) {
		foreach ($calendars as $calendarInfo) {
			$calendar = new Calendar($this->backend, $calendarInfo, $this->l10n, $this->config, $this->logger);
			$cm->registerCalendar(new CalendarImpl(
				$calendar,
				$calendarInfo,
				$this->backend
			));
		}
	}
}
