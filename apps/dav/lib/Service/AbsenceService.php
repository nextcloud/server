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
use OCA\DAV\BackgroundJob\OutOfOfficeEventDispatcherJob;
use OCA\DAV\CalDAV\TimezoneService;
use OCA\DAV\Db\Absence;
use OCA\DAV\Db\AbsenceMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\BackgroundJob\IJobList;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IUser;
use OCP\User\Events\OutOfOfficeChangedEvent;
use OCP\User\Events\OutOfOfficeClearedEvent;
use OCP\User\Events\OutOfOfficeScheduledEvent;

class AbsenceService {
	public function __construct(
		private AbsenceMapper $absenceMapper,
		private IEventDispatcher $eventDispatcher,
		private IJobList $jobList,
		private TimezoneService $timezoneService,
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
		IUser $user,
		string $firstDay,
		string $lastDay,
		string $status,
		string $message,
	): Absence {
		try {
			$absence = $this->absenceMapper->findByUserId($user->getUID());
		} catch (DoesNotExistException) {
			$absence = new Absence();
		}

		$absence->setUserId($user->getUID());
		$absence->setFirstDay($firstDay);
		$absence->setLastDay($lastDay);
		$absence->setStatus($status);
		$absence->setMessage($message);

		if ($absence->getId() === null) {
			$absence = $this->absenceMapper->insert($absence);
			$eventData = $absence->toOutOufOfficeData(
				$user,
				$this->timezoneService->getUserTimezone($user->getUID()) ?? $this->timezoneService->getDefaultTimezone(),
			);
			$this->eventDispatcher->dispatchTyped(new OutOfOfficeScheduledEvent($eventData));
		} else {
			$absence = $this->absenceMapper->update($absence);
			$eventData = $absence->toOutOufOfficeData(
				$user,
				$this->timezoneService->getUserTimezone($user->getUID()) ?? $this->timezoneService->getDefaultTimezone(),
			);
			$this->eventDispatcher->dispatchTyped(new OutOfOfficeChangedEvent($eventData));
		}

		$this->jobList->scheduleAfter(
			OutOfOfficeEventDispatcherJob::class,
			$eventData->getStartDate(),
			[
				'id' => $absence->getId(),
				'event' => OutOfOfficeEventDispatcherJob::EVENT_START,
			],
		);
		$this->jobList->scheduleAfter(
			OutOfOfficeEventDispatcherJob::class,
			$eventData->getEndDate(),
			[
				'id' => $absence->getId(),
				'event' => OutOfOfficeEventDispatcherJob::EVENT_END,
			],
		);

		return $absence;
	}

	/**
	 * @throws \OCP\DB\Exception
	 */
	public function clearAbsence(IUser $user): void {
		try {
			$absence = $this->absenceMapper->findByUserId($user->getUID());
		} catch (DoesNotExistException $e) {
			// Nothing to clear
			return;
		}
		$this->absenceMapper->delete($absence);
		$this->jobList->remove(OutOfOfficeEventDispatcherJob::class);
		$eventData = $absence->toOutOufOfficeData(
			$user,
			$this->timezoneService->getUserTimezone($user->getUID()) ?? $this->timezoneService->getDefaultTimezone(),
		);
		$this->eventDispatcher->dispatchTyped(new OutOfOfficeClearedEvent($eventData));
	}
}
