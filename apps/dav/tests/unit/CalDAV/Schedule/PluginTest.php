<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Tests\unit\CalDAV\Schedule;

use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\CalDAV\Calendar;
use OCA\DAV\CalDAV\CalendarHome;
use OCA\DAV\CalDAV\DefaultCalendarValidator;
use OCA\DAV\CalDAV\Plugin as CalDAVPlugin;
use OCA\DAV\CalDAV\Schedule\Plugin;
use OCA\DAV\CalDAV\Trashbin\Plugin as TrashbinPlugin;
use OCP\IConfig;
use OCP\IL10N;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Sabre\CalDAV\Backend\BackendInterface;
use Sabre\DAV\PropFind;
use Sabre\DAV\Server;
use Sabre\DAV\Tree;
use Sabre\DAV\Xml\Property\Href;
use Sabre\DAV\Xml\Property\LocalHref;
use Sabre\DAVACL\IPrincipal;
use Sabre\HTTP\Request;
use Sabre\HTTP\Response;
use Sabre\HTTP\ResponseInterface;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\ITip\Message;
use Sabre\VObject\Parameter;
use Sabre\VObject\Property\ICalendar\CalAddress;
use Sabre\Xml\Service;
use Test\TestCase;

class PluginTest extends TestCase {

	/** @var Plugin */
	private $plugin;

	/** @var Server|MockObject */
	private $server;

	/** @var IConfig|MockObject */
	private $config;

	/** @var LoggerInterface&MockObject */
	private $logger;

	/** @var DefaultCalendarValidator */
	private $calendarValidator;

