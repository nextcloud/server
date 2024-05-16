<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\Unit\Listener;

use OCA\DAV\BackgroundJob\UpdateCalendarResourcesRoomsBackgroundJob;
use OCA\DAV\Events\ScheduleResourcesRoomsUpdateEvent;
use OCA\DAV\Listener\UpdateCalendarResourcesRoomsListener;
use OCP\BackgroundJob\IJobList;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

/**
 * @covers \OCA\DAV\Listener\UpdateCalendarResourcesRoomsListener
 */
class UpdateCalendarResourcesRoomsListenerTest extends TestCase {
	private UpdateCalendarResourcesRoomsListener $listener;

	/** @var IJobList|MockObject */
	private $jobList;

	protected function setUp(): void {
		parent::setUp();

		$this->jobList = $this->createMock(IJobList::class);

		$this->listener = new UpdateCalendarResourcesRoomsListener(
			$this->jobList,
		);
	}

	public function testHandle(): void {
		$jobs = [
			$this->createMock(UpdateCalendarResourcesRoomsBackgroundJob::class),
		];
		$this->jobList->expects(self::once())
			->method('getJobsIterator')
			->with(UpdateCalendarResourcesRoomsBackgroundJob::class, null, 0)
			->willReturn($jobs);
		$this->jobList->expects(self::once())
			->method('resetBackgroundJob')
			->with($jobs[0]);

		$this->listener->handle(new ScheduleResourcesRoomsUpdateEvent());
	}

	public function testHandleNoJob(): void {
		$jobs = [];
		$this->jobList->expects(self::once())
			->method('getJobsIterator')
			->with(UpdateCalendarResourcesRoomsBackgroundJob::class, null, 0)
			->willReturn($jobs);
		$this->jobList->expects(self::never())
			->method('resetBackgroundJob');

		$this->listener->handle(new ScheduleResourcesRoomsUpdateEvent());
	}
}
