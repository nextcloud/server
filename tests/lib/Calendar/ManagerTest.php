<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Calendar;

use DateTimeImmutable;
use OC\AppFramework\Bootstrap\Coordinator;
use OC\Calendar\AvailabilityResult;
use OC\Calendar\Manager;
use OCA\DAV\CalDAV\Auth\CustomPrincipalPlugin;
use OCA\DAV\Connector\Sabre\Server;
use OCA\DAV\ServerFactory;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Calendar\ICalendar;
use OCP\Calendar\ICalendarIsShared;
use OCP\Calendar\ICalendarIsWritable;
use OCP\Calendar\ICreateFromString;
use OCP\Calendar\IHandleImipMessage;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Security\ISecureRandom;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;
use Sabre\VObject\Component\VCalendar;
use Test\TestCase;

/*
 * This allows us to create Mock object supporting both interfaces
 */
interface ITestCalendar extends ICreateFromString, IHandleImipMessage, ICalendarIsShared, ICalendarIsWritable {
}

class ManagerTest extends TestCase {
	/** @var Coordinator&MockObject */
	private $coordinator;

	/** @var ContainerInterface&MockObject */
	private $container;

	/** @var LoggerInterface&MockObject */
	private $logger;

	/** @var Manager */
	private $manager;

	/** @var ITimeFactory&MockObject */
	private $time;

	/** @var ISecureRandom&MockObject */
	private ISecureRandom $secureRandom;

	private IUserManager&MockObject $userManager;
	private ServerFactory&MockObject $serverFactory;

	private VCalendar $vCalendar1a;
	private VCalendar $vCalendar2a;
	private VCalendar $vCalendar3a;

