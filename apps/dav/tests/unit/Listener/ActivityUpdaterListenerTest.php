<?php

declare(strict_types=1);

/**
 * @copyright 2022 Thomas Citharel <nextcloud@tcit.fr>
 *
 * @author Thomas Citharel <nextcloud@tcit.fr>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\DAV\Tests\Unit\Listener;

use OCA\DAV\CalDAV\Activity\Backend as ActivityBackend;
use OCA\DAV\CalDAV\Activity\Provider\Event;
use OCA\DAV\DAV\Sharing\Plugin as SharingPlugin;
use OCA\DAV\Events\CalendarDeletedEvent;
use OCA\DAV\Events\CalendarObjectDeletedEvent;
use OCA\DAV\Listener\ActivityUpdaterListener;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class ActivityUpdaterListenerTest extends TestCase {

	/** @var ActivityBackend|MockObject */
	private $activityBackend;
	/** @var LoggerInterface|MockObject */
	private $logger;
	/** @var ActivityUpdaterListener */
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

	public function dataForTestHandleCalendarObjectDeletedEvent(): array {
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

	public function dataForTestHandleCalendarDeletedEvent(): array {
		return [
			[1, [], [], true],
			[1, ['{' . SharingPlugin::NS_NEXTCLOUD . '}deleted-at' => 120], [], false],
		];
	}
}
