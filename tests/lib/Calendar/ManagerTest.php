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
use Sabre\VObject\Component\VCalendar;
use Test\TestCase;

/*
 * This allows us to create Mock object supporting both interfaces
 */
interface ITestCalendar extends ICreateFromString, IHandleImipMessage {
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

	/** @var ITimeFactory|ITimeFactory&MockObject|MockObject */
	private $time;

	private VCalendar $vCalendar1a;
	private VCalendar $vCalendar2a;
	private VCalendar $vCalendar3a;

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

		// construct calendar with a 1 hour event and same start/end time zones
		$this->vCalendar1a = new VCalendar();
		/** @var VEvent $vEvent */
		$vEvent = $this->vCalendar1a->add('VEVENT', []);
		$vEvent->UID->setValue('96a0e6b1-d886-4a55-a60d-152b31401dcc');
		$vEvent->add('DTSTART', '20240701T080000', ['TZID' => 'America/Toronto']);
		$vEvent->add('DTEND', '20240701T090000', ['TZID' => 'America/Toronto']);
		$vEvent->add('SUMMARY', 'Test Event');
		$vEvent->add('SEQUENCE', 3);
		$vEvent->add('STATUS', 'CONFIRMED');
		$vEvent->add('TRANSP', 'OPAQUE');
		$vEvent->add('ORGANIZER', 'mailto:organizer@testing.com', ['CN' => 'Organizer']);
		$vEvent->add('ATTENDEE', 'mailto:attendee1@testing.com', [
			'CN' => 'Attendee One',
			'CUTYPE' => 'INDIVIDUAL',
			'PARTSTAT' => 'NEEDS-ACTION',
			'ROLE' => 'REQ-PARTICIPANT',
			'RSVP' => 'TRUE'
		]);

		// construct calendar with a event for reply
		$this->vCalendar2a = new VCalendar();
		/** @var VEvent $vEvent */
		$vEvent = $this->vCalendar2a->add('VEVENT', []);
		$vEvent->UID->setValue('dcc733bf-b2b2-41f2-a8cf-550ae4b67aff');
		$vEvent->add('DTSTART', '20210820');
		$vEvent->add('DTEND', '20220821');
		$vEvent->add('SUMMARY', 'berry basket');
		$vEvent->add('SEQUENCE', 3);
		$vEvent->add('STATUS', 'CONFIRMED');
		$vEvent->add('TRANSP', 'OPAQUE');
		$vEvent->add('ORGANIZER', 'mailto:linus@stardew-tent-living.com', ['CN' => 'admin']);
		$vEvent->add('ATTENDEE', 'mailto:pierre@general-store.com', [
			'CN' => 'pierre@general-store.com',
			'CUTYPE' => 'INDIVIDUAL',
			'ROLE' => 'REQ-PARTICIPANT',
			'PARTSTAT' => 'ACCEPTED',
		]);

		// construct calendar with a event for reply
		$this->vCalendar3a = new VCalendar();
		/** @var VEvent $vEvent */
		$vEvent = $this->vCalendar3a->add('VEVENT', []);
		$vEvent->UID->setValue('dcc733bf-b2b2-41f2-a8cf-550ae4b67aff');
		$vEvent->add('DTSTART', '20210820');
		$vEvent->add('DTEND', '20220821');
		$vEvent->add('SUMMARY', 'berry basket');
		$vEvent->add('SEQUENCE', 3);
		$vEvent->add('STATUS', 'CANCELLED');
		$vEvent->add('TRANSP', 'OPAQUE');
		$vEvent->add('ORGANIZER', 'mailto:linus@stardew-tent-living.com', ['CN' => 'admin']);
		$vEvent->add('ATTENDEE', 'mailto:pierre@general-store.com', [
			'CN' => 'pierre@general-store.com',
			'CUTYPE' => 'INDIVIDUAL',
			'ROLE' => 'REQ-PARTICIPANT',
			'PARTSTAT' => 'ACCEPTED',
		]);

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