	protected function setUp(): void {
		parent::setUp();

		$this->config = $this->createMock(IConfig::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->calendarValidator = new DefaultCalendarValidator();

		$this->server = $this->createMock(Server::class);
		$this->server->httpResponse = $this->getMockBuilder(ResponseInterface::class)
			->disableOriginalConstructor()
			->getMock();
		$this->server->xml = new Service();

		$this->plugin = new Plugin($this->config, $this->logger, $this->calendarValidator);
		$this->plugin->initialize($this->server);
	}

	public function testInitialize(): void {

		$this->server->expects($this->exactly(10))
			->method('on')
			->withConsecutive(
				// Sabre\CalDAV\Schedule\Plugin events
				['method:POST', [$this->plugin, 'httpPost']],
				['propFind', [$this->plugin, 'propFind']],
				['propPatch', [$this->plugin, 'propPatch']],
				['calendarObjectChange', [$this->plugin, 'calendarObjectChange']],
				['beforeUnbind', [$this->plugin, 'beforeUnbind']],
				['schedule', [$this->plugin, 'scheduleLocalDelivery']],
				['getSupportedPrivilegeSet', [$this->plugin, 'getSupportedPrivilegeSet']],
				// OCA\DAV\CalDAV\Schedule\Plugin events
				['propFind', [$this->plugin, 'propFindDefaultCalendarUrl'], 90],
				['afterWriteContent', [$this->plugin, 'dispatchSchedulingResponses']],
				['afterCreateFile', [$this->plugin, 'dispatchSchedulingResponses']]
			);

		$this->plugin->initialize($this->server);
	}

	public function testGetAddressesForPrincipal(): void {
		$href = $this->createMock(Href::class);
		$href
			->expects($this->once())
			->method('getHrefs')
			->willReturn(['lukas@nextcloud.com', 'rullzer@nextcloud.com']);
		$this->server
			->expects($this->once())
			->method('getProperties')
			->with(
				'MyPrincipal',
				[
					'{urn:ietf:params:xml:ns:caldav}calendar-user-address-set',
				]
			)
			->willReturn([
				'{urn:ietf:params:xml:ns:caldav}calendar-user-address-set' => $href
			]);

		$result = $this->invokePrivate($this->plugin, 'getAddressesForPrincipal', ['MyPrincipal']);
		$this->assertSame(['lukas@nextcloud.com', 'rullzer@nextcloud.com'], $result);
	}

	public function testGetAddressesForPrincipalEmpty(): void {
		$this->server
			->expects($this->once())
			->method('getProperties')
			->with(
				'MyPrincipal',
				[
					'{urn:ietf:params:xml:ns:caldav}calendar-user-address-set',
				]
			)
			->willReturn(null);

		$result = $this->invokePrivate($this->plugin, 'getAddressesForPrincipal', ['MyPrincipal']);
		$this->assertSame([], $result);
	}

	public function testStripOffMailTo(): void {
		$this->assertEquals('test@example.com', $this->invokePrivate($this->plugin, 'stripOffMailTo', ['test@example.com']));
		$this->assertEquals('test@example.com', $this->invokePrivate($this->plugin, 'stripOffMailTo', ['mailto:test@example.com']));
	}

	public function testGetAttendeeRSVP(): void {
		$property1 = $this->createMock(CalAddress::class);
		$parameter1 = $this->createMock(Parameter::class);
		$property1->expects($this->once())
			->method('offsetGet')
			->with('RSVP')
			->willReturn($parameter1);
		$parameter1->expects($this->once())
			->method('getValue')
			->with()
			->willReturn('TRUE');

		$property2 = $this->createMock(CalAddress::class);
		$parameter2 = $this->createMock(Parameter::class);
		$property2->expects($this->once())
			->method('offsetGet')
			->with('RSVP')
			->willReturn($parameter2);
		$parameter2->expects($this->once())
			->method('getValue')
			->with()
			->willReturn('FALSE');

		$property3 = $this->createMock(CalAddress::class);
		$property3->expects($this->once())
			->method('offsetGet')
			->with('RSVP')
			->willReturn(null);

		$this->assertTrue($this->invokePrivate($this->plugin, 'getAttendeeRSVP', [$property1]));
		$this->assertFalse($this->invokePrivate($this->plugin, 'getAttendeeRSVP', [$property2]));
		$this->assertFalse($this->invokePrivate($this->plugin, 'getAttendeeRSVP', [$property3]));
	}

	public function propFindDefaultCalendarUrlProvider(): array {
		return [
			[
				'principals/users/myuser',
				'calendars/myuser',
				false,
				CalDavBackend::PERSONAL_CALENDAR_URI,
				CalDavBackend::PERSONAL_CALENDAR_NAME,
				true
			],
			[
				'principals/users/myuser',
				'calendars/myuser',
				false,
				CalDavBackend::PERSONAL_CALENDAR_URI,
				CalDavBackend::PERSONAL_CALENDAR_NAME,
				true,
				true
			],
			[
				'principals/users/myuser',
				'calendars/myuser',
				false,
				CalDavBackend::PERSONAL_CALENDAR_URI,
				CalDavBackend::PERSONAL_CALENDAR_NAME,
				false,
				false,
				true
			],
			[
				'principals/users/myuser',
				'calendars/myuser',
				false,
				CalDavBackend::PERSONAL_CALENDAR_URI,
				CalDavBackend::PERSONAL_CALENDAR_NAME,
				false
			],
			[
				'principals/users/myuser',
				null,
				false,
				CalDavBackend::PERSONAL_CALENDAR_URI,
				CalDavBackend::PERSONAL_CALENDAR_NAME,
				true
			],
			[
				'principals/users/myuser',
				'calendars/myuser',
				false,
				CalDavBackend::PERSONAL_CALENDAR_URI,
				CalDavBackend::PERSONAL_CALENDAR_NAME,
				true,
				false,
				false,
				false,
			],
			[
				'principals/users/myuser',
				'calendars/myuser',
				false,
				'my_other_calendar',
				'My Other Calendar',
				true
			],
			[
				'principals/calendar-resources',
				'system-calendars/calendar-resources/myuser',
				true,
				CalDavBackend::RESOURCE_BOOKING_CALENDAR_URI,
				CalDavBackend::RESOURCE_BOOKING_CALENDAR_NAME,
				true
			],
			[
				'principals/calendar-resources',
				'system-calendars/calendar-resources/myuser',
				true,
				CalDavBackend::RESOURCE_BOOKING_CALENDAR_URI,
				CalDavBackend::RESOURCE_BOOKING_CALENDAR_NAME,
				false
			],
			[
				'principals/something-else',
				'calendars/whatever',
				false,
				CalDavBackend::PERSONAL_CALENDAR_URI,
				CalDavBackend::PERSONAL_CALENDAR_NAME,
				true
			],
		];
	}

	/**
	 * @dataProvider propFindDefaultCalendarUrlProvider
	 */
	public function testPropFindDefaultCalendarUrl(string $principalUri, ?string $calendarHome, bool $isResource, string $calendarUri, string $displayName, bool $exists, bool $deleted = false, bool $hasExistingCalendars = false, bool $propertiesForPath = true): void {
		$propFind = new PropFind(
			$principalUri,
			[
				Plugin::SCHEDULE_DEFAULT_CALENDAR_URL
			],
			0
		);
		/** @var IPrincipal|MockObject $node */
		$node = $this->getMockBuilder(IPrincipal::class)
			->disableOriginalConstructor()
			->getMock();

		$node->expects($this->once())
			->method('getPrincipalUrl')
			->with()
			->willReturn($principalUri);

		$calDAVPlugin = $this->getMockBuilder(CalDAVPlugin::class)
			->disableOriginalConstructor()
			->getMock();

		$calDAVPlugin->expects($this->once())
			->method('getCalendarHomeForPrincipal')
			->willReturn($calendarHome);

		$this->server->expects($this->once())
			->method('getPlugin')
			->with('caldav')
			->willReturn($calDAVPlugin);
		if (!$calendarHome) {
			$this->plugin->propFindDefaultCalendarUrl($propFind, $node);

			$this->assertNull($propFind->get(Plugin::SCHEDULE_DEFAULT_CALENDAR_URL));
			return;
		}
		if ($principalUri === 'principals/something-else') {
			$this->plugin->propFindDefaultCalendarUrl($propFind, $node);

			$this->assertNull($propFind->get(Plugin::SCHEDULE_DEFAULT_CALENDAR_URL));
			return;
		}

		if (!$isResource) {
			$this->config->expects($this->once())
				->method('getUserValue')
				->with('myuser', 'dav', 'defaultCalendar', CalDavBackend::PERSONAL_CALENDAR_URI)
				->willReturn($calendarUri);
		}

		$calendarHomeObject = $this->createMock(CalendarHome::class);
		$calendarHomeObject->expects($this->once())
			->method('childExists')
			->with($calendarUri)
			->willReturn($exists);

		if ($exists) {
			$calendar = $this->createMock(Calendar::class);
			$calendar->expects($this->once())->method('isDeleted')->willReturn($deleted);
			$calendarHomeObject->expects($deleted && !$hasExistingCalendars ? $this->exactly(2) : $this->once())->method('getChild')->with($calendarUri)->willReturn($calendar);
		}

		$calendarBackend = $this->createMock(CalDavBackend::class);
		$calendarUri = $hasExistingCalendars ? 'custom' : $calendarUri;
		$displayName = $hasExistingCalendars ? 'Custom Calendar' : $displayName;

		$existingCalendars = $hasExistingCalendars ? [
			new Calendar(
				$calendarBackend,
				['uri' => 'deleted', '{DAV:}displayname' => 'A deleted calendar', TrashbinPlugin::PROPERTY_DELETED_AT => 42],
				$this->createMock(IL10N::class),
				$this->config,
				$this->createMock(LoggerInterface::class)
			),
			new Calendar(
				$calendarBackend,
				['uri' => $calendarUri, '{DAV:}displayname' => $displayName],
				$this->createMock(IL10N::class),
				$this->config,
				$this->createMock(LoggerInterface::class)
			)
		] : [];

		if (!$exists || $deleted) {
			if (!$hasExistingCalendars) {
				$calendarBackend->expects($this->once())
					->method('createCalendar')
					->with($principalUri, $calendarUri, [
						'{DAV:}displayname' => $displayName,
					]);

				$calendarHomeObject->expects($this->exactly($deleted ? 2 : 1))
					->method('getCalDAVBackend')
					->with()
					->willReturn($calendarBackend);
			}

			if (!$isResource) {
				$calendarHomeObject->expects($this->once())
					->method('getChildren')
					->with()
					->willReturn($existingCalendars);
			}
		}

		/** @var Tree|MockObject $tree */
		$tree = $this->createMock(Tree::class);
		$tree->expects($this->once())
			->method('getNodeForPath')
			->with($calendarHome)
			->willReturn($calendarHomeObject);
		$this->server->tree = $tree;

		$properties = $propertiesForPath ? [
			['href' => '/remote.php/dav/' . $calendarHome . '/' . $calendarUri]
		] : [];

		$this->server->expects($this->once())
			->method('getPropertiesForPath')
			->with($calendarHome . '/' . $calendarUri, [], 1)
			->willReturn($properties);

		$this->plugin->propFindDefaultCalendarUrl($propFind, $node);

		if (!$propertiesForPath) {
			$this->assertNull($propFind->get(Plugin::SCHEDULE_DEFAULT_CALENDAR_URL));
			return;
		}

		/** @var LocalHref $result */
		$result = $propFind->get(Plugin::SCHEDULE_DEFAULT_CALENDAR_URL);
		$this->assertEquals('/remote.php/dav/' . $calendarHome . '/' . $calendarUri, $result->getHref());
	}

	/**
	 * Test Calendar Event Creation for Personal Calendar
	 *
	 * Should generate 2 messages for attendees User 2 and User External
	 */
	public function testCalendarObjectChangePersonalCalendarCreate(): void {

		// define place holders
		/** @var Message[] $iTipMessages */
		$iTipMessages = [];
		// construct calendar node
		$calendarNode = new Calendar(
			$this->createMock(BackendInterface::class),
			[
				'uri' => 'personal',
				'principaluri' => 'principals/users/user1',
				'{DAV:}displayname' => 'Calendar Shared By User1',
			],
			$this->createMock(IL10N::class),
			$this->config,
			$this->logger
		);
		// construct server request object
		$request = new Request(
			'PUT',
			'/remote.php/dav/calendars/user1/personal/B0DC78AE-6DD7-47E3-80BE-89F23E6D5383.ics'
		);
		$request->setBaseUrl('/remote.php/dav/');
		// construct server response object
		$response = new Response();
		// construct server tree object
		$tree = $this->createMock(Tree::class);
		$tree->expects($this->once())
			->method('getNodeForPath')
			->with('calendars/user1/personal')
			->willReturn($calendarNode);
		// construct server properties and returns
		$this->server->httpRequest = $request;
		$this->server->tree = $tree;
		$this->server->expects($this->exactly(1))->method('getProperties')
			->willReturnMap([
				[
					'principals/users/user1',
					['{urn:ietf:params:xml:ns:caldav}calendar-user-address-set'],
					['{urn:ietf:params:xml:ns:caldav}calendar-user-address-set' => new LocalHref(
						['mailto:user1@testing.local','/remote.php/dav/principals/users/user1/']
					)]
				]
			]);
		$this->server->expects($this->exactly(2))->method('emit')->willReturnCallback(
			function (string $eventName, array $arguments = [], ?callable $continueCallBack = null) use (&$iTipMessages) {
				$this->assertEquals('schedule', $eventName);
				$this->assertCount(1, $arguments);
				$iTipMessages[] = $arguments[0];
				return true;
			}
		);
		// construct calendar with a 1 hour event and same start/end time zones
		$vCalendar = new VCalendar();
		$vEvent = $vCalendar->add('VEVENT', []);
		$vEvent->UID->setValue('96a0e6b1-d886-4a55-a60d-152b31401dcc');
		$vEvent->add('DTSTART', '20240701T080000', ['TZID' => 'America/Toronto']);
		$vEvent->add('DTEND', '20240701T090000', ['TZID' => 'America/Toronto']);
		$vEvent->add('SUMMARY', 'Test Recurring Event');
		$vEvent->add('ORGANIZER', 'mailto:user1@testing.local', ['CN' => 'User One']);
		$vEvent->add('ATTENDEE', 'mailto:user2@testing.local', [
			'CN' => 'User Two',
			'CUTYPE' => 'INDIVIDUAL',
			'PARTSTAT' => 'NEEDS-ACTION',
			'ROLE' => 'REQ-PARTICIPANT',
			'RSVP' => 'TRUE'
		]);
		$vEvent->add('ATTENDEE', 'mailto:user@external.local', [
			'CN' => 'User External',
			'CUTYPE' => 'INDIVIDUAL',
			'PARTSTAT' => 'NEEDS-ACTION',
			'ROLE' => 'REQ-PARTICIPANT',
			'RSVP' => 'TRUE'
		]);
		// define flags
		$newFlag = true;
		$modifiedFlag = false;
		// execute method
		$this->plugin->calendarObjectChange(
			$request,
			$response,
			$vCalendar,
			'calendars/user1/personal',
			$modifiedFlag,
			$newFlag
		);
		// test for correct iTip message count
		$this->assertCount(2, $iTipMessages);
		// test for Sharer Attendee
		$this->assertEquals('mailto:user1@testing.local', $iTipMessages[0]->sender);
		$this->assertEquals('mailto:user2@testing.local', $iTipMessages[0]->recipient);
		$this->assertTrue($iTipMessages[0]->significantChange);
		// test for External Attendee
		$this->assertEquals('mailto:user1@testing.local', $iTipMessages[1]->sender);
		$this->assertEquals('mailto:user@external.local', $iTipMessages[1]->recipient);
		$this->assertTrue($iTipMessages[1]->significantChange);

	}

	/**
	 * Test Calendar Event Creation for Shared Calendar as Sharer/Owner
	 *
	 * Should generate 3 messages for attendees User 2 (Sharee), User 3 (Non-Sharee) and User External
	 */
	public function testCalendarObjectChangeSharedCalendarSharerCreate(): void {

		// define place holders
		/** @var Message[] $iTipMessages */
		$iTipMessages = [];
		// construct calendar node
		$calendarNode = new Calendar(
			$this->createMock(BackendInterface::class),
			[
				'uri' => 'calendar_shared_by_user1',
				'principaluri' => 'principals/users/user1',
				'{DAV:}displayname' => 'Calendar Shared By User1',
				'{http://owncloud.org/ns}owner-principal' => 'principals/users/user1'
			],
			$this->createMock(IL10N::class),
			$this->config,
			$this->logger
		);
		// construct server request object
		$request = new Request(
			'PUT',
			'/remote.php/dav/calendars/user1/calendar_shared_by_user1/B0DC78AE-6DD7-47E3-80BE-89F23E6D5383.ics'
		);
		$request->setBaseUrl('/remote.php/dav/');
		// construct server response object
		$response = new Response();
		// construct server tree object
		$tree = $this->createMock(Tree::class);
		$tree->expects($this->once())
			->method('getNodeForPath')
			->with('calendars/user1/calendar_shared_by_user1')
			->willReturn($calendarNode);
		// construct server properties and returns
		$this->server->httpRequest = $request;
		$this->server->tree = $tree;
		$this->server->expects($this->exactly(1))->method('getProperties')
			->willReturnMap([
				[
					'principals/users/user1',
					['{urn:ietf:params:xml:ns:caldav}calendar-user-address-set'],
					['{urn:ietf:params:xml:ns:caldav}calendar-user-address-set' => new LocalHref(
						['mailto:user1@testing.local','/remote.php/dav/principals/users/user1/']
					)]
				]
			]);
		$this->server->expects($this->exactly(3))->method('emit')->willReturnCallback(
			function (string $eventName, array $arguments = [], ?callable $continueCallBack = null) use (&$iTipMessages) {
				$this->assertEquals('schedule', $eventName);
				$this->assertCount(1, $arguments);
				$iTipMessages[] = $arguments[0];
				return true;
			}
		);
		// construct calendar with a 1 hour event and same start/end time zones
		$vCalendar = new VCalendar();
		$vEvent = $vCalendar->add('VEVENT', []);
		$vEvent->UID->setValue('96a0e6b1-d886-4a55-a60d-152b31401dcc');
		$vEvent->add('DTSTART', '20240701T080000', ['TZID' => 'America/Toronto']);
		$vEvent->add('DTEND', '20240701T090000', ['TZID' => 'America/Toronto']);
		$vEvent->add('SUMMARY', 'Test Recurring Event');
		$vEvent->add('ORGANIZER', 'mailto:user1@testing.local', ['CN' => 'User One']);
		$vEvent->add('ATTENDEE', 'mailto:user2@testing.local', [
			'CN' => 'User Two',
			'CUTYPE' => 'INDIVIDUAL',
			'PARTSTAT' => 'NEEDS-ACTION',
			'ROLE' => 'REQ-PARTICIPANT',
			'RSVP' => 'TRUE'
		]);
		$vEvent->add('ATTENDEE', 'mailto:user3@testing.local', [
			'CN' => 'User Three',
			'CUTYPE' => 'INDIVIDUAL',
			'PARTSTAT' => 'NEEDS-ACTION',
			'ROLE' => 'REQ-PARTICIPANT',
			'RSVP' => 'TRUE'
		]);
		$vEvent->add('ATTENDEE', 'mailto:user@external.local', [
			'CN' => 'User External',
			'CUTYPE' => 'INDIVIDUAL',
			'PARTSTAT' => 'NEEDS-ACTION',
			'ROLE' => 'REQ-PARTICIPANT',
			'RSVP' => 'TRUE'
		]);
		// define flags
		$newFlag = true;
		$modifiedFlag = false;
		// execute method
		$this->plugin->calendarObjectChange(
			$request,
			$response,
			$vCalendar,
			'calendars/user1/calendar_shared_by_user1',
			$modifiedFlag,
			$newFlag
		);
		// test for correct iTip message count
		$this->assertCount(3, $iTipMessages);
		// test for Sharer Attendee
		$this->assertEquals('mailto:user1@testing.local', $iTipMessages[0]->sender);
		$this->assertEquals('mailto:user2@testing.local', $iTipMessages[0]->recipient);
		$this->assertTrue($iTipMessages[0]->significantChange);
		// test for Non Shee Attendee
		$this->assertEquals('mailto:user1@testing.local', $iTipMessages[1]->sender);
		$this->assertEquals('mailto:user3@testing.local', $iTipMessages[1]->recipient);
		$this->assertTrue($iTipMessages[1]->significantChange);
		// test for External Attendee
		$this->assertEquals('mailto:user1@testing.local', $iTipMessages[2]->sender);
		$this->assertEquals('mailto:user@external.local', $iTipMessages[2]->recipient);
		$this->assertTrue($iTipMessages[2]->significantChange);

	}

	/**
	 * Test Calendar Event Creation for Shared Calendar as Shree
	 *
	 * Should generate 3 messages for attendees User 1 (Sharer/Owner), User 3 (Non-Sharee) and User External
	 */
	public function testCalendarObjectChangeSharedCalendarShreeCreate(): void {

		// define place holders
		/** @var Message[] $iTipMessages */
		$iTipMessages = [];
		// construct calendar node
		$calendarNode = new Calendar(
			$this->createMock(BackendInterface::class),
			[
				'uri' => 'calendar_shared_by_user1',
				'principaluri' => 'principals/users/user2',
				'{DAV:}displayname' => 'Calendar Shared By User1',
				'{http://owncloud.org/ns}owner-principal' => 'principals/users/user1'
			],
			$this->createMock(IL10N::class),
			$this->config,
			$this->logger
		);
		// construct server request object
		$request = new Request(
			'PUT',
			'/remote.php/dav/calendars/user2/calendar_shared_by_user1/B0DC78AE-6DD7-47E3-80BE-89F23E6D5383.ics'
		);
		$request->setBaseUrl('/remote.php/dav/');
		// construct server response object
		$response = new Response();
		// construct server tree object
		$tree = $this->createMock(Tree::class);
		$tree->expects($this->once())
			->method('getNodeForPath')
			->with('calendars/user2/calendar_shared_by_user1')
			->willReturn($calendarNode);
		// construct server properties and returns
		$this->server->httpRequest = $request;
		$this->server->tree = $tree;
		$this->server->expects($this->exactly(2))->method('getProperties')
			->willReturnMap([
				[
					'principals/users/user1',
					['{urn:ietf:params:xml:ns:caldav}calendar-user-address-set'],
					['{urn:ietf:params:xml:ns:caldav}calendar-user-address-set' => new LocalHref(
						['mailto:user1@testing.local','/remote.php/dav/principals/users/user1/']
					)]
				],
				[
					'principals/users/user2',
					['{urn:ietf:params:xml:ns:caldav}calendar-user-address-set'],
					['{urn:ietf:params:xml:ns:caldav}calendar-user-address-set' => new LocalHref(
						['mailto:user2@testing.local','/remote.php/dav/principals/users/user2/']
					)]
				]
			]);
		$this->server->expects($this->exactly(3))->method('emit')->willReturnCallback(
			function (string $eventName, array $arguments = [], ?callable $continueCallBack = null) use (&$iTipMessages) {
				$this->assertEquals('schedule', $eventName);
				$this->assertCount(1, $arguments);
				$iTipMessages[] = $arguments[0];
				return true;
			}
		);
		// construct calendar with a 1 hour event and same start/end time zones
		$vCalendar = new VCalendar();
		$vEvent = $vCalendar->add('VEVENT', []);
		$vEvent->UID->setValue('96a0e6b1-d886-4a55-a60d-152b31401dcc');
		$vEvent->add('DTSTART', '20240701T080000', ['TZID' => 'America/Toronto']);
		$vEvent->add('DTEND', '20240701T090000', ['TZID' => 'America/Toronto']);
		$vEvent->add('SUMMARY', 'Test Recurring Event');
		$vEvent->add('ORGANIZER', 'mailto:user2@testing.local', ['CN' => 'User Two']);
		$vEvent->add('ATTENDEE', 'mailto:user1@testing.local', [
			'CN' => 'User One',
			'CUTYPE' => 'INDIVIDUAL',
			'PARTSTAT' => 'NEEDS-ACTION',
			'ROLE' => 'REQ-PARTICIPANT',
			'RSVP' => 'TRUE'
		]);
		$vEvent->add('ATTENDEE', 'mailto:user3@testing.local', [
			'CN' => 'User Three',
			'CUTYPE' => 'INDIVIDUAL',
			'PARTSTAT' => 'NEEDS-ACTION',
			'ROLE' => 'REQ-PARTICIPANT',
			'RSVP' => 'TRUE'
		]);
		$vEvent->add('ATTENDEE', 'mailto:user@external.local', [
			'CN' => 'User External',
			'CUTYPE' => 'INDIVIDUAL',
			'PARTSTAT' => 'NEEDS-ACTION',
			'ROLE' => 'REQ-PARTICIPANT',
			'RSVP' => 'TRUE'
		]);
		// define flags
		$newFlag = true;
		$modifiedFlag = false;
		// execute method
		$this->plugin->calendarObjectChange(
			$request,
			$response,
			$vCalendar,
			'calendars/user2/calendar_shared_by_user1',
			$modifiedFlag,
			$newFlag
		);
		// test for correct iTip message count
		$this->assertCount(3, $iTipMessages);
		// test for Sharer Attendee
		$this->assertEquals('mailto:user2@testing.local', $iTipMessages[0]->sender);
		$this->assertEquals('mailto:user1@testing.local', $iTipMessages[0]->recipient);
		$this->assertTrue($iTipMessages[0]->significantChange);
		// test for Non Shee Attendee
		$this->assertEquals('mailto:user2@testing.local', $iTipMessages[1]->sender);
		$this->assertEquals('mailto:user3@testing.local', $iTipMessages[1]->recipient);
		$this->assertTrue($iTipMessages[1]->significantChange);
		// test for External Attendee
		$this->assertEquals('mailto:user2@testing.local', $iTipMessages[2]->sender);
		$this->assertEquals('mailto:user@external.local', $iTipMessages[2]->recipient);
		$this->assertTrue($iTipMessages[2]->significantChange);

	}

	/**
	 * Test Calendar Event Creation with iTip and iMip disabled
	 *
	 * Should generate 2 messages for attendees User 2 and User External
	 */
	public function testCalendarObjectChangeWithSchedulingDisabled(): void {
		// construct server request
		$request = new Request(
			'PUT',
			'/remote.php/dav/calendars/user1/personal/B0DC78AE-6DD7-47E3-80BE-89F23E6D5383.ics',
			['x-nc-scheduling' => 'false']
		);
		$request->setBaseUrl('/remote.php/dav/');
		// construct server response
		$response = new Response();
		// construct server tree
		$tree = $this->createMock(Tree::class);
		$tree->expects($this->never())
			->method('getNodeForPath');
		// construct server properties and returns
		$this->server->httpRequest = $request;
		$this->server->tree = $tree;
		// construct empty calendar event
		$vCalendar = new VCalendar();
		$vEvent = $vCalendar->add('VEVENT', []);
		// define flags
		$newFlag = true;
		$modifiedFlag = false;
		// execute method
		$this->plugin->calendarObjectChange(
			$request,
			$response,
			$vCalendar,
			'calendars/user1/personal',
			$modifiedFlag,
			$newFlag
		);
	}
}
