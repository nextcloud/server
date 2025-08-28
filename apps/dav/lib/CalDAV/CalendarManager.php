<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\CalDAV;

use OCP\Calendar\IManager;

class CalendarManager {

	/**
	 * CalendarManager constructor.
	 *
	 * @param CalDavBackend $backend
	 */
	public function __construct(
		private CalDavBackend $backend,
		private readonly CalendarFactory $calendarFactory,
	) {
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
			$calendar = $this->calendarFactory->createCalendar($calendarInfo);
			$cm->registerCalendar(new CalendarImpl(
				$calendar,
				$calendarInfo,
				$this->backend
			));
		}
	}
}