	public function testHandleImipReplyWithNoCalendars(): void {
		// construct calendar manager returns
		/** @var Manager&MockObject $manager */
		$manager = $this->getMockBuilder(Manager::class)
			->setConstructorArgs([
				$this->coordinator,
				$this->container,
				$this->logger,
				$this->time,
			])
			->onlyMethods(['getCalendarsForPrincipal'])
			->getMock();
		$manager->expects(self::once())
			->method('getCalendarsForPrincipal')
			->willReturn([]);
		// construct logger returns
		$this->logger->expects(self::once())->method('warning')
			->with('iMip message could not be processed because user has no calendars');
		// construct parameters
		$principalUri = 'principals/user/linus';
		$sender = 'pierre@general-store.com';
		$recipient = 'linus@stardew-tent-living.com';
		$calendar = $this->vCalendar2a;
		$calendar->add('METHOD', 'REPLY');
		// Act
		$result = $manager->handleIMipReply($principalUri, $sender, $recipient, $calendar->serialize());
		// Assert
		$this->assertFalse($result);
	}

	public function testHandleImipReplyWithInvalidData(): void {
		// construct mock user calendar
		$userCalendar = $this->createMock(ITestCalendar::class);
		// construct mock calendar manager and returns
		/** @var Manager&MockObject $manager */
		$manager = $this->getMockBuilder(Manager::class)
			->setConstructorArgs([
				$this->coordinator,
				$this->container,
				$this->logger,
				$this->time,
			])
			->onlyMethods(['getCalendarsForPrincipal'])
			->getMock();
		$manager->expects(self::once())
			->method('getCalendarsForPrincipal')
			->willReturn([$userCalendar]);
		// construct logger returns
		$this->logger->expects(self::once())->method('error')
			->with('iMip message could not be processed because an error occurred while parsing the iMip message');
		// construct parameters
		$principalUri = 'principals/user/attendee1';
		$sender = 'organizer@testing.com';
		$recipient = 'attendee1@testing.com';
		// Act
		$result = $manager->handleIMipReply($principalUri, $sender, $recipient, 'Invalid data');
		// Assert
		$this->assertFalse($result);
	}

	public function testHandleImipReplyWithNoMethod(): void {
		// construct mock user calendar
		$userCalendar = $this->createMock(ITestCalendar::class);
		// construct mock calendar manager and returns
		/** @var Manager&MockObject $manager */
		$manager = $this->getMockBuilder(Manager::class)
			->setConstructorArgs([
				$this->coordinator,
				$this->container,
				$this->logger,
				$this->time,
			])
			->onlyMethods(['getCalendarsForPrincipal'])
			->getMock();
		$manager->expects(self::once())
			->method('getCalendarsForPrincipal')
			->willReturn([$userCalendar]);
		// construct logger returns
		$this->logger->expects(self::once())->method('warning')
			->with('iMip message contains an incorrect or invalid method');
		// construct parameters
		$principalUri = 'principals/user/linus';
		$sender = 'pierre@general-store.com';
		$recipient = 'linus@stardew-tent-living.com';
		$calendar = $this->vCalendar2a;
		// Act
		$result = $manager->handleIMipReply($principalUri, $sender, $recipient, $calendar->serialize());
		// Assert
		$this->assertFalse($result);
	}

	public function testHandleImipReplyWithInvalidMethod(): void {
		// construct mock user calendar
		$userCalendar = $this->createMock(ITestCalendar::class);
		// construct mock calendar manager and returns
		/** @var Manager&MockObject $manager */
		$manager = $this->getMockBuilder(Manager::class)
			->setConstructorArgs([
				$this->coordinator,
				$this->container,
				$this->logger,
				$this->time,
			])
			->onlyMethods(['getCalendarsForPrincipal'])
			->getMock();
		$manager->expects(self::once())
			->method('getCalendarsForPrincipal')
			->willReturn([$userCalendar]);
		// construct logger returns
		$this->logger->expects(self::once())->method('warning')
			->with('iMip message contains an incorrect or invalid method');
		// construct parameters
		$principalUri = 'principals/user/linus';
		$sender = 'pierre@general-store.com';
		$recipient = 'linus@stardew-tent-living.com';
		$calendar = $this->vCalendar2a;
		$calendar->add('METHOD', 'UNKNOWN');
		// Act
		$result = $manager->handleIMipReply($principalUri, $sender, $recipient, $calendar->serialize());
		// Assert
		$this->assertFalse($result);
	}

