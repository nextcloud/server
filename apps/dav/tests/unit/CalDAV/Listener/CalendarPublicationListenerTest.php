<?php
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Tests\unit\CalDAV\Listeners;

use OCA\DAV\CalDAV\Activity\Backend;
use OCA\DAV\Events\CalendarPublishedEvent;
use OCA\DAV\Events\CalendarUnpublishedEvent;
use OCA\DAV\Listener\CalendarPublicationListener;
use OCP\EventDispatcher\Event;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class CalendarPublicationListenerTest extends TestCase {

	/** @var Backend|MockObject */
	private $activityBackend;

	/** @var LoggerInterface|MockObject */
	private $logger;

	private CalendarPublicationListener $calendarPublicationListener;

	/** @var CalendarPublishedEvent|MockObject */
	private $publicationEvent;

	/** @var CalendarUnpublishedEvent|MockObject */
	private $unpublicationEvent;

	protected function setUp(): void {
		parent::setUp();

		$this->activityBackend = $this->createMock(Backend::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->publicationEvent = $this->createMock(CalendarPublishedEvent::class);
		$this->unpublicationEvent = $this->createMock(CalendarUnpublishedEvent::class);
		$this->calendarPublicationListener = new CalendarPublicationListener($this->activityBackend, $this->logger);
	}

	public function testInvalidEvent(): void {
		$this->activityBackend->expects($this->never())->method('onCalendarPublication');
		$this->logger->expects($this->never())->method('debug');
		$this->calendarPublicationListener->handle(new Event());
	}

	public function testPublicationEvent(): void {
		$this->publicationEvent->expects($this->once())->method('getCalendarData')->with()->willReturn([]);
		$this->activityBackend->expects($this->once())->method('onCalendarPublication')->with([], true);
		$this->logger->expects($this->once())->method('debug');
		$this->calendarPublicationListener->handle($this->publicationEvent);
	}

	public function testUnPublicationEvent(): void {
		$this->unpublicationEvent->expects($this->once())->method('getCalendarData')->with()->willReturn([]);
		$this->activityBackend->expects($this->once())->method('onCalendarPublication')->with([], false);
		$this->logger->expects($this->once())->method('debug');
		$this->calendarPublicationListener->handle($this->unpublicationEvent);
	}
}
