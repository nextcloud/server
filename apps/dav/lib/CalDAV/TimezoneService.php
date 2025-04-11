<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\CalDAV;

use OCA\DAV\Db\PropertyMapper;
use OCP\Calendar\ICalendar;
use OCP\Calendar\IManager;
use OCP\IConfig;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Component\VTimeZone;
use Sabre\VObject\Reader;
use function array_reduce;

class TimezoneService {

	public function __construct(
		private IConfig $config,
		private PropertyMapper $propertyMapper,
		private IManager $calendarManager,
	) {
	}

	public function getUserTimezone(string $userId): ?string {
		$fromConfig = $this->config->getUserValue(
			$userId,
			'core',
			'timezone',
		);
		if ($fromConfig !== '') {
			return $fromConfig;
		}

		$availabilityPropPath = 'calendars/' . $userId . '/inbox';
		$availabilityProp = '{' . Plugin::NS_CALDAV . '}calendar-availability';
		$availabilities = $this->propertyMapper->findPropertyByPathAndName($userId, $availabilityPropPath, $availabilityProp);
		if (!empty($availabilities)) {
			$availability = $availabilities[0]->getPropertyvalue();
			/** @var VCalendar $vCalendar */
			$vCalendar = Reader::read($availability);
			/** @var VTimeZone $vTimezone */
			$vTimezone = $vCalendar->VTIMEZONE;
			// Sabre has a fallback to date_default_timezone_get
			return $vTimezone->getTimeZone()->getName();
		}

		$principal = 'principals/users/' . $userId;
		$uri = $this->config->getUserValue($userId, 'dav', 'defaultCalendar', CalDavBackend::PERSONAL_CALENDAR_URI);
		$calendars = $this->calendarManager->getCalendarsForPrincipal($principal);

		/** @var ?VTimeZone $personalCalendarTimezone */
		$personalCalendarTimezone = array_reduce($calendars, function (?VTimeZone $acc, ICalendar $calendar) use ($uri) {
			if ($acc !== null) {
				return $acc;
			}
			if ($calendar->getUri() === $uri && !$calendar->isDeleted() && $calendar instanceof CalendarImpl) {
				return $calendar->getSchedulingTimezone();
			}
			return null;
		});
		if ($personalCalendarTimezone !== null) {
			return $personalCalendarTimezone->getTimeZone()->getName();
		}

		// No timezone in the personalCalendarTimezone calendar or no personalCalendarTimezone calendar
		// Loop through all calendars until we find a timezone.
		/** @var ?VTimeZone $firstTimezone */
		$firstTimezone = array_reduce($calendars, function (?VTimeZone $acc, ICalendar $calendar) {
			if ($acc !== null) {
				return $acc;
			}
			if (!$calendar->isDeleted() && $calendar instanceof CalendarImpl) {
				return $calendar->getSchedulingTimezone();
			}
			return null;
		});
		if ($firstTimezone !== null) {
			return $firstTimezone->getTimeZone()->getName();
		}
		return null;
	}

	public function getDefaultTimezone(): string {
		return $this->config->getSystemValueString('default_timezone', 'UTC');
	}

}