	public function testHandleImipReplyWithNoEvent(): void {
		// construct mock user calendar
		$userCalendar = $this->createMock(ITestCalendar::class);
		// construct mock calendar manager and returns
		/** @var Manager&MockObject $manager */
		$manager = $this->getMockBuilder(Manager::class)
			->setConstructorArgs([
				$this->coordinator,
				$this->container,
				$this->logger,
				$this->time,
			])
			->onlyMethods(['getCalendarsForPrincipal'])
			->getMock();
		$manager->expects(self::once())
			->method('getCalendarsForPrincipal')
			->willReturn([$userCalendar]);
		// construct logger returns
		$this->logger->expects(self::once())->method('warning')
			->with('iMip message contains no event');
		// construct parameters
		$principalUri = 'principals/user/linus';
		$sender = 'pierre@general-store.com';
		$recipient = 'linus@stardew-tent-living.com';
		$calendar = $this->vCalendar2a;
		$calendar->add('METHOD', 'REPLY');
		$calendar->remove('VEVENT');
		// Act
		$result = $manager->handleIMipReply($principalUri, $sender, $recipient, $calendar->serialize());
		// Assert
		$this->assertFalse($result);
	}

	public function testHandleImipReplyWithNoUid(): void {
		// construct mock user calendar
		$userCalendar = $this->createMock(ITestCalendar::class);
		// construct mock calendar manager and returns
		/** @var Manager&MockObject $manager */
		$manager = $this->getMockBuilder(Manager::class)
			->setConstructorArgs([
				$this->coordinator,
				$this->container,
				$this->logger,
				$this->time,
			])
			->onlyMethods(['getCalendarsForPrincipal'])
			->getMock();
		$manager->expects(self::once())
			->method('getCalendarsForPrincipal')
			->willReturn([$userCalendar]);
		// construct logger returns
		$this->logger->expects(self::once())->method('warning')
			->with('iMip message event dose not contains a UID');
		// construct parameters
		$principalUri = 'principals/user/linus';
		$sender = 'pierre@general-store.com';
		$recipient = 'linus@stardew-tent-living.com';
		$calendar = $this->vCalendar2a;
		$calendar->add('METHOD', 'REPLY');
		$calendar->VEVENT->remove('UID');
		// Act
		$result = $manager->handleIMipReply($principalUri, $sender, $recipient, $calendar->serialize());
		// Assert
		$this->assertFalse($result);
	}

	public function testHandleImipReplyWithNoOrganizer(): void {
		// construct mock user calendar
		$userCalendar = $this->createMock(ITestCalendar::class);
		// construct mock calendar manager and returns
		/** @var Manager&MockObject $manager */
		$manager = $this->getMockBuilder(Manager::class)
			->setConstructorArgs([
				$this->coordinator,
				$this->container,
				$this->logger,
				$this->time,
			])
			->onlyMethods(['getCalendarsForPrincipal'])
			->getMock();
		$manager->expects(self::once())
			->method('getCalendarsForPrincipal')
			->willReturn([$userCalendar]);
		// construct logger returns
		$this->logger->expects(self::once())->method('warning')
			->with('iMip message event dose not contains an organizer');
		// construct parameters
		$principalUri = 'principals/user/linus';
		$sender = 'pierre@general-store.com';
		$recipient = 'linus@stardew-tent-living.com';
		$calendar = $this->vCalendar2a;
		$calendar->add('METHOD', 'REPLY');
		$calendar->VEVENT->remove('ORGANIZER');
		// Act
		$result = $manager->handleIMipReply($principalUri, $sender, $recipient, $calendar->serialize());
		// Assert
		$this->assertFalse($result);
	}

