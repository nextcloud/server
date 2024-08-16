<?php
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Tests\unit\CalDAV\Listeners;

use OCA\DAV\CalDAV\Activity\Backend;
use OCA\DAV\Events\CalendarShareUpdatedEvent;
use OCA\DAV\Listener\CalendarShareUpdateListener;
use OCP\EventDispatcher\Event;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class CalendarShareUpdateListenerTest extends TestCase {

	/** @var Backend|MockObject */
	private $activityBackend;

	/** @var LoggerInterface|MockObject */
	private $logger;

	private CalendarShareUpdateListener $calendarPublicationListener;

	/** @var CalendarShareUpdatedEvent|MockObject */
	private $event;

	protected function setUp(): void {
		parent::setUp();

		$this->activityBackend = $this->createMock(Backend::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->event = $this->createMock(CalendarShareUpdatedEvent::class);
		$this->calendarPublicationListener = new CalendarShareUpdateListener($this->activityBackend, $this->logger);
	}

	public function testInvalidEvent(): void {
		$this->activityBackend->expects($this->never())->method('onCalendarUpdateShares');
		$this->logger->expects($this->never())->method('debug');
		$this->calendarPublicationListener->handle(new Event());
	}

	public function testEvent(): void {
		$this->event->expects($this->once())->method('getCalendarData')->with()->willReturn([]);
		$this->event->expects($this->once())->method('getOldShares')->with()->willReturn([]);
		$this->event->expects($this->once())->method('getAdded')->with()->willReturn([]);
		$this->event->expects($this->once())->method('getRemoved')->with()->willReturn([]);
		$this->activityBackend->expects($this->once())->method('onCalendarUpdateShares')->with([], [], [], []);
		$this->logger->expects($this->once())->method('debug');
		$this->calendarPublicationListener->handle($this->event);
	}
}
