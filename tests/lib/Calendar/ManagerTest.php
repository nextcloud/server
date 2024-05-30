<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Calendar;

use OC\AppFramework\Bootstrap\Coordinator;
use OC\Calendar\Manager;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Calendar\ICalendar;
use OCP\Calendar\ICreateFromString;
use OCP\Calendar\IHandleImipMessage;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Sabre\VObject\Document;
use Sabre\VObject\Reader;
use Test\TestCase;

/*
 * This allows us to create Mock object supporting both interfaces
 */
interface ICreateFromStringAndHandleImipMessage extends ICreateFromString, IHandleImipMessage {
}

class ManagerTest extends TestCase {
	/** @var Coordinator|MockObject */
	private $coordinator;

	/** @var MockObject|ContainerInterface */
	private $container;

	/** @var MockObject|LoggerInterface */
	private $logger;

	/** @var Manager */
	private $manager;

	/** @var ITimeFactory|ITimeFactory&MockObject|MockObject  */
	private $time;

	protected function setUp(): void {
		parent::setUp();

		$this->coordinator = $this->createMock(Coordinator::class);
		$this->container = $this->createMock(ContainerInterface::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->time = $this->createMock(ITimeFactory::class);

		$this->manager = new Manager(
			$this->coordinator,
			$this->container,
			$this->logger,
			$this->time,
		);
	}

	/**
	 * @dataProvider searchProvider
	 */
	public function testSearch($search1, $search2, $expected) {
		/** @var ICalendar | MockObject $calendar1 */
		$calendar1 = $this->createMock(ICalendar::class);
		$calendar1->method('getKey')->willReturn('simple:1');
		$calendar1->expects($this->once())
			->method('search')
			->with('', [], [], null, null)
			->willReturn($search1);

		/** @var ICalendar | MockObject $calendar2 */
		$calendar2 = $this->createMock(ICalendar::class);
		$calendar2->method('getKey')->willReturn('simple:2');
		$calendar2->expects($this->once())
			->method('search')
			->with('', [], [], null, null)
			->willReturn($search2);

		$this->manager->registerCalendar($calendar1);
		$this->manager->registerCalendar($calendar2);

		$result = $this->manager->search('');
		$this->assertEquals($expected, $result);
	}

	/**
	 * @dataProvider searchProvider
	 */
	public function testSearchOptions($search1, $search2, $expected) {
		/** @var ICalendar | MockObject $calendar1 */
		$calendar1 = $this->createMock(ICalendar::class);
		$calendar1->method('getKey')->willReturn('simple:1');
		$calendar1->expects($this->once())
			->method('search')
			->with('searchTerm', ['SUMMARY', 'DESCRIPTION'],
				['timerange' => ['start' => null, 'end' => null]], 5, 20)
			->willReturn($search1);

		/** @var ICalendar | MockObject $calendar2 */
		$calendar2 = $this->createMock(ICalendar::class);
		$calendar2->method('getKey')->willReturn('simple:2');
		$calendar2->expects($this->once())
			->method('search')
			->with('searchTerm', ['SUMMARY', 'DESCRIPTION'],
				['timerange' => ['start' => null, 'end' => null]], 5, 20)
			->willReturn($search2);

		$this->manager->registerCalendar($calendar1);
		$this->manager->registerCalendar($calendar2);

		$result = $this->manager->search('searchTerm', ['SUMMARY', 'DESCRIPTION'],
			['timerange' => ['start' => null, 'end' => null]], 5, 20);
		$this->assertEquals($expected, $result);
	}

	public function searchProvider() {
		$search1 = [
			[
				'id' => 1,
				'data' => 'foobar',
			],
			[
				'id' => 2,
				'data' => 'barfoo',
			]
		];
		$search2 = [
			[
				'id' => 3,
				'data' => 'blablub',
			],
			[
				'id' => 4,
				'data' => 'blubbla',
			]
		];

		$expected = [
			[
				'id' => 1,
				'data' => 'foobar',
				'calendar-key' => 'simple:1',
			],
			[
				'id' => 2,
				'data' => 'barfoo',
				'calendar-key' => 'simple:1',
			],
			[
				'id' => 3,
				'data' => 'blablub',
				'calendar-key' => 'simple:2',
			],
			[
				'id' => 4,
				'data' => 'blubbla',
				'calendar-key' => 'simple:2',
			]
		];

		return [
			[
				$search1,
				$search2,
				$expected
			]
		];
	}

	public function testRegisterUnregister() {
		/** @var ICalendar | MockObject $calendar1 */
		$calendar1 = $this->createMock(ICalendar::class);
		$calendar1->method('getKey')->willReturn('key1');

		/** @var ICalendar | MockObject $calendar2 */
		$calendar2 = $this->createMock(ICalendar::class);
		$calendar2->method('getKey')->willReturn('key2');

		$this->manager->registerCalendar($calendar1);
		$this->manager->registerCalendar($calendar2);

		$result = $this->manager->getCalendars();
		$this->assertCount(2, $result);
		$this->assertContains($calendar1, $result);
		$this->assertContains($calendar2, $result);

		$this->manager->unregisterCalendar($calendar1);

		$result = $this->manager->getCalendars();
		$this->assertCount(1, $result);
		$this->assertContains($calendar2, $result);
	}

	public function testGetCalendars() {
		/** @var ICalendar | MockObject $calendar1 */
		$calendar1 = $this->createMock(ICalendar::class);
		$calendar1->method('getKey')->willReturn('key1');

		/** @var ICalendar | MockObject $calendar2 */
		$calendar2 = $this->createMock(ICalendar::class);
		$calendar2->method('getKey')->willReturn('key2');

		$this->manager->registerCalendar($calendar1);
		$this->manager->registerCalendar($calendar2);

		$result = $this->manager->getCalendars();
		$this->assertCount(2, $result);
		$this->assertContains($calendar1, $result);
		$this->assertContains($calendar2, $result);

		$this->manager->clear();

		$result = $this->manager->getCalendars();

		$this->assertCount(0, $result);
	}

	public function testEnabledIfNot() {
		$isEnabled = $this->manager->isEnabled();
		$this->assertFalse($isEnabled);
	}

	public function testIfEnabledIfSo() {
		/** @var ICalendar | MockObject $calendar */
		$calendar = $this->createMock(ICalendar::class);
		$this->manager->registerCalendar($calendar);

		$isEnabled = $this->manager->isEnabled();
		$this->assertTrue($isEnabled);
	}

	public function testHandleImipReplyWrongMethod(): void {
		$principalUri = 'principals/user/linus';
		$sender = 'pierre@general-store.com';
		$recipient = 'linus@stardew-tent-living.com';
		$calendarData = $this->getVCalendarReply();
		$calendarData->METHOD = 'REQUEST';

		$this->logger->expects(self::once())
			->method('warning');
		$this->time->expects(self::never())
			->method('getTime');

		$result = $this->manager->handleIMipReply($principalUri, $sender, $recipient, $calendarData->serialize());
		$this->assertFalse($result);
	}

	public function testHandleImipReplyOrganizerNotRecipient(): void {
		$principalUri = 'principals/user/linus';
		$recipient = 'pierre@general-store.com';
		$sender = 'linus@stardew-tent-living.com';
		$calendarData = $this->getVCalendarReply();

		$this->logger->expects(self::once())
			->method('warning');
		$this->time->expects(self::never())
			->method('getTime');

		$result = $this->manager->handleIMipReply($principalUri, $sender, $recipient, $calendarData->serialize());
		$this->assertFalse($result);
	}

	public function testHandleImipReplyDateInThePast(): void {
		$principalUri = 'principals/user/linus';
		$sender = 'pierre@general-store.com';
		$recipient = 'linus@stardew-tent-living.com';
		$calendarData = $this->getVCalendarReply();
		$calendarData->VEVENT->DTSTART = new \DateTime('2013-04-07'); // set to in the past

		$this->time->expects(self::once())
			->method('getTime')
			->willReturn(time());

		$this->logger->expects(self::once())
			->method('warning');

		$result = $this->manager->handleIMipReply($principalUri, $sender, $recipient, $calendarData->serialize());
		$this->assertFalse($result);
	}

	public function testHandleImipReplyNoCalendars(): void {
		/** @var Manager | \PHPUnit\Framework\MockObject\MockObject $manager */
		$manager = $this->getMockBuilder(Manager::class)
			->setConstructorArgs([
				$this->coordinator,
				$this->container,
				$this->logger,
				$this->time
			])
			->setMethods([
				'getCalendarsForPrincipal'
			])
			->getMock();
		$principalUri = 'principals/user/linus';
		$sender = 'pierre@general-store.com';
		$recipient = 'linus@stardew-tent-living.com';
		$calendarData = $this->getVCalendarReply();

		$this->time->expects(self::once())
			->method('getTime')
			->willReturn(1628374233);
		$manager->expects(self::once())
			->method('getCalendarsForPrincipal')
			->willReturn([]);
		$this->logger->expects(self::once())
			->method('warning');

		$result = $manager->handleIMipReply($principalUri, $sender, $recipient, $calendarData->serialize());
		$this->assertFalse($result);
	}

	public function testHandleImipReplyEventNotFound(): void {
		/** @var Manager | \PHPUnit\Framework\MockObject\MockObject $manager */
		$manager = $this->getMockBuilder(Manager::class)
			->setConstructorArgs([
				$this->coordinator,
				$this->container,
				$this->logger,
				$this->time
			])
			->setMethods([
				'getCalendarsForPrincipal'
			])
			->getMock();
		$calendar = $this->createMock(ICreateFromStringAndHandleImipMessage::class);
		$principalUri = 'principals/user/linus';
		$sender = 'pierre@general-store.com';
		$recipient = 'linus@stardew-tent-living.com';
		$calendarData = $this->getVCalendarReply();

		$this->time->expects(self::once())
			->method('getTime')
			->willReturn(1628374233);
		$manager->expects(self::once())
			->method('getCalendarsForPrincipal')
			->willReturn([$calendar]);
		$calendar->expects(self::once())
			->method('search')
			->willReturn([]);
		$this->logger->expects(self::once())
			->method('info');
		$calendar->expects(self::never())
			->method('handleIMipMessage');

		$result = $manager->handleIMipReply($principalUri, $sender, $recipient, $calendarData->serialize());
		$this->assertFalse($result);
	}

	public function testHandleImipReply(): void {
		/** @var Manager | \PHPUnit\Framework\MockObject\MockObject $manager */
		$manager = $this->getMockBuilder(Manager::class)
			->setConstructorArgs([
				$this->coordinator,
				$this->container,
				$this->logger,
				$this->time
			])
			->setMethods([
				'getCalendarsForPrincipal'
			])
			->getMock();
		$calendar = $this->createMock(ICreateFromStringAndHandleImipMessage::class);
		$principalUri = 'principals/user/linus';
		$sender = 'pierre@general-store.com';
		$recipient = 'linus@stardew-tent-living.com';
		$calendarData = $this->getVCalendarReply();

		$this->time->expects(self::once())
			->method('getTime')
			->willReturn(1628374233);
		$manager->expects(self::once())
			->method('getCalendarsForPrincipal')
			->willReturn([$calendar]);
		$calendar->expects(self::once())
			->method('search')
			->willReturn([['uri' => 'testname.ics']]);
		$calendar->expects(self::once())
			->method('handleIMipMessage')
			->with('testname.ics', $calendarData->serialize());

		$result = $manager->handleIMipReply($principalUri, $sender, $recipient, $calendarData->serialize());
		$this->assertTrue($result);
	}

	public function testHandleImipCancelWrongMethod(): void {
		$principalUri = 'principals/user/pierre';
		$sender = 'linus@stardew-tent-living.com';
		$recipient = 'pierre@general-store.com';
		$replyTo = null;
		$calendarData = $this->getVCalendarCancel();
		$calendarData->METHOD = 'REQUEST';

		$this->logger->expects(self::once())
			->method('warning');
		$this->time->expects(self::never())
			->method('getTime');

		$result = $this->manager->handleIMipCancel($principalUri, $sender, $replyTo, $recipient, $calendarData->serialize());
		$this->assertFalse($result);
	}

	public function testHandleImipCancelAttendeeNotRecipient(): void {
		$principalUri = '/user/admin';
		$sender = 'linus@stardew-tent-living.com';
		$recipient = 'leah@general-store.com';
		$replyTo = null;
		$calendarData = $this->getVCalendarCancel();

		$this->logger->expects(self::once())
			->method('warning');
		$this->time->expects(self::never())
			->method('getTime');

		$result = $this->manager->handleIMipCancel($principalUri, $sender, $replyTo, $recipient, $calendarData->serialize());
		$this->assertFalse($result);
	}

	public function testHandleImipCancelDateInThePast(): void {
		$principalUri = 'principals/user/pierre';
		$sender = 'linus@stardew-tent-living.com';
		$recipient = 'pierre@general-store.com';
		$replyTo = null;
		$calendarData = $this->getVCalendarCancel();
		$calendarData->VEVENT->DTSTART = new \DateTime('2013-04-07'); // set to in the past

		$this->time->expects(self::once())
			->method('getTime')
			->willReturn(time());
		$this->logger->expects(self::once())
			->method('warning');

		$result = $this->manager->handleIMipCancel($principalUri, $sender, $replyTo, $recipient, $calendarData->serialize());
		$this->assertFalse($result);
	}

	public function testHandleImipCancelNoCalendars(): void {
		/** @var Manager | \PHPUnit\Framework\MockObject\MockObject $manager */
		$manager = $this->getMockBuilder(Manager::class)
			->setConstructorArgs([
				$this->coordinator,
				$this->container,
				$this->logger,
				$this->time
			])
			->setMethods([
				'getCalendarsForPrincipal'
			])
			->getMock();
		$principalUri = 'principals/user/pierre';
		$sender = 'linus@stardew-tent-living.com';
		$recipient = 'pierre@general-store.com';
		$replyTo = null;
		$calendarData = $this->getVCalendarCancel();

		$this->time->expects(self::once())
			->method('getTime')
			->willReturn(1628374233);
		$manager->expects(self::once())
			->method('getCalendarsForPrincipal')
			->with($principalUri)
			->willReturn([]);
		$this->logger->expects(self::once())
			->method('warning');

		$result = $manager->handleIMipCancel($principalUri, $sender, $replyTo, $recipient, $calendarData->serialize());
		$this->assertFalse($result);
	}

	public function testHandleImipCancelOrganiserInReplyTo(): void {
		/** @var Manager | \PHPUnit\Framework\MockObject\MockObject $manager */
		$manager = $this->getMockBuilder(Manager::class)
			->setConstructorArgs([
				$this->coordinator,
				$this->container,
				$this->logger,
				$this->time
			])
			->setMethods([
				'getCalendarsForPrincipal'
			])
			->getMock();
		$principalUri = 'principals/user/pierre';
		$sender = 'clint@stardew-blacksmiths.com';
		$recipient = 'pierre@general-store.com';
		$replyTo = 'linus@stardew-tent-living.com';
		$calendar = $this->createMock(ICreateFromStringAndHandleImipMessage::class);
		$calendarData = $this->getVCalendarCancel();

		$this->time->expects(self::once())
			->method('getTime')
			->willReturn(1628374233);
		$manager->expects(self::once())
			->method('getCalendarsForPrincipal')
			->with($principalUri)
			->willReturn([$calendar]);
		$calendar->expects(self::once())
			->method('search')
			->willReturn([['uri' => 'testname.ics']]);
		$calendar->expects(self::once())
			->method('handleIMipMessage')
			->with('testname.ics', $calendarData->serialize());
		$result = $manager->handleIMipCancel($principalUri, $sender, $replyTo, $recipient, $calendarData->serialize());
		$this->assertTrue($result);
	}

	public function testHandleImipCancel(): void {
		/** @var Manager | \PHPUnit\Framework\MockObject\MockObject $manager */
		$manager = $this->getMockBuilder(Manager::class)
			->setConstructorArgs([
				$this->coordinator,
				$this->container,
				$this->logger,
				$this->time
			])
			->setMethods([
				'getCalendarsForPrincipal'
			])
			->getMock();
		$principalUri = 'principals/user/pierre';
		$sender = 'linus@stardew-tent-living.com';
		$recipient = 'pierre@general-store.com';
		$replyTo = null;
		$calendar = $this->createMock(ICreateFromStringAndHandleImipMessage::class);
		$calendarData = $this->getVCalendarCancel();

		$this->time->expects(self::once())
			->method('getTime')
			->willReturn(1628374233);
		$manager->expects(self::once())
			->method('getCalendarsForPrincipal')
			->with($principalUri)
			->willReturn([$calendar]);
		$calendar->expects(self::once())
			->method('search')
			->willReturn([['uri' => 'testname.ics']]);
		$calendar->expects(self::once())
			->method('handleIMipMessage')
			->with('testname.ics', $calendarData->serialize());
		$result = $manager->handleIMipCancel($principalUri, $sender, $replyTo, $recipient, $calendarData->serialize());
		$this->assertTrue($result);
	}

	private function getVCalendarReply(): Document {
		$data = <<<EOF
BEGIN:VCALENDAR
PRODID:-//Nextcloud/Nextcloud CalDAV Server//EN
VERSION:2.0
CALSCALE:GREGORIAN
METHOD:REPLY
BEGIN:VEVENT
DTSTART;VALUE=DATE:20210820
DTEND;VALUE=DATE:20220821
DTSTAMP:20210812T100040Z
ORGANIZER;CN=admin:mailto:linus@stardew-tent-living.com
UID:dcc733bf-b2b2-41f2-a8cf-550ae4b67aff
ATTENDEE;CUTYPE=INDIVIDUAL;ROLE=REQ-PARTICIPANT;PARTSTAT=ACCEPTED;CN=pierr
 e@general-store.com;X-NUM-GUESTS=0:mailto:pierre@general-store.com
CREATED:20220812T100021Z
DESCRIPTION:
LAST-MODIFIED:20220812T100040Z
LOCATION:
SEQUENCE:3
STATUS:CONFIRMED
SUMMARY:berry basket
TRANSP:OPAQUE
END:VEVENT
END:VCALENDAR
EOF;
		return Reader::read($data);
	}

	private function getVCalendarCancel(): Document {
		$data = <<<EOF
BEGIN:VCALENDAR
PRODID:-//Nextcloud/Nextcloud CalDAV Server//EN
VERSION:2.0
CALSCALE:GREGORIAN
METHOD:CANCEL
BEGIN:VEVENT
DTSTART;VALUE=DATE:20210820
DTEND;VALUE=DATE:20220821
DTSTAMP:20210812T100040Z
ORGANIZER;CN=admin:mailto:linus@stardew-tent-living.com
UID:dcc733bf-b2b2-41f2-a8cf-550ae4b67aff
ATTENDEE;CUTYPE=INDIVIDUAL;ROLE=REQ-PARTICIPANT;PARTSTAT=ACCEPTED;CN=pierr
 e@general-store.com;X-NUM-GUESTS=0:mailto:pierre@general-store.com
CREATED:20220812T100021Z
DESCRIPTION:
LAST-MODIFIED:20220812T100040Z
LOCATION:
SEQUENCE:3
STATUS:CANCELLED
SUMMARY:berry basket
TRANSP:OPAQUE
END:VEVENT
END:VCALENDAR
EOF;
		return Reader::read($data);
	}
}