	public function testHandleImipReplyWithNoAttendee(): void {
		// construct mock user calendar
		$userCalendar = $this->createMock(ITestCalendar::class);
		// construct mock calendar manager and returns
		/** @var Manager&MockObject $manager */
		$manager = $this->getMockBuilder(Manager::class)
			->setConstructorArgs([
				$this->coordinator,
				$this->container,
				$this->logger,
				$this->time,
			])
			->onlyMethods(['getCalendarsForPrincipal'])
			->getMock();
		$manager->expects(self::once())
			->method('getCalendarsForPrincipal')
			->willReturn([$userCalendar]);
		// construct logger returns
		$this->logger->expects(self::once())->method('warning')
			->with('iMip message event dose not contains any attendees');
		// construct parameters
		$principalUri = 'principals/user/linus';
		$sender = 'pierre@general-store.com';
		$recipient = 'linus@stardew-tent-living.com';
		$calendar = $this->vCalendar2a;
		$calendar->add('METHOD', 'REPLY');
		$calendar->VEVENT->remove('ATTENDEE');
		// Act
		$result = $manager->handleIMipReply($principalUri, $sender, $recipient, $calendar->serialize());
		// Assert
		$this->assertFalse($result);
	}

	public function testHandleImipReplyDateInThePast(): void {
		// construct mock user calendar
		$userCalendar = $this->createMock(ITestCalendar::class);
		// construct mock calendar manager and returns
		/** @var Manager&MockObject $manager */
		$manager = $this->getMockBuilder(Manager::class)
			->setConstructorArgs([
				$this->coordinator,
				$this->container,
				$this->logger,
				$this->time,
			])
			->onlyMethods(['getCalendarsForPrincipal'])
			->getMock();
		$manager->expects(self::once())
			->method('getCalendarsForPrincipal')
			->willReturn([$userCalendar]);
		// construct logger and time returns
		$this->logger->expects(self::once())->method('warning')
			->with('iMip message event could not be processed because the event is in the past');
		$this->time->expects(self::once())
			->method('getTime')
			->willReturn(time());
		// construct parameters
		$principalUri = 'principals/user/linus';
		$sender = 'pierre@general-store.com';
		$recipient = 'linus@stardew-tent-living.com';
		$calendarData = clone $this->vCalendar2a;
		$calendarData->add('METHOD', 'REPLY');
		$calendarData->VEVENT->DTSTART = new \DateTime('2013-04-07'); // set to in the past
		// Act
		$result = $manager->handleIMipReply($principalUri, $sender, $recipient, $calendarData->serialize());
		// Assert
		$this->assertFalse($result);
	}

	public function testHandleImipReplyEventNotFound(): void {
		// construct mock user calendar
		$userCalendar = $this->createMock(ITestCalendar::class);
		$userCalendar->expects(self::once())
			->method('search')
			->willReturn([]);
		// construct mock calendar manager and returns
		/** @var Manager&MockObject $manager */
		$manager = $this->getMockBuilder(Manager::class)
			->setConstructorArgs([
				$this->coordinator,
				$this->container,
				$this->logger,
				$this->time,
			])
			->onlyMethods(['getCalendarsForPrincipal'])
			->getMock();
		$manager->expects(self::once())
			->method('getCalendarsForPrincipal')
			->willReturn([$userCalendar]);
		// construct time returns
		$this->time->expects(self::once())
			->method('getTime')
			->willReturn(1628374233);
		// construct parameters
		$principalUri = 'principals/user/linus';
		$sender = 'pierre@general-store.com';
		$recipient = 'linus@stardew-tent-living.com';
		$calendarData = clone $this->vCalendar2a;
		$calendarData->add('METHOD', 'REPLY');
		// construct logger return
		$this->logger->expects(self::once())->method('warning')
			->with('iMip message event could not be processed because no corresponding event was found in any calendar ' . $principalUri . ' and UID ' . $calendarData->VEVENT->UID->getValue());
		// Act
		$result = $manager->handleIMipReply($principalUri, $sender, $recipient, $calendarData->serialize());
		// Assert
		$this->assertFalse($result);
	}