	protected function setUp(): void {
		parent::setUp();

		$this->coordinator = $this->createMock(Coordinator::class);
		$this->container = $this->createMock(ContainerInterface::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->time = $this->createMock(ITimeFactory::class);
		$this->secureRandom = $this->createMock(ISecureRandom::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->serverFactory = $this->createMock(ServerFactory::class);

		$this->manager = new Manager(
			$this->coordinator,
			$this->container,
			$this->logger,
			$this->time,
			$this->secureRandom,
			$this->userManager,
			$this->serverFactory,
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
	public function testSearch($search1, $search2, $expected): void {
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
	public function testSearchOptions($search1, $search2, $expected): void {
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

	public static function searchProvider(): array {
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

	public function testRegisterUnregister(): void {
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

	public function testGetCalendars(): void {
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

	public function testEnabledIfNot(): void {
		$isEnabled = $this->manager->isEnabled();
		$this->assertFalse($isEnabled);
	}

	public function testIfEnabledIfSo(): void {
		/** @var ICalendar | MockObject $calendar */
		$calendar = $this->createMock(ICalendar::class);
		$this->manager->registerCalendar($calendar);

		$isEnabled = $this->manager->isEnabled();
		$this->assertTrue($isEnabled);
	}

	public function testHandleImipRequestWithNoCalendars(): void {
		// construct calendar manager returns
		/** @var Manager&MockObject $manager */
		$manager = $this->getMockBuilder(Manager::class)
			->setConstructorArgs([
				$this->coordinator,
				$this->container,
				$this->logger,
				$this->time,
				$this->secureRandom,
				$this->userManager,
				$this->serverFactory,
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
		$principalUri = 'principals/user/attendee1';
		$sender = 'organizer@testing.com';
		$recipient = 'attendee1@testing.com';
		$calendar = $this->vCalendar1a;
		$calendar->add('METHOD', 'REQUEST');
		// Act
		$result = $manager->handleIMipRequest($principalUri, $sender, $recipient, $calendar->serialize());
		// Assert
		$this->assertFalse($result);
	}

	public function testHandleImipRequestWithInvalidData(): void {
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
				$this->secureRandom,
				$this->userManager,
				$this->serverFactory,
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
		$result = $manager->handleIMipRequest($principalUri, $sender, $recipient, 'Invalid data');
		// Assert
		$this->assertFalse($result);
	}

	public function testHandleImipRequestWithNoMethod(): void {
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
				$this->secureRandom,
				$this->userManager,
				$this->serverFactory,
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
		$principalUri = 'principals/user/attendee1';
		$sender = 'organizer@testing.com';
		$recipient = 'attendee1@testing.com';
		$calendar = $this->vCalendar1a;
		// Act
		$result = $manager->handleIMipRequest($principalUri, $sender, $recipient, $calendar->serialize());
		// Assert
		$this->assertFalse($result);
	}

	public function testHandleImipRequestWithInvalidMethod(): void {
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
				$this->secureRandom,
				$this->userManager,
				$this->serverFactory,
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
		$principalUri = 'principals/user/attendee1';
		$sender = 'organizer@testing.com';
		$recipient = 'attendee1@testing.com';
		$calendar = $this->vCalendar1a;
		$calendar->add('METHOD', 'CANCEL');
		// Act
		$result = $manager->handleIMipRequest($principalUri, $sender, $recipient, $calendar->serialize());
		// Assert
		$this->assertFalse($result);
	}

	public function testHandleImipRequestWithNoEvent(): void {
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
				$this->secureRandom,
				$this->userManager,
				$this->serverFactory,
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
		$principalUri = 'principals/user/attendee1';
		$sender = 'organizer@testing.com';
		$recipient = 'attendee1@testing.com';
		$calendar = $this->vCalendar1a;
		$calendar->add('METHOD', 'REQUEST');
		$calendar->remove('VEVENT');
		// Act
		$result = $manager->handleIMipRequest($principalUri, $sender, $recipient, $calendar->serialize());
		// Assert
		$this->assertFalse($result);
	}

	public function testHandleImipRequestWithNoUid(): void {
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
				$this->secureRandom,
				$this->userManager,
				$this->serverFactory,
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
		$principalUri = 'principals/user/attendee1';
		$sender = 'organizer@testing.com';
		$recipient = 'attendee1@testing.com';
		$calendar = $this->vCalendar1a;
		$calendar->add('METHOD', 'REQUEST');
		$calendar->VEVENT->remove('UID');
		// Act
		$result = $manager->handleIMipRequest($principalUri, $sender, $recipient, $calendar->serialize());
		// Assert
		$this->assertFalse($result);
	}

	public function testHandleImipRequestWithNoOrganizer(): void {
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
				$this->secureRandom,
				$this->userManager,
				$this->serverFactory,
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
		$principalUri = 'principals/user/attendee1';
		$sender = 'organizer@testing.com';
		$recipient = 'attendee1@testing.com';
		$calendar = $this->vCalendar1a;
		$calendar->add('METHOD', 'REQUEST');
		$calendar->VEVENT->remove('ORGANIZER');
		// Act
		$result = $manager->handleIMipRequest($principalUri, $sender, $recipient, $calendar->serialize());
		// Assert
		$this->assertFalse($result);
	}

	public function testHandleImipRequestWithNoAttendee(): void {
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
				$this->secureRandom,
				$this->userManager,
				$this->serverFactory,
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
		$principalUri = 'principals/user/attendee1';
		$sender = 'organizer@testing.com';
		$recipient = 'attendee1@testing.com';
		$calendar = $this->vCalendar1a;
		$calendar->add('METHOD', 'REQUEST');
		$calendar->VEVENT->remove('ATTENDEE');
		// Act
		$result = $manager->handleIMipRequest($principalUri, $sender, $recipient, $calendar->serialize());
		// Assert
		$this->assertFalse($result);
	}

	public function testHandleImipRequestWithInvalidAttendee(): void {
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
				$this->secureRandom,
				$this->userManager,
				$this->serverFactory,
			])
			->onlyMethods(['getCalendarsForPrincipal'])
			->getMock();
		$manager->expects(self::once())
			->method('getCalendarsForPrincipal')
			->willReturn([$userCalendar]);
		// construct logger returns
		$this->logger->expects(self::once())->method('warning')
			->with('iMip message event does not contain a attendee that matches the recipient');
		// construct parameters
		$principalUri = 'principals/user/attendee1';
		$sender = 'organizer@testing.com';
		$recipient = 'attendee2@testing.com';
		$calendar = $this->vCalendar1a;
		$calendar->add('METHOD', 'REQUEST');
		// Act
		$result = $manager->handleIMipRequest($principalUri, $sender, $recipient, $calendar->serialize());
		// Assert
		$this->assertFalse($result);
	}

	public function testHandleImipRequestWithNoMatch(): void {
		// construct mock user calendar
		$userCalendar = $this->createMock(ITestCalendar::class);
		$userCalendar->expects(self::once())
			->method('isDeleted')
			->willReturn(false);
		$userCalendar->expects(self::once())
			->method('isWritable')
			->willReturn(true);
		$userCalendar->expects(self::once())
			->method('isShared')
			->willReturn(false);
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
				$this->secureRandom,
				$this->userManager,
				$this->serverFactory,
			])
			->onlyMethods(['getCalendarsForPrincipal'])
			->getMock();
		$manager->expects(self::once())
			->method('getCalendarsForPrincipal')
			->willReturn([$userCalendar]);
		// construct logger returns
		$this->logger->expects(self::once())->method('warning')
			->with('iMip message event could not be processed because no corresponding event was found in any calendar');
		// construct parameters
		$principalUri = 'principals/user/attendee1';
		$sender = 'organizer@testing.com';
		$recipient = 'attendee1@testing.com';
		$calendar = $this->vCalendar1a;
		$calendar->add('METHOD', 'REQUEST');
		// Act
		$result = $manager->handleIMipRequest($principalUri, $sender, $recipient, $calendar->serialize());
		// Assert
		$this->assertFalse($result);
	}

	public function testHandleImipRequest(): void {
		// construct mock user calendar
		$userCalendar = $this->createMock(ITestCalendar::class);
		$userCalendar->expects(self::once())
			->method('isDeleted')
			->willReturn(false);
		$userCalendar->expects(self::once())
			->method('isWritable')
			->willReturn(true);
		$userCalendar->expects(self::once())
			->method('isShared')
			->willReturn(false);
		$userCalendar->expects(self::once())
			->method('search')
			->willReturn([['uri' => 'principals/user/attendee1/personal']]);
		// construct mock calendar manager and returns
		/** @var Manager&MockObject $manager */
		$manager = $this->getMockBuilder(Manager::class)
			->setConstructorArgs([
				$this->coordinator,
				$this->container,
				$this->logger,
				$this->time,
				$this->secureRandom,
				$this->userManager,
				$this->serverFactory,
			])
			->onlyMethods(['getCalendarsForPrincipal'])
			->getMock();
		$manager->expects(self::once())
			->method('getCalendarsForPrincipal')
			->willReturn([$userCalendar]);
		// construct parameters
		$principalUri = 'principals/user/attendee1';
		$sender = 'organizer@testing.com';
		$recipient = 'attendee1@testing.com';
		$calendar = $this->vCalendar1a;
		$calendar->add('METHOD', 'REQUEST');
		// construct user calendar returns
		$userCalendar->expects(self::once())
			->method('handleIMipMessage')
			->with('', $calendar->serialize());
		// Act
		$result = $manager->handleIMipRequest($principalUri, $sender, $recipient, $calendar->serialize());
		// Assert
		$this->assertTrue($result);
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
				$this->secureRandom,
				$this->userManager,
				$this->serverFactory,
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
				$this->secureRandom,
				$this->userManager,
				$this->serverFactory,
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
				$this->secureRandom,
				$this->userManager,
				$this->serverFactory,
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
				$this->secureRandom,
				$this->userManager,
				$this->serverFactory,
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
				$this->secureRandom,
				$this->userManager,
				$this->serverFactory,
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
				$this->secureRandom,
				$this->userManager,
				$this->serverFactory,
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
				$this->secureRandom,
				$this->userManager,
				$this->serverFactory,
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
				$this->secureRandom,
				$this->userManager,
				$this->serverFactory,
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
				$this->secureRandom,
				$this->userManager,
				$this->serverFactory,
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
				$this->secureRandom,
				$this->userManager,
				$this->serverFactory,
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
			->with('iMip message event could not be processed because no corresponding event was found in any calendar ' . $principalUri . 'and UID' . $calendarData->VEVENT->UID->getValue());
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
				$this->secureRandom,
				$this->userManager,
				$this->serverFactory,
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
				$this->secureRandom,
				$this->userManager,
				$this->serverFactory,
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
				$this->secureRandom,
				$this->userManager,
				$this->serverFactory,
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
				$this->secureRandom,
				$this->userManager,
				$this->serverFactory,
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
				$this->secureRandom,
				$this->userManager,
				$this->serverFactory,
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
				$this->secureRandom,
				$this->userManager,
				$this->serverFactory,
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
				$this->secureRandom,
				$this->userManager,
				$this->serverFactory,
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
				$this->secureRandom,
				$this->userManager,
				$this->serverFactory,
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
				$this->secureRandom,
				$this->userManager,
				$this->serverFactory,
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
				$this->secureRandom,
				$this->userManager,
				$this->serverFactory,
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
				$this->secureRandom,
				$this->userManager,
				$this->serverFactory,
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
				$this->secureRandom,
				$this->userManager,
				$this->serverFactory,
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
			->with('iMip message event could not be processed because no corresponding event was found in any calendar ' . $principalUri . 'and UID' . $calendarData->VEVENT->UID->getValue());
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
				$this->secureRandom,
				$this->userManager,
				$this->serverFactory,
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
				$this->secureRandom,
				$this->userManager,
				$this->serverFactory,
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

	private function getFreeBusyResponse(): string {
		return <<<EOF
<?xml version="1.0" encoding="utf-8"?>
<cal:schedule-response xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns" xmlns:cal="urn:ietf:params:xml:ns:caldav" xmlns:cs="http://calendarserver.org/ns/" xmlns:oc="http://owncloud.org/ns" xmlns:nc="http://nextcloud.org/ns">
  <cal:response>
    <cal:recipient>
      <d:href>mailto:admin@imap.localhost</d:href>
    </cal:recipient>
    <cal:request-status>2.0;Success</cal:request-status>
    <cal:calendar-data>BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Sabre//Sabre VObject 4.5.6//EN
CALSCALE:GREGORIAN
METHOD:REPLY
BEGIN:VFREEBUSY
DTSTART:20250116T060000Z
DTEND:20250117T060000Z
DTSTAMP:20250111T125634Z
FREEBUSY:20250116T060000Z/20250116T230000Z
FREEBUSY;FBTYPE=BUSY-UNAVAILABLE:20250116T230000Z/20250117T060000Z
ATTENDEE:mailto:admin@imap.localhost
UID:6099eab3-9bf1-4c7a-809e-4d46957cc372
ORGANIZER;CN=admin:mailto:admin@imap.localhost
END:VFREEBUSY
END:VCALENDAR
</cal:calendar-data>
  </cal:response>
  <cal:response>
    <cal:recipient>
      <d:href>mailto:empty@imap.localhost</d:href>
    </cal:recipient>
    <cal:request-status>2.0;Success</cal:request-status>
    <cal:calendar-data>BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Sabre//Sabre VObject 4.5.6//EN
CALSCALE:GREGORIAN
METHOD:REPLY
BEGIN:VFREEBUSY
DTSTART:20250116T060000Z
DTEND:20250117T060000Z
DTSTAMP:20250111T125634Z
ATTENDEE:mailto:empty@imap.localhost
UID:6099eab3-9bf1-4c7a-809e-4d46957cc372
ORGANIZER;CN=admin:mailto:admin@imap.localhost
END:VFREEBUSY
END:VCALENDAR
</cal:calendar-data>
  </cal:response>
  <cal:response>
    <cal:recipient>
      <d:href>mailto:user@imap.localhost</d:href>
    </cal:recipient>
    <cal:request-status>2.0;Success</cal:request-status>
    <cal:calendar-data>BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Sabre//Sabre VObject 4.5.6//EN
CALSCALE:GREGORIAN
METHOD:REPLY
BEGIN:VFREEBUSY
DTSTART:20250116T060000Z
DTEND:20250117T060000Z
DTSTAMP:20250111T125634Z
FREEBUSY:20250116T060000Z/20250116T230000Z
FREEBUSY;FBTYPE=BUSY-UNAVAILABLE:20250116T230000Z/20250117T060000Z
ATTENDEE:mailto:user@imap.localhost
UID:6099eab3-9bf1-4c7a-809e-4d46957cc372
ORGANIZER;CN=admin:mailto:admin@imap.localhost
END:VFREEBUSY
END:VCALENDAR
</cal:calendar-data>
  </cal:response>
  <cal:response>
    <cal:recipient>
      <d:href>mailto:nouser@domain.tld</d:href>
    </cal:recipient>
    <cal:request-status>3.7;Could not find principal</cal:request-status>
  </cal:response>
</cal:schedule-response>
EOF;
	}

	public function testCheckAvailability(): void {
		$organizer = $this->createMock(IUser::class);
		$organizer->expects(self::once())
			->method('getUID')
			->willReturn('admin');
		$organizer->expects(self::once())
			->method('getEMailAddress')
			->willReturn('admin@imap.localhost');

		$user1 = $this->createMock(IUser::class);
		$user2 = $this->createMock(IUser::class);

		$this->userManager->expects(self::exactly(3))
			->method('getByEmail')
			->willReturnMap([
				['user@imap.localhost', [$user1]],
				['empty@imap.localhost', [$user2]],
				['nouser@domain.tld', []],
			]);

		$authPlugin = $this->createMock(CustomPrincipalPlugin::class);
		$authPlugin->expects(self::once())
			->method('setCurrentPrincipal')
			->with('principals/users/admin');

		$server = $this->createMock(Server::class);
		$server->expects(self::once())
			->method('getPlugin')
			->with('auth')
			->willReturn($authPlugin);
		$server->expects(self::once())
			->method('invokeMethod')
			->willReturnCallback(function (
				RequestInterface $request,
				ResponseInterface $response,
				bool $sendResponse,
			): void {
				$requestBody = file_get_contents(__DIR__ . '/../../data/ics/free-busy-request.ics');
				$this->assertEquals('POST', $request->getMethod());
				$this->assertEquals('calendars/admin/outbox', $request->getPath());
				$this->assertEquals('text/calendar', $request->getHeader('Content-Type'));
				$this->assertEquals('0', $request->getHeader('Depth'));
				$this->assertEquals($requestBody, $request->getBodyAsString());
				$this->assertFalse($sendResponse);
				$response->setStatus(200);
				$response->setBody($this->getFreeBusyResponse());
			});

		$this->serverFactory->expects(self::once())
			->method('createAttendeeAvailabilityServer')
			->willReturn($server);

		$start = new DateTimeImmutable('2025-01-16T06:00:00Z');
		$end = new DateTimeImmutable('2025-01-17T06:00:00Z');
		$actual = $this->manager->checkAvailability($start, $end, $organizer, [
			'user@imap.localhost',
			'empty@imap.localhost',
			'nouser@domain.tld',
		]);
		$expected = [
			new AvailabilityResult('admin@imap.localhost', false),
			new AvailabilityResult('empty@imap.localhost', true),
			new AvailabilityResult('user@imap.localhost', false),
		];
		$this->assertEquals($expected, $actual);
	}

	public function testCheckAvailabilityWithMailtoPrefix(): void {
		$organizer = $this->createMock(IUser::class);
		$organizer->expects(self::once())
			->method('getUID')
			->willReturn('admin');
		$organizer->expects(self::once())
			->method('getEMailAddress')
			->willReturn('admin@imap.localhost');

		$user1 = $this->createMock(IUser::class);
		$user2 = $this->createMock(IUser::class);

		$this->userManager->expects(self::exactly(3))
			->method('getByEmail')
			->willReturnMap([
				['user@imap.localhost', [$user1]],
				['empty@imap.localhost', [$user2]],
				['nouser@domain.tld', []],
			]);

		$authPlugin = $this->createMock(CustomPrincipalPlugin::class);
		$authPlugin->expects(self::once())
			->method('setCurrentPrincipal')
			->with('principals/users/admin');

		$server = $this->createMock(Server::class);
		$server->expects(self::once())
			->method('getPlugin')
			->with('auth')
			->willReturn($authPlugin);
		$server->expects(self::once())
			->method('invokeMethod')
			->willReturnCallback(function (
				RequestInterface $request,
				ResponseInterface $response,
				bool $sendResponse,
			): void {
				$requestBody = file_get_contents(__DIR__ . '/../../data/ics/free-busy-request.ics');
				$this->assertEquals('POST', $request->getMethod());
				$this->assertEquals('calendars/admin/outbox', $request->getPath());
				$this->assertEquals('text/calendar', $request->getHeader('Content-Type'));
				$this->assertEquals('0', $request->getHeader('Depth'));
				$this->assertEquals($requestBody, $request->getBodyAsString());
				$this->assertFalse($sendResponse);
				$response->setStatus(200);
				$response->setBody($this->getFreeBusyResponse());
			});

		$this->serverFactory->expects(self::once())
			->method('createAttendeeAvailabilityServer')
			->willReturn($server);

		$start = new DateTimeImmutable('2025-01-16T06:00:00Z');
		$end = new DateTimeImmutable('2025-01-17T06:00:00Z');
		$actual = $this->manager->checkAvailability($start, $end, $organizer, [
			'mailto:user@imap.localhost',
			'mailto:empty@imap.localhost',
			'mailto:nouser@domain.tld',
		]);
		$expected = [
			new AvailabilityResult('admin@imap.localhost', false),
			new AvailabilityResult('empty@imap.localhost', true),
			new AvailabilityResult('user@imap.localhost', false),
		];
		$this->assertEquals($expected, $actual);
	}
}
