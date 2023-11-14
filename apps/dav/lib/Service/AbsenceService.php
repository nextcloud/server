<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @author Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\DAV\Service;

use InvalidArgumentException;
use OC\User\OutOfOfficeData;
use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\CalDAV\CalendarImpl;
use OCA\DAV\Db\Absence;
use OCA\DAV\Db\AbsenceMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Calendar\ICalendar;
use OCP\Calendar\IManager;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IConfig;
use OCP\IUserManager;
use OCP\User\Events\OutOfOfficeChangedEvent;
use OCP\User\Events\OutOfOfficeClearedEvent;
use OCP\User\Events\OutOfOfficeScheduledEvent;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Component\VTimeZone;
use Sabre\VObject\Reader;

class AbsenceService {
	public function __construct(
		private AbsenceMapper $absenceMapper,
		private IEventDispatcher $eventDispatcher,
		private IUserManager $userManager,
		private ITimeFactory $timeFactory,
		private IConfig $appConfig,
		private IManager $calendarManager,
	) {
	}

	/**
	 * @param string $firstDay The first day (inclusive) of the absence formatted as YYYY-MM-DD.
	 * @param string $lastDay The last day (inclusive) of the absence formatted as YYYY-MM-DD.
	 *
	 * @throws \OCP\DB\Exception
	 * @throws InvalidArgumentException If no user with the given user id exists.
	 */
	public function createOrUpdateAbsence(
		string $userId,
		string $firstDay,
		string $lastDay,
		string $status,
		string $message,
	): Absence {
		try {
			$absence = $this->absenceMapper->findByUserId($userId);
		} catch (DoesNotExistException) {
			$absence = new Absence();
		}

		$absence->setUserId($userId);
		$absence->setFirstDay($firstDay);
		$absence->setLastDay($lastDay);
		$absence->setStatus($status);
		$absence->setMessage($message);

		// TODO: this method should probably just take a IUser instance
		$user = $this->userManager->get($userId);
		if ($user === null) {
			throw new InvalidArgumentException("User $userId does not exist");
		}

		if ($absence->getId() === null) {
			$persistedAbsence = $this->absenceMapper->insert($absence);
			$timezone = $this->getAbsenceTimezone($userId);
			$this->eventDispatcher->dispatchTyped(new OutOfOfficeScheduledEvent(
				$persistedAbsence->toOutOufOfficeData($user, $timezone)
			));
			return $persistedAbsence;
		}

		$timezone = $this->getAbsenceTimezone($userId);

		$this->eventDispatcher->dispatchTyped(new OutOfOfficeChangedEvent(
			$absence->toOutOufOfficeData($user, $timezone)
		));
		return $this->absenceMapper->update($absence);
	}

	/**
	 * @throws \OCP\DB\Exception
	 */
	public function clearAbsence(string $userId): void {
		try {
			$absence = $this->absenceMapper->findByUserId($userId);
		} catch (DoesNotExistException $e) {
			// Nothing to clear
			return;
		}
		$this->absenceMapper->delete($absence);
		// TODO: this method should probably just take a IUser instance
		$user = $this->userManager->get($userId);
		if ($user === null) {
			throw new InvalidArgumentException("User $userId does not exist");
		}
		$timezone = $this->getAbsenceTimezone($userId);
		$eventData = $absence->toOutOufOfficeData($user, $timezone);
		$this->eventDispatcher->dispatchTyped(new OutOfOfficeClearedEvent($eventData));
	}

	public function getAbsence(string $userId): ?Absence {
		try {
			return $this->absenceMapper->findByUserId($userId);
		} catch (DoesNotExistException $e) {
			return null;
		}
	}

	public function isInEffect(OutOfOfficeData $absence): bool {
		$now = $this->timeFactory->getTime();
		return $absence->getStartDate() <= $now && $absence->getEndDate() >= $now;
	}

	/**
	 * Get a users calendar timezone or null if no calendar timezones exist
	 *
	 * @param string $userId
	 * @return string|null
	 */
	public function getAbsenceTimezone(string $userId): ?string {
		$availability = $this->absenceMapper->getAvailability($userId);
		if(!empty($availability)) {
			/** @var VCalendar $vCalendar */
			$vCalendar = Reader::read($availability);
			/** @var VTimeZone $vTimezone */
			$vTimezone = $vCalendar->VTIMEZONE;
			// Sabre has a fallback to date_default_timezone_get
			return $vTimezone->getTimeZone()->getName();
		}

		$principal = 'principals/users/' . $userId;
		$uri = $this->appConfig->getUserValue($userId, 'dav', 'defaultCalendar', CalDavBackend::PERSONAL_CALENDAR_URI);
		$calendars = $this->calendarManager->getCalendarsForPrincipal($principal);

		$tz = null;
		$personal = array_filter($calendars, function (ICalendar $calendar) use ($uri) {
			return $calendar->getUri() === $uri && $calendar->isDeleted() === false;
		});

		if(!empty($personal)) {
			$personal = array_pop($personal);
			$tz = $personal instanceof CalendarImpl ? $personal->getSchedulingTimezone() : null;
		}

		if($tz !== null) {
			return $tz->getTimeZone()->getName();
		}

		// No timezone in the personal calendar or no personal calendar
		// Loop through all calendars until we find a timezone.
		/** @var CalendarImpl $calendar */
		foreach ($calendars as $calendar) {
			if($calendar->isDeleted() === true) {
				continue;
			}
			$tz = $calendar->getSchedulingTimezone();
			if($tz !== null) {
				break;
			}
		}

		return $tz?->getTimeZone()->getName();

	}
}