	public function testHandleImipReply(): void {
		/** @var Manager | \PHPUnit\Framework\MockObject\MockObject $manager */
		$manager = $this->getMockBuilder(Manager::class)
			->setConstructorArgs([
				$this->coordinator,
				$this->container,
				$this->logger,
				$this->time,
			])
			->onlyMethods([
				'getCalendarsForPrincipal'
			])
			->getMock();
		$calendar = $this->createMock(ITestCalendar::class);
		$principalUri = 'principals/user/linus';
		$sender = 'pierre@general-store.com';
		$recipient = 'linus@stardew-tent-living.com';
		$calendarData = clone $this->vCalendar2a;
		$calendarData->add('METHOD', 'REPLY');

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
		// Act
		$result = $manager->handleIMipReply($principalUri, $sender, $recipient, $calendarData->serialize());
		// Assert
		$this->assertTrue($result);
	}

	public function testHandleImipCancelWithNoCalendars(): void {
		// construct calendar manager returns
		/** @var Manager&MockObject $manager */
		$manager = $this->getMockBuilder(Manager::class)
			->setConstructorArgs([
				$this->coordinator,
				$this->container,
				$this->logger,
				$this->time,
			])
			->onlyMethods(['getCalendarsForPrincipal'])
			->getMock();
		$manager->expects(self::once())
			->method('getCalendarsForPrincipal')
			->willReturn([]);
		// construct logger returns
		$this->logger->expects(self::once())->method('warning')
			->with('iMip message could not be processed because user has no calendars');
		// construct parameters
		$principalUri = 'principals/user/pierre';
		$sender = 'linus@stardew-tent-living.com';
		$recipient = 'pierre@general-store.com';
		$replyTo = null;
		$calendar = $this->vCalendar3a;
		$calendar->add('METHOD', 'CANCEL');
		// Act
		$result = $manager->handleIMipCancel($principalUri, $sender, $replyTo, $recipient, $calendar->serialize());
		// Assert
		$this->assertFalse($result);
	}

	public function testHandleImipCancelWithInvalidData(): void {
		// construct mock user calendar
		$userCalendar = $this->createMock(ITestCalendar::class);
		// construct mock calendar manager and returns
		/** @var Manager&MockObject $manager */
		$manager = $this->getMockBuilder(Manager::class)
			->setConstructorArgs([
				$this->coordinator,
				$this->container,
				$this->logger,
				$this->time,
			])
			->onlyMethods(['getCalendarsForPrincipal'])
			->getMock();
		$manager->expects(self::once())
			->method('getCalendarsForPrincipal')
			->willReturn([$userCalendar]);
		// construct logger returns
		$this->logger->expects(self::once())->method('error')
			->with('iMip message could not be processed because an error occurred while parsing the iMip message');
		// construct parameters
		$principalUri = 'principals/user/attendee1';
		$sender = 'organizer@testing.com';
		$recipient = 'attendee1@testing.com';
		$replyTo = null;
		// Act
		$result = $manager->handleIMipCancel($principalUri, $sender, $replyTo, $recipient, 'Invalid data');
		// Assert
		$this->assertFalse($result);
	}

	public function testHandleImipCancelWithNoMethod(): void {
		// construct mock user calendar
		$userCalendar = $this->createMock(ITestCalendar::class);
		// construct mock calendar manager and returns
		/** @var Manager&MockObject $manager */
		$manager = $this->getMockBuilder(Manager::class)
			->setConstructorArgs([
				$this->coordinator,
				$this->container,
				$this->logger,
				$this->time,
			])
			->onlyMethods(['getCalendarsForPrincipal'])
			->getMock();
		$manager->expects(self::once())
			->method('getCalendarsForPrincipal')
			->willReturn([$userCalendar]);
		// construct logger returns
		$this->logger->expects(self::once())->method('warning')
			->with('iMip message contains an incorrect or invalid method');
		// construct parameters
		$principalUri = 'principals/user/pierre';
		$sender = 'linus@stardew-tent-living.com';
		$recipient = 'pierre@general-store.com';
		$replyTo = null;
		$calendar = $this->vCalendar3a;
		// Act
		$result = $manager->handleIMipCancel($principalUri, $sender, $replyTo, $recipient, $calendar->serialize());
		// Assert
		$this->assertFalse($result);
	}

