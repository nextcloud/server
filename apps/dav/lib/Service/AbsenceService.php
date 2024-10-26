<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Service;

use InvalidArgumentException;
use OCA\DAV\BackgroundJob\OutOfOfficeEventDispatcherJob;
use OCA\DAV\CalDAV\TimezoneService;
use OCA\DAV\Db\Absence;
use OCA\DAV\Db\AbsenceMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IUser;
use OCP\User\Events\OutOfOfficeChangedEvent;
use OCP\User\Events\OutOfOfficeClearedEvent;
use OCP\User\Events\OutOfOfficeScheduledEvent;
use OCP\User\IOutOfOfficeData;

class AbsenceService {
	public function __construct(
		private AbsenceMapper $absenceMapper,
		private IEventDispatcher $eventDispatcher,
		private IJobList $jobList,
		private TimezoneService $timezoneService,
		private ITimeFactory $timeFactory,
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
		?string $replacementUserId = null,
		?string $replacementUserDisplayName = null,
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
		$absence->setReplacementUserId($replacementUserId);
		$absence->setReplacementUserDisplayName($replacementUserDisplayName);

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

		$now = $this->timeFactory->getTime();
		if ($eventData->getStartDate() > $now) {
			$this->jobList->scheduleAfter(
				OutOfOfficeEventDispatcherJob::class,
				$eventData->getStartDate(),
				[
					'id' => $absence->getId(),
					'event' => OutOfOfficeEventDispatcherJob::EVENT_START,
				],
			);
		}
		if ($eventData->getEndDate() > $now) {
			$this->jobList->scheduleAfter(
				OutOfOfficeEventDispatcherJob::class,
				$eventData->getEndDate(),
				[
					'id' => $absence->getId(),
					'event' => OutOfOfficeEventDispatcherJob::EVENT_END,
				],
			);
		}

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

	public function getAbsence(string $userId): ?Absence {
		try {
			return $this->absenceMapper->findByUserId($userId);
		} catch (DoesNotExistException $e) {
			return null;
		}
	}

	public function getCurrentAbsence(IUser $user): ?IOutOfOfficeData {
		try {
			$absence = $this->absenceMapper->findByUserId($user->getUID());
			$oooData = $absence->toOutOufOfficeData(
				$user,
				$this->timezoneService->getUserTimezone($user->getUID()) ?? $this->timezoneService->getDefaultTimezone(),
			);
			if ($this->isInEffect($oooData)) {
				return $oooData;
			}
		} catch (DoesNotExistException) {
			// Nothing there to process
		}
		return null;
	}

	public function isInEffect(IOutOfOfficeData $absence): bool {
		$now = $this->timeFactory->getTime();
		return $absence->getStartDate() <= $now && $absence->getEndDate() >= $now;
	}
}
