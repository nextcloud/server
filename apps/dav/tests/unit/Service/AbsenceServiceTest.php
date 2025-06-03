<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\Service;

use DateTimeImmutable;
use DateTimeZone;
use OCA\DAV\BackgroundJob\OutOfOfficeEventDispatcherJob;
use OCA\DAV\CalDAV\TimezoneService;
use OCA\DAV\Db\Absence;
use OCA\DAV\Db\AbsenceMapper;
use OCA\DAV\Service\AbsenceService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IUser;
use OCP\User\Events\OutOfOfficeChangedEvent;
use OCP\User\Events\OutOfOfficeScheduledEvent;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AbsenceServiceTest extends TestCase {
	private AbsenceService $absenceService;
	private AbsenceMapper&MockObject $absenceMapper;
	private IEventDispatcher&MockObject $eventDispatcher;
	private IJobList&MockObject $jobList;
	private TimezoneService&MockObject $timezoneService;
	private ITimeFactory&MockObject $timeFactory;

	protected function setUp(): void {
		parent::setUp();

		$this->absenceMapper = $this->createMock(AbsenceMapper::class);
		$this->eventDispatcher = $this->createMock(IEventDispatcher::class);
		$this->jobList = $this->createMock(IJobList::class);
		$this->timezoneService = $this->createMock(TimezoneService::class);
		$this->timeFactory = $this->createMock(ITimeFactory::class);

		$this->absenceService = new AbsenceService(
			$this->absenceMapper,
			$this->eventDispatcher,
			$this->jobList,
			$this->timezoneService,
			$this->timeFactory,
		);
	}

	public function testCreateAbsenceEmitsScheduledEvent(): void {
		$tz = new DateTimeZone('Europe/Berlin');
		$user = $this->createMock(IUser::class);
		$user->method('getUID')
			->willReturn('user');

		$this->absenceMapper->expects(self::once())
			->method('findByUserId')
			->with('user')
			->willThrowException(new DoesNotExistException('foo bar'));
		$this->absenceMapper->expects(self::once())
			->method('insert')
			->willReturnCallback(function (Absence $absence): Absence {
				$absence->setId(1);
				return $absence;
			});
		$this->timezoneService->expects(self::once())
			->method('getUserTimezone')
			->with('user')
			->willReturn('Europe/Berlin');
		$this->eventDispatcher->expects(self::once())
			->method('dispatchTyped')
			->with(self::callback(static function (Event $event) use ($user, $tz): bool {
				self::assertInstanceOf(OutOfOfficeScheduledEvent::class, $event);
				/** @var OutOfOfficeScheduledEvent $event */
				$data = $event->getData();
				self::assertEquals('1', $data->getId());
				self::assertEquals($user, $data->getUser());
				self::assertEquals(
					(new DateTimeImmutable('2023-01-05', $tz))->getTimeStamp(),
					$data->getStartDate(),
				);
				self::assertEquals(
					(new DateTimeImmutable('2023-01-10', $tz))->getTimeStamp() + 3600 * 23 + 59 * 60,
					$data->getEndDate(),
				);
				self::assertEquals('status', $data->getShortMessage());
				self::assertEquals('message', $data->getMessage());
				return true;
			}));
		$this->timeFactory->expects(self::once())
			->method('getTime')
			->willReturn(PHP_INT_MAX);
		$this->jobList->expects(self::never())
			->method('scheduleAfter');

		$this->absenceService->createOrUpdateAbsence(
			$user,
			'2023-01-05',
			'2023-01-10',
			'status',
			'message',
		);
	}

	public function testUpdateAbsenceEmitsChangedEvent(): void {
		$tz = new DateTimeZone('Europe/Berlin');
		$user = $this->createMock(IUser::class);
		$user->method('getUID')
			->willReturn('user');
		$absence = new Absence();
		$absence->setId(1);
		$absence->setFirstDay('1970-01-01');
		$absence->setLastDay('1970-01-10');
		$absence->setStatus('old status');
		$absence->setMessage('old message');

		$this->absenceMapper->expects(self::once())
			->method('findByUserId')
			->with('user')
			->willReturn($absence);
		$this->absenceMapper->expects(self::once())
			->method('update')
			->willReturnCallback(static function (Absence $absence): Absence {
				self::assertEquals('2023-01-05', $absence->getFirstDay());
				self::assertEquals('2023-01-10', $absence->getLastDay());
				self::assertEquals('status', $absence->getStatus());
				self::assertEquals('message', $absence->getMessage());
				return $absence;
			});
		$this->timezoneService->expects(self::once())
			->method('getUserTimezone')
			->with('user')
			->willReturn('Europe/Berlin');
		$this->eventDispatcher->expects(self::once())
			->method('dispatchTyped')
			->with(self::callback(static function (Event $event) use ($user, $tz): bool {
				self::assertInstanceOf(OutOfOfficeChangedEvent::class, $event);
				/** @var OutOfOfficeChangedEvent $event */
				$data = $event->getData();
				self::assertEquals('1', $data->getId());
				self::assertEquals($user, $data->getUser());
				self::assertEquals(
					(new DateTimeImmutable('2023-01-05', $tz))->getTimeStamp(),
					$data->getStartDate(),
				);
				self::assertEquals(
					(new DateTimeImmutable('2023-01-10', $tz))->getTimeStamp() + 3600 * 23 + 59 * 60,
					$data->getEndDate(),
				);
				self::assertEquals('status', $data->getShortMessage());
				self::assertEquals('message', $data->getMessage());
				return true;
			}));
		$this->timeFactory->expects(self::once())
			->method('getTime')
			->willReturn(PHP_INT_MAX);
		$this->jobList->expects(self::never())
			->method('scheduleAfter');

		$this->absenceService->createOrUpdateAbsence(
			$user,
			'2023-01-05',
			'2023-01-10',
			'status',
			'message',
		);
	}

	public function testCreateAbsenceSchedulesBothJobs(): void {
		$tz = new DateTimeZone('Europe/Berlin');
		$startDateString = '2023-01-05';
		$startDate = new DateTimeImmutable($startDateString, $tz);
		$endDateString = '2023-01-10';
		$endDate = new DateTimeImmutable($endDateString, $tz);
		$user = $this->createMock(IUser::class);
		$user->method('getUID')
			->willReturn('user');

		$this->absenceMapper->expects(self::once())
			->method('findByUserId')
			->with('user')
			->willThrowException(new DoesNotExistException('foo bar'));
		$this->absenceMapper->expects(self::once())
			->method('insert')
			->willReturnCallback(function (Absence $absence): Absence {
				$absence->setId(1);
				return $absence;
			});
		$this->timezoneService->expects(self::once())
			->method('getUserTimezone')
			->with('user')
			->willReturn($tz->getName());
		$this->timeFactory->expects(self::once())
			->method('getTime')
			->willReturn((new DateTimeImmutable('2023-01-01', $tz))->getTimestamp());
		$this->jobList->expects(self::exactly(2))
			->method('scheduleAfter')
			->willReturnMap([
				[OutOfOfficeEventDispatcherJob::class, $startDate->getTimestamp(), [
					'id' => '1',
					'event' => OutOfOfficeEventDispatcherJob::EVENT_START,
				]],
				[OutOfOfficeEventDispatcherJob::class, $endDate->getTimestamp() + 3600 * 23 + 59 * 60, [
					'id' => '1',
					'event' => OutOfOfficeEventDispatcherJob::EVENT_END,
				]],
			]);

		$this->absenceService->createOrUpdateAbsence(
			$user,
			$startDateString,
			$endDateString,
			'',
			'',
		);
	}

	public function testCreateAbsenceSchedulesOnlyEndJob(): void {
		$tz = new DateTimeZone('Europe/Berlin');
		$endDateString = '2023-01-10';
		$endDate = new DateTimeImmutable($endDateString, $tz);
		$user = $this->createMock(IUser::class);
		$user->method('getUID')
			->willReturn('user');

		$this->absenceMapper->expects(self::once())
			->method('findByUserId')
			->with('user')
			->willThrowException(new DoesNotExistException('foo bar'));
		$this->absenceMapper->expects(self::once())
			->method('insert')
			->willReturnCallback(function (Absence $absence): Absence {
				$absence->setId(1);
				return $absence;
			});
		$this->timezoneService->expects(self::once())
			->method('getUserTimezone')
			->with('user')
			->willReturn($tz->getName());
		$this->timeFactory->expects(self::once())
			->method('getTime')
			->willReturn((new DateTimeImmutable('2023-01-07', $tz))->getTimestamp());
		$this->jobList->expects(self::once())
			->method('scheduleAfter')
			->with(OutOfOfficeEventDispatcherJob::class, $endDate->getTimestamp() + 3600 * 23 + 59 * 60, [
				'id' => '1',
				'event' => OutOfOfficeEventDispatcherJob::EVENT_END,
			]);

		$this->absenceService->createOrUpdateAbsence(
			$user,
			'2023-01-05',
			$endDateString,
			'',
			'',
		);
	}

	public function testCreateAbsenceSchedulesNoJob(): void {
		$tz = new DateTimeZone('Europe/Berlin');
		$user = $this->createMock(IUser::class);
		$user->method('getUID')
			->willReturn('user');

		$this->absenceMapper->expects(self::once())
			->method('findByUserId')
			->with('user')
			->willThrowException(new DoesNotExistException('foo bar'));
		$this->absenceMapper->expects(self::once())
			->method('insert')
			->willReturnCallback(function (Absence $absence): Absence {
				$absence->setId(1);
				return $absence;
			});
		$this->timezoneService->expects(self::once())
			->method('getUserTimezone')
			->with('user')
			->willReturn($tz->getName());
		$this->timeFactory->expects(self::once())
			->method('getTime')
			->willReturn((new DateTimeImmutable('2023-01-12', $tz))->getTimestamp());
		$this->jobList->expects(self::never())
			->method('scheduleAfter');

		$this->absenceService->createOrUpdateAbsence(
			$user,
			'2023-01-05',
			'2023-01-10',
			'',
			'',
		);
	}

	public function testUpdateAbsenceSchedulesBothJobs(): void {
		$tz = new DateTimeZone('Europe/Berlin');
		$startDateString = '2023-01-05';
		$startDate = new DateTimeImmutable($startDateString, $tz);
		$endDateString = '2023-01-10';
		$endDate = new DateTimeImmutable($endDateString, $tz);
		$user = $this->createMock(IUser::class);
		$user->method('getUID')
			->willReturn('user');
		$absence = new Absence();
		$absence->setId(1);
		$absence->setFirstDay('1970-01-01');
		$absence->setLastDay('1970-01-10');
		$absence->setStatus('old status');
		$absence->setMessage('old message');

		$this->absenceMapper->expects(self::once())
			->method('findByUserId')
			->with('user')
			->willReturn($absence);
		$this->absenceMapper->expects(self::once())
			->method('update')
			->willReturnCallback(static function (Absence $absence) use ($startDateString, $endDateString): Absence {
				self::assertEquals($startDateString, $absence->getFirstDay());
				self::assertEquals($endDateString, $absence->getLastDay());
				return $absence;
			});
		$this->timezoneService->expects(self::once())
			->method('getUserTimezone')
			->with('user')
			->willReturn($tz->getName());
		$this->timeFactory->expects(self::once())
			->method('getTime')
			->willReturn((new DateTimeImmutable('2023-01-01', $tz))->getTimestamp());
		$this->jobList->expects(self::exactly(2))
			->method('scheduleAfter')
			->willReturnMap([
				[OutOfOfficeEventDispatcherJob::class, $startDate->getTimestamp(), [
					'id' => '1',
					'event' => OutOfOfficeEventDispatcherJob::EVENT_START,
				]],
				[OutOfOfficeEventDispatcherJob::class, $endDate->getTimestamp() + 3600 * 23 + 59 * 60, [
					'id' => '1',
					'event' => OutOfOfficeEventDispatcherJob::EVENT_END,
				]],
			]);

		$this->absenceService->createOrUpdateAbsence(
			$user,
			$startDateString,
			$endDateString,
			'',
			'',
		);
	}

	public function testUpdateSchedulesOnlyEndJob(): void {
		$tz = new DateTimeZone('Europe/Berlin');
		$endDateString = '2023-01-10';
		$endDate = new DateTimeImmutable($endDateString, $tz);
		$user = $this->createMock(IUser::class);
		$user->method('getUID')
			->willReturn('user');
		$absence = new Absence();
		$absence->setId(1);
		$absence->setFirstDay('1970-01-01');
		$absence->setLastDay('1970-01-10');
		$absence->setStatus('old status');
		$absence->setMessage('old message');

		$this->absenceMapper->expects(self::once())
			->method('findByUserId')
			->with('user')
			->willReturn($absence);
		$this->absenceMapper->expects(self::once())
			->method('update')
			->willReturnCallback(static function (Absence $absence) use ($endDateString): Absence {
				self::assertEquals('2023-01-05', $absence->getFirstDay());
				self::assertEquals($endDateString, $absence->getLastDay());
				return $absence;
			});
		$this->timezoneService->expects(self::once())
			->method('getUserTimezone')
			->with('user')
			->willReturn($tz->getName());
		$this->timeFactory->expects(self::once())
			->method('getTime')
			->willReturn((new DateTimeImmutable('2023-01-07', $tz))->getTimestamp());
		$this->jobList->expects(self::once())
			->method('scheduleAfter')
			->with(OutOfOfficeEventDispatcherJob::class, $endDate->getTimestamp() + 23 * 3600 + 59 * 60, [
				'id' => '1',
				'event' => OutOfOfficeEventDispatcherJob::EVENT_END,
			]);

		$this->absenceService->createOrUpdateAbsence(
			$user,
			'2023-01-05',
			$endDateString,
			'',
			'',
		);
	}

	public function testUpdateAbsenceSchedulesNoJob(): void {
		$tz = new DateTimeZone('Europe/Berlin');
		$user = $this->createMock(IUser::class);
		$user->method('getUID')
			->willReturn('user');
		$absence = new Absence();
		$absence->setId(1);
		$absence->setFirstDay('1970-01-01');
		$absence->setLastDay('1970-01-10');
		$absence->setStatus('old status');
		$absence->setMessage('old message');

		$this->absenceMapper->expects(self::once())
			->method('findByUserId')
			->with('user')
			->willReturn($absence);
		$this->absenceMapper->expects(self::once())
			->method('update')
			->willReturnCallback(static function (Absence $absence): Absence {
				self::assertEquals('2023-01-05', $absence->getFirstDay());
				self::assertEquals('2023-01-10', $absence->getLastDay());
				return $absence;
			});
		$this->timezoneService->expects(self::once())
			->method('getUserTimezone')
			->with('user')
			->willReturn($tz->getName());
		$this->timeFactory->expects(self::once())
			->method('getTime')
			->willReturn((new DateTimeImmutable('2023-01-12', $tz))->getTimestamp());
		$this->jobList->expects(self::never())
			->method('scheduleAfter');

		$this->absenceService->createOrUpdateAbsence(
			$user,
			'2023-01-05',
			'2023-01-10',
			'',
			'',
		);
	}
}