	public function testHandleImipCancelWithInvalidMethod(): void {
		// construct mock user calendar
		$userCalendar = $this->createMock(ITestCalendar::class);
		// construct mock calendar manager and returns
		/** @var Manager&MockObject $manager */
		$manager = $this->getMockBuilder(Manager::class)
			->setConstructorArgs([
				$this->coordinator,
				$this->container,
				$this->logger,
				$this->time,
			])
			->onlyMethods(['getCalendarsForPrincipal'])
			->getMock();
		$manager->expects(self::once())
			->method('getCalendarsForPrincipal')
			->willReturn([$userCalendar]);
		// construct logger returns
		$this->logger->expects(self::once())->method('warning')
			->with('iMip message contains an incorrect or invalid method');
		// construct parameters
		$principalUri = 'principals/user/pierre';
		$sender = 'linus@stardew-tent-living.com';
		$recipient = 'pierre@general-store.com';
		$replyTo = null;
		$calendar = $this->vCalendar3a;
		$calendar->add('METHOD', 'UNKNOWN');
		// Act
		$result = $manager->handleIMipCancel($principalUri, $sender, $replyTo, $recipient, $calendar->serialize());
		// Assert
		$this->assertFalse($result);
	}

	public function testHandleImipCancelWithNoEvent(): void {
		// construct mock user calendar
		$userCalendar = $this->createMock(ITestCalendar::class);
		// construct mock calendar manager and returns
		/** @var Manager&MockObject $manager */
		$manager = $this->getMockBuilder(Manager::class)
			->setConstructorArgs([
				$this->coordinator,
				$this->container,
				$this->logger,
				$this->time,
			])
			->onlyMethods(['getCalendarsForPrincipal'])
			->getMock();
		$manager->expects(self::once())
			->method('getCalendarsForPrincipal')
			->willReturn([$userCalendar]);
		// construct logger returns
		$this->logger->expects(self::once())->method('warning')
			->with('iMip message contains no event');
		// construct parameters
		$principalUri = 'principals/user/pierre';
		$sender = 'linus@stardew-tent-living.com';
		$recipient = 'pierre@general-store.com';
		$replyTo = null;
		$calendar = $this->vCalendar3a;
		$calendar->add('METHOD', 'CANCEL');
		$calendar->remove('VEVENT');
		// Act
		$result = $manager->handleIMipCancel($principalUri, $sender, $replyTo, $recipient, $calendar->serialize());
		// Assert
		$this->assertFalse($result);
	}

	public function testHandleImipCancelWithNoUid(): void {
		// construct mock user calendar
		$userCalendar = $this->createMock(ITestCalendar::class);
		// construct mock calendar manager and returns
		/** @var Manager&MockObject $manager */
		$manager = $this->getMockBuilder(Manager::class)
			->setConstructorArgs([
				$this->coordinator,
				$this->container,
				$this->logger,
				$this->time,
			])
			->onlyMethods(['getCalendarsForPrincipal'])
			->getMock();
		$manager->expects(self::once())
			->method('getCalendarsForPrincipal')
			->willReturn([$userCalendar]);
		// construct logger returns
		$this->logger->expects(self::once())->method('warning')
			->with('iMip message event dose not contains a UID');
		// construct parameters
		$principalUri = 'principals/user/pierre';
		$sender = 'linus@stardew-tent-living.com';
		$recipient = 'pierre@general-store.com';
		$replyTo = null;
		$calendar = $this->vCalendar3a;
		$calendar->add('METHOD', 'CANCEL');
		$calendar->VEVENT->remove('UID');
		// Act
		$result = $manager->handleIMipCancel($principalUri, $sender, $replyTo, $recipient, $calendar->serialize());
		// Assert
		$this->assertFalse($result);
	}

