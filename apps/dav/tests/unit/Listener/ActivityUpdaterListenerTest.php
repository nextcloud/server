<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Tests\unit\Listener;

use OCA\DAV\CalDAV\Activity\Backend as ActivityBackend;
use OCA\DAV\CalDAV\Activity\Provider\Event;
use OCA\DAV\DAV\Sharing\Plugin as SharingPlugin;
use OCA\DAV\Events\CalendarDeletedEvent;
use OCA\DAV\Listener\ActivityUpdaterListener;
use OCP\Calendar\Events\CalendarObjectDeletedEvent;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class ActivityUpdaterListenerTest extends TestCase {

	private ActivityBackend&MockObject $activityBackend;
	private LoggerInterface&MockObject $logger;
	private ActivityUpdaterListener $listener;

	protected function setUp(): void {
		parent::setUp();

		$this->activityBackend = $this->createMock(ActivityBackend::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		$this->listener = new ActivityUpdaterListener(
			$this->activityBackend,
			$this->logger
		);
	}

	/**
	 * @dataProvider dataForTestHandleCalendarObjectDeletedEvent
	 */
	public function testHandleCalendarObjectDeletedEvent(int $calendarId, array $calendarData, array $shares, array $objectData, bool $createsActivity): void {
		$event = new CalendarObjectDeletedEvent($calendarId, $calendarData, $shares, $objectData);
		$this->logger->expects($this->once())->method('debug')->with(
			$createsActivity ? "Activity generated for deleted calendar object in calendar $calendarId" : "Calendar object in calendar $calendarId was already in trashbin, skipping deletion activity"
		);
		$this->activityBackend->expects($createsActivity ? $this->once() : $this->never())->method('onTouchCalendarObject')->with(
			Event::SUBJECT_OBJECT_DELETE,
			$calendarData,
			$shares,
			$objectData
		);
		$this->listener->handle($event);
	}

	public static function dataForTestHandleCalendarObjectDeletedEvent(): array {
		return [
			[1, [], [], [], true],
			[1, [], [], ['{' . SharingPlugin::NS_NEXTCLOUD . '}deleted-at' => 120], false],
		];
	}

	/**
	 * @dataProvider dataForTestHandleCalendarDeletedEvent
	 */
	public function testHandleCalendarDeletedEvent(int $calendarId, array $calendarData, array $shares, bool $createsActivity): void {
		$event = new CalendarDeletedEvent($calendarId, $calendarData, $shares);
		$this->logger->expects($this->once())->method('debug')->with(
			$createsActivity ? "Activity generated for deleted calendar $calendarId" : "Calendar $calendarId was already in trashbin, skipping deletion activity"
		);
		$this->activityBackend->expects($createsActivity ? $this->once() : $this->never())->method('onCalendarDelete')->with(
			$calendarData,
			$shares
		);
		$this->listener->handle($event);
	}

	public static function dataForTestHandleCalendarDeletedEvent(): array {
		return [
			[1, [], [], true],
			[1, ['{' . SharingPlugin::NS_NEXTCLOUD . '}deleted-at' => 120], [], false],
		];
	}
}
