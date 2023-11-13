<?php

declare(strict_types=1);

/*
 * @copyright 2023 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2023 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
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

	public function __construct(private IConfig $config,
		private PropertyMapper $propertyMapper,
		private IManager $calendarManager) {
	}

	public function getUserTimezone(string $userId): ?string {
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