	public function testHandleImipCancelWithNoOrganizer(): void {
		// construct mock user calendar
		$userCalendar = $this->createMock(ITestCalendar::class);
		// construct mock calendar manager and returns
		/** @var Manager&MockObject $manager */
		$manager = $this->getMockBuilder(Manager::class)
			->setConstructorArgs([
				$this->coordinator,
				$this->container,
				$this->logger,
				$this->time,
			])
			->onlyMethods(['getCalendarsForPrincipal'])
			->getMock();
		$manager->expects(self::once())
			->method('getCalendarsForPrincipal')
			->willReturn([$userCalendar]);
		// construct logger returns
		$this->logger->expects(self::once())->method('warning')
			->with('iMip message event dose not contains an organizer');
		// construct parameters
		$principalUri = 'principals/user/pierre';
		$sender = 'linus@stardew-tent-living.com';
		$recipient = 'pierre@general-store.com';
		$replyTo = null;
		$calendar = $this->vCalendar3a;
		$calendar->add('METHOD', 'CANCEL');
		$calendar->VEVENT->remove('ORGANIZER');
		// Act
		$result = $manager->handleIMipCancel($principalUri, $sender, $replyTo, $recipient, $calendar->serialize());
		// Assert
		$this->assertFalse($result);
	}

	public function testHandleImipCancelWithNoAttendee(): void {
		// construct mock user calendar
		$userCalendar = $this->createMock(ITestCalendar::class);
		// construct mock calendar manager and returns
		/** @var Manager&MockObject $manager */
		$manager = $this->getMockBuilder(Manager::class)
			->setConstructorArgs([
				$this->coordinator,
				$this->container,
				$this->logger,
				$this->time,
			])
			->onlyMethods(['getCalendarsForPrincipal'])
			->getMock();
		$manager->expects(self::once())
			->method('getCalendarsForPrincipal')
			->willReturn([$userCalendar]);
		// construct logger returns
		$this->logger->expects(self::once())->method('warning')
			->with('iMip message event dose not contains any attendees');
		// construct parameters
		$principalUri = 'principals/user/pierre';
		$sender = 'pierre@general-store.com';
		$recipient = 'linus@stardew-tent-living.com';
		$replyTo = null;
		$calendar = $this->vCalendar3a;
		$calendar->add('METHOD', 'CANCEL');
		$calendar->VEVENT->remove('ATTENDEE');
		// Act
		$result = $manager->handleIMipCancel($principalUri, $sender, $replyTo, $recipient, $calendar->serialize());
		// Assert
		$this->assertFalse($result);
	}

	public function testHandleImipCancelAttendeeNotRecipient(): void {
		// construct mock user calendar
		$userCalendar = $this->createMock(ITestCalendar::class);
		// construct mock calendar manager and returns
		/** @var Manager&MockObject $manager */
		$manager = $this->getMockBuilder(Manager::class)
			->setConstructorArgs([
				$this->coordinator,
				$this->container,
				$this->logger,
				$this->time,
			])
			->onlyMethods(['getCalendarsForPrincipal'])
			->getMock();
		$manager->expects(self::once())
			->method('getCalendarsForPrincipal')
			->willReturn([$userCalendar]);
		// construct logger returns
		$this->logger->expects(self::once())->method('warning')
			->with('iMip message event could not be processed because recipient must be an ATTENDEE of this event');
		// construct parameters
		$principalUri = 'principals/user/pierre';
		$sender = 'linus@stardew-tent-living.com';
		$recipient = 'leah@general-store.com';
		$replyTo = null;
		$calendarData = clone $this->vCalendar3a;
		$calendarData->add('METHOD', 'CANCEL');
		// Act
		$result = $manager->handleIMipCancel($principalUri, $sender, $replyTo, $recipient, $calendarData->serialize());
		// Assert
		$this->assertFalse($result);
	}

	public function testHandleImipCancelDateInThePast(): void {
		// construct mock user calendar
		$userCalendar = $this->createMock(ITestCalendar::class);
		// construct mock calendar manager and returns
		/** @var Manager&MockObject $manager */
		$manager = $this->getMockBuilder(Manager::class)
			->setConstructorArgs([
				$this->coordinator,
				$this->container,
				$this->logger,
				$this->time,
			])
			->onlyMethods(['getCalendarsForPrincipal'])
			->getMock();
		$manager->expects(self::once())
			->method('getCalendarsForPrincipal')
			->willReturn([$userCalendar]);
		// construct logger and time returns
		$this->logger->expects(self::once())->method('warning')
			->with('iMip message event could not be processed because the event is in the past');
		$this->time->expects(self::once())
			->method('getTime')
			->willReturn(time());
		// construct parameters
		$principalUri = 'principals/user/pierre';
		$sender = 'linus@stardew-tent-living.com';
		$recipient = 'pierre@general-store.com';
		$replyTo = null;
		$calendarData = clone $this->vCalendar3a;
		$calendarData->add('METHOD', 'CANCEL');
		$calendarData->VEVENT->DTSTART = new \DateTime('2013-04-07'); // set to in the past
		// Act
		$result = $manager->handleIMipCancel($principalUri, $sender, $replyTo, $recipient, $calendarData->serialize());
		// Assert
		$this->assertFalse($result);
	}

