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
use OCA\DAV\Db\PropertyMapper;
use OCA\DAV\ServerFactory;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Calendar\ICalendar;
use OCP\Calendar\ICalendarExport;
use OCP\Calendar\ICalendarIsEnabled;
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
interface ITestCalendar extends ICreateFromString, IHandleImipMessage, ICalendarIsEnabled, ICalendarIsWritable, ICalendarIsShared, ICalendarExport {

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
	private PropertyMapper&MockObject $propertyMapper;

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
		$this->propertyMapper = $this->createMock(PropertyMapper::class);

		$this->manager = new Manager(
			$this->coordinator,
			$this->container,
			$this->logger,
			$this->time,
			$this->secureRandom,
			$this->userManager,
			$this->serverFactory,
			$this->propertyMapper,
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

	#[\PHPUnit\Framework\Attributes\DataProvider('searchProvider')]
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

	#[\PHPUnit\Framework\Attributes\DataProvider('searchProvider')]
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

	public function testHandleImipWithNoCalendars(): void {
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
				$this->propertyMapper,
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
		$userId = 'attendee1';
		$calendar = $this->vCalendar1a;
		$calendar->add('METHOD', 'REQUEST');
		// test method
		$result = $manager->handleIMip($userId, $calendar->serialize());
		// Assert
		$this->assertFalse($result);
	}

	public function testHandleImipWithNoEvent(): void {
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
				$this->propertyMapper,
			])
			->onlyMethods(['getCalendarsForPrincipal'])
			->getMock();
		$manager->expects(self::once())
			->method('getCalendarsForPrincipal')
			->willReturn([$userCalendar]);
		// construct logger returns
		$this->logger->expects(self::once())->method('warning')
			->with('iMip message does not contain any event(s)');
		// construct parameters
		$userId = 'attendee1';
		$calendar = $this->vCalendar1a;
		$calendar->add('METHOD', 'REQUEST');
		$calendar->remove('VEVENT');
		// Act
		$result = $manager->handleIMip($userId, $calendar->serialize());
		// Assert
		$this->assertFalse($result);
	}

	public function testHandleImipMissingOrganizerWithRecipient(): void {
		// construct mock user calendar
		$userCalendar = $this->createMock(ITestCalendar::class);
		$userCalendar->expects(self::once())
			->method('isDeleted')
			->willReturn(false);
		$userCalendar->expects(self::once())
			->method('isWritable')
			->willReturn(true);
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
				$this->propertyMapper,
			])
			->onlyMethods(['getCalendarsForPrincipal'])
			->getMock();
		$manager->expects(self::once())
			->method('getCalendarsForPrincipal')
			->willReturn([$userCalendar]);
		// construct parameters
		$userId = 'attendee1';
		$calendar = $this->vCalendar1a;
		$calendar->add('METHOD', 'REQUEST');
		$calendar->VEVENT->remove('ORGANIZER');
		// construct user calendar returns
		$userCalendar->expects(self::once())
			->method('handleIMipMessage');
		// test method
		$result = $manager->handleIMip($userId, $calendar->serialize(), ['recipient' => 'organizer@testing.com']);
	}

	public function testHandleImipMissingOrganizerNoRecipient(): void {
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
				$this->propertyMapper,
			])
			->onlyMethods(['getCalendarsForPrincipal'])
			->getMock();
		$manager->expects(self::once())
			->method('getCalendarsForPrincipal')
			->willReturn([$userCalendar]);
		// construct parameters
		$userId = 'attendee1';
		$calendar = $this->vCalendar1a;
		$calendar->add('METHOD', 'REQUEST');
		$calendar->VEVENT->remove('ORGANIZER');
		// Logger expects warning
		$this->logger->expects($this->once())
			->method('warning')
			->with('iMip message event does not contain an organizer and no recipient was provided');

		$result = $manager->handleIMip($userId, $calendar->serialize(), []);
	}

	public function testHandleImipWithNoUid(): void {
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
				$this->propertyMapper,
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
		$userId = 'attendee1';
		$calendar = $this->vCalendar1a;
		$calendar->add('METHOD', 'REQUEST');
		$calendar->VEVENT->remove('UID');
		// test method
		$result = $manager->handleIMip($userId, $calendar->serialize());
		// Assert
		$this->assertFalse($result);
	}

	public function testHandleImipWithNoMatch(): void {
		// construct mock user calendar
		$userCalendar = $this->createMock(ITestCalendar::class);
		$userCalendar->expects(self::once())
			->method('isDeleted')
			->willReturn(false);
		$userCalendar->expects(self::once())
			->method('isWritable')
			->willReturn(true);
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
				$this->propertyMapper,
			])
			->onlyMethods(['getCalendarsForPrincipal'])
			->getMock();
		$manager->expects(self::once())
			->method('getCalendarsForPrincipal')
			->willReturn([$userCalendar]);
		// construct logger returns
		$this->logger->expects(self::once())->method('warning')
			->with('iMip message could not be processed because no corresponding event was found in any calendar');
		// construct parameters
		$userId = 'attendee1';
		$calendar = $this->vCalendar1a;
		$calendar->add('METHOD', 'REQUEST');
		// test method
		$result = $manager->handleIMip($userId, $calendar->serialize());
		// Assert
		$this->assertFalse($result);
	}

	public function testHandleImip(): void {
		// construct mock user calendar
		$userCalendar = $this->createMock(ITestCalendar::class);
		$userCalendar->expects(self::once())
			->method('isDeleted')
			->willReturn(false);
		$userCalendar->expects(self::once())
			->method('isWritable')
			->willReturn(true);
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
				$this->propertyMapper,
			])
			->onlyMethods(['getCalendarsForPrincipal'])
			->getMock();
		$manager->expects(self::once())
			->method('getCalendarsForPrincipal')
			->willReturn([$userCalendar]);
		// construct parameters
		$userId = 'attendee1';
		$calendar = $this->vCalendar1a;
		$calendar->add('METHOD', 'REQUEST');
		// construct user calendar returns
		$userCalendar->expects(self::once())
			->method('handleIMipMessage');
		// test method
		$result = $manager->handleIMip($userId, $calendar->serialize());
	}

	public function testHandleImipWithAbsentCreateOption(): void {
		// construct mock user calendar (no matching event found)
		$userCalendar = $this->createMock(ITestCalendar::class);
		$userCalendar->expects(self::once())
			->method('isDeleted')
			->willReturn(false);
		$userCalendar->expects(self::exactly(2))
			->method('isWritable')
			->willReturn(true);
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
				$this->propertyMapper,
			])
			->onlyMethods(['getCalendarsForPrincipal', 'getPrimaryCalendar'])
			->getMock();
		$manager->expects(self::once())
			->method('getCalendarsForPrincipal')
			->willReturn([$userCalendar]);
		$manager->expects(self::once())
			->method('getPrimaryCalendar')
			->willReturn(null);
		// construct parameters
		$userId = 'attendee1';
		$calendar = $this->vCalendar1a;
		$calendar->add('METHOD', 'REQUEST');
		// construct user calendar returns - should create new event
		$userCalendar->expects(self::once())
			->method('handleIMipMessage')
			->with($userId, self::callback(function ($data) {
				return str_contains($data, 'STATUS:TENTATIVE');
			}));
		// test method with absent=create option
		$result = $manager->handleIMip($userId, $calendar->serialize(), [
			'absent' => 'create',
			'absentCreateStatus' => 'tentative',
		]);
		// Assert
		$this->assertTrue($result);
	}

	public function testHandleImipWithAbsentIgnoreOption(): void {
		// construct mock user calendar (no matching event found)
		$userCalendar = $this->createMock(ITestCalendar::class);
		$userCalendar->expects(self::once())
			->method('isDeleted')
			->willReturn(false);
		$userCalendar->expects(self::once())
			->method('isWritable')
			->willReturn(true);
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
				$this->propertyMapper,
			])
			->onlyMethods(['getCalendarsForPrincipal'])
			->getMock();
		$manager->expects(self::once())
			->method('getCalendarsForPrincipal')
			->willReturn([$userCalendar]);
		// construct logger returns - should log warning since event not found and absent=ignore
		$this->logger->expects(self::once())->method('warning')
			->with('iMip message could not be processed because no corresponding event was found in any calendar');
		// construct parameters
		$userId = 'attendee1';
		$calendar = $this->vCalendar1a;
		$calendar->add('METHOD', 'REQUEST');
		// test method with absent=ignore option
		$result = $manager->handleIMip($userId, $calendar->serialize(), [
			'absent' => 'ignore',
		]);
		// Assert
		$this->assertFalse($result);
	}

	public function testHandleImipWithAbsentCreateNoWritableCalendar(): void {
		// construct mock user calendar (not writable)
		$userCalendar = $this->createMock(ITestCalendar::class);
		$userCalendar->expects(self::exactly(2))
			->method('isDeleted')
			->willReturn(false);
		$userCalendar->expects(self::exactly(2))
			->method('isWritable')
			->willReturn(false);
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
				$this->propertyMapper,
			])
			->onlyMethods(['getCalendarsForPrincipal', 'getPrimaryCalendar'])
			->getMock();
		$manager->expects(self::once())
			->method('getCalendarsForPrincipal')
			->willReturn([$userCalendar]);
		$manager->expects(self::once())
			->method('getPrimaryCalendar')
			->willReturn(null);
		// construct logger returns
		$this->logger->expects(self::once())->method('warning')
			->with('iMip message could not be processed because no writable calendar was found');
		// construct parameters
		$userId = 'attendee1';
		$calendar = $this->vCalendar1a;
		$calendar->add('METHOD', 'REQUEST');
		// test method with absent=create option but no writable calendar
		$result = $manager->handleIMip($userId, $calendar->serialize(), [
			'absent' => 'create',
			'absentCreateStatus' => 'tentative',
		]);
		// Assert
		$this->assertFalse($result);
	}

	public function testHandleImipWithAbsentCreateUsesPrimaryCalendar(): void {
		// construct mock user calendar (no matching event found)
		$userCalendar = $this->createMock(ITestCalendar::class);
		$userCalendar->expects(self::once())
			->method('isDeleted')
			->willReturn(false);
		$userCalendar->expects(self::once())
			->method('isWritable')
			->willReturn(true);
		$userCalendar->expects(self::once())
			->method('search')
			->willReturn([]);
		// construct mock primary calendar
		$primaryCalendar = $this->createMock(ITestCalendar::class);
		$primaryCalendar->expects(self::once())
			->method('isDeleted')
			->willReturn(false);
		$primaryCalendar->expects(self::once())
			->method('isWritable')
			->willReturn(true);
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
				$this->propertyMapper,
			])
			->onlyMethods(['getCalendarsForPrincipal', 'getPrimaryCalendar'])
			->getMock();
		$manager->expects(self::once())
			->method('getCalendarsForPrincipal')
			->willReturn([$userCalendar]);
		$manager->expects(self::once())
			->method('getPrimaryCalendar')
			->willReturn($primaryCalendar);
		// construct parameters
		$userId = 'attendee1';
		$calendar = $this->vCalendar1a;
		$calendar->add('METHOD', 'REQUEST');
		// primary calendar should receive the event
		$primaryCalendar->expects(self::once())
			->method('handleIMipMessage')
			->with($userId, self::callback(function ($data) {
				return str_contains($data, 'STATUS:TENTATIVE');
			}));
		// test method with absent=create option
		$result = $manager->handleIMip($userId, $calendar->serialize(), [
			'absent' => 'create',
			'absentCreateStatus' => 'tentative',
		]);
		// Assert
		$this->assertTrue($result);
	}

	public function testHandleImipWithAbsentCreateOverwritesExistingStatus(): void {
		// construct mock user calendar (no matching event found)
		$userCalendar = $this->createMock(ITestCalendar::class);
		$userCalendar->expects(self::once())
			->method('isDeleted')
			->willReturn(false);
		$userCalendar->expects(self::exactly(2))
			->method('isWritable')
			->willReturn(true);
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
				$this->propertyMapper,
			])
			->onlyMethods(['getCalendarsForPrincipal', 'getPrimaryCalendar'])
			->getMock();
		$manager->expects(self::once())
			->method('getCalendarsForPrincipal')
			->willReturn([$userCalendar]);
		$manager->expects(self::once())
			->method('getPrimaryCalendar')
			->willReturn(null);
		// construct parameters - calendar already has CONFIRMED status
		$userId = 'attendee1';
		$calendar = $this->vCalendar1a;
		$calendar->add('METHOD', 'REQUEST');
		// The original event has STATUS:CONFIRMED, but it should be overwritten to TENTATIVE
		$userCalendar->expects(self::once())
			->method('handleIMipMessage')
			->with($userId, self::callback(function ($data) {
				// Should contain TENTATIVE and not CONFIRMED
				return str_contains($data, 'STATUS:TENTATIVE') && !str_contains($data, 'STATUS:CONFIRMED');
			}));
		// test method with absent=create option
		$result = $manager->handleIMip($userId, $calendar->serialize(), [
			'absent' => 'create',
			'absentCreateStatus' => 'tentative',
		]);
		// Assert
		$this->assertTrue($result);
	}

	public function testhandleIMipRequestWithInvalidPrincipal() {
		$invalidPrincipal = 'invalid-principal-uri';
		$sender = 'sender@example.com';
		$recipient = 'recipient@example.com';
		$calendarData = $this->vCalendar1a->serialize();

		$this->logger->expects(self::once())
			->method('error')
			->with('Invalid principal URI provided for iMip request');

		$result = $this->manager->handleIMipRequest($invalidPrincipal, $sender, $recipient, $calendarData);
		$this->assertFalse($result);
	}

	public function testhandleIMipRequest() {
		$principalUri = 'principals/users/attendee1';
		$sender = 'sender@example.com';
		$recipient = 'recipient@example.com';
		$calendarData = $this->vCalendar1a->serialize();

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
				$this->propertyMapper,
			])
			->onlyMethods(['handleIMip'])
			->getMock();
		$manager->expects(self::once())
			->method('handleIMip')
			->with('attendee1', $calendarData)
			->willReturn(true);

		$result = $manager->handleIMipRequest($principalUri, $sender, $recipient, $calendarData);
		$this->assertTrue($result);
	}

	public function testhandleIMipReplyWithInvalidPrincipal() {
		$invalidPrincipal = 'invalid-principal-uri';
		$sender = 'sender@example.com';
		$recipient = 'recipient@example.com';
		$calendarData = $this->vCalendar2a->serialize();

		$this->logger->expects(self::once())
			->method('error')
			->with('Invalid principal URI provided for iMip reply');

		$result = $this->manager->handleIMipReply($invalidPrincipal, $sender, $recipient, $calendarData);
		$this->assertFalse($result);
	}

	public function testhandleIMipReply() {
		$principalUri = 'principals/users/attendee2';
		$sender = 'sender@example.com';
		$recipient = 'recipient@example.com';
		$calendarData = $this->vCalendar2a->serialize();

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
				$this->propertyMapper,
			])
			->onlyMethods(['handleIMip'])
			->getMock();
		$manager->expects(self::once())
			->method('handleIMip')
			->with('attendee2', $calendarData)
			->willReturn(true);

		$result = $manager->handleIMipReply($principalUri, $sender, $recipient, $calendarData);
		$this->assertTrue($result);
	}

	public function testhandleIMipCancelWithInvalidPrincipal() {
		$invalidPrincipal = 'invalid-principal-uri';
		$sender = 'sender@example.com';
		$replyTo = null;
		$recipient = 'recipient@example.com';
		$calendarData = $this->vCalendar3a->serialize();

		$this->logger->expects(self::once())
			->method('error')
			->with('Invalid principal URI provided for iMip cancel');

		$result = $this->manager->handleIMipCancel($invalidPrincipal, $sender, $replyTo, $recipient, $calendarData);
		$this->assertFalse($result);
	}

	public function testhandleIMipCancel() {
		$principalUri = 'principals/users/attendee3';
		$sender = 'sender@example.com';
		$replyTo = null;
		$recipient = 'recipient@example.com';
		$calendarData = $this->vCalendar3a->serialize();

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
				$this->propertyMapper,
			])
			->onlyMethods(['handleIMip'])
			->getMock();
		$manager->expects(self::once())
			->method('handleIMip')
			->with('attendee3', $calendarData)
			->willReturn(true);

		$result = $manager->handleIMipCancel($principalUri, $sender, $replyTo, $recipient, $calendarData);
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