	public function testHandleImipCancelEventNotFound(): void {
		// construct mock user calendar
		$userCalendar = $this->createMock(ITestCalendar::class);
		$userCalendar->expects(self::once())
			->method('search')
			->willReturn([]);
		// construct mock calendar manager and returns
		/** @var Manager&MockObject $manager */
		$manager = $this->getMockBuilder(Manager::class)
			->setConstructorArgs([
				$this->coordinator,
				$this->container,
				$this->logger,
				$this->time,
			])
			->onlyMethods(['getCalendarsForPrincipal'])
			->getMock();
		$manager->expects(self::once())
			->method('getCalendarsForPrincipal')
			->willReturn([$userCalendar]);
		// construct time returns
		$this->time->expects(self::once())
			->method('getTime')
			->willReturn(1628374233);
		// construct parameters
		$principalUri = 'principals/user/pierre';
		$sender = 'linus@stardew-tent-living.com';
		$recipient = 'pierre@general-store.com';
		$replyTo = null;
		$calendarData = clone $this->vCalendar3a;
		$calendarData->add('METHOD', 'CANCEL');
		// construct logger return
		$this->logger->expects(self::once())->method('warning')
			->with('iMip message event could not be processed because no corresponding event was found in any calendar ' . $principalUri . ' and UID ' . $calendarData->VEVENT->UID->getValue());
		// Act
		$result = $manager->handleIMipCancel($principalUri, $sender, $replyTo, $recipient, $calendarData->serialize());
		// Assert
		$this->assertFalse($result);
	}

	public function testHandleImipCancelOrganiserInReplyTo(): void {
		/** @var Manager | \PHPUnit\Framework\MockObject\MockObject $manager */
		$manager = $this->getMockBuilder(Manager::class)
			->setConstructorArgs([
				$this->coordinator,
				$this->container,
				$this->logger,
				$this->time,
			])
			->onlyMethods([
				'getCalendarsForPrincipal'
			])
			->getMock();

		$principalUri = 'principals/user/pierre';
		$sender = 'clint@stardew-tent-living.com';
		$recipient = 'pierre@general-store.com';
		$replyTo = 'linus@stardew-tent-living.com';
		$calendar = $this->createMock(ITestCalendar::class);
		$calendarData = clone $this->vCalendar3a;
		$calendarData->add('METHOD', 'CANCEL');

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
		// Act
		$result = $manager->handleIMipCancel($principalUri, $sender, $replyTo, $recipient, $calendarData->serialize());
		// Assert
		$this->assertTrue($result);
	}

	public function testHandleImipCancel(): void {
		/** @var Manager | \PHPUnit\Framework\MockObject\MockObject $manager */
		$manager = $this->getMockBuilder(Manager::class)
			->setConstructorArgs([
				$this->coordinator,
				$this->container,
				$this->logger,
				$this->time,
			])
			->onlyMethods([
				'getCalendarsForPrincipal'
			])
			->getMock();
		$principalUri = 'principals/user/pierre';
		$sender = 'linus@stardew-tent-living.com';
		$recipient = 'pierre@general-store.com';
		$replyTo = null;
		$calendar = $this->createMock(ITestCalendar::class);
		$calendarData = clone $this->vCalendar3a;
		$calendarData->add('METHOD', 'CANCEL');

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
		// Act
		$result = $manager->handleIMipCancel($principalUri, $sender, $replyTo, $recipient, $calendarData->serialize());
		// Assert
		$this->assertTrue($result);
	}

}
