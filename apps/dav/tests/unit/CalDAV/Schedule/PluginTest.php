<?php

declare(strict_types=1);
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
use OCP\Config\IUserConfig;
use OCP\IAppConfig;
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
	private Plugin $plugin;
	private Server&MockObject $server;
	private IConfig&MockObject $config;
	private LoggerInterface&MockObject $logger;
	private DefaultCalendarValidator $calendarValidator;
	private CalDavBackend&MockObject $caldavBackend;
	private IUserConfig&MockObject $userConfig;
	private IAppConfig&MockObject $appConfig;

	protected function setUp(): void {
		parent::setUp();

		$this->config = $this->createMock(IConfig::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->calendarValidator = new DefaultCalendarValidator();
		$this->caldavBackend = $this->createMock(CalDavBackend::class);
		$this->userConfig = $this->createMock(IUserConfig::class);
		$this->appConfig = $this->createMock(IAppConfig::class);

		$this->server = $this->createMock(Server::class);
		$this->server->httpResponse = $this->createMock(ResponseInterface::class);
		$this->server->xml = new Service();

		$this->plugin = new Plugin($this->config, $this->logger, $this->calendarValidator, $this->caldavBackend, $this->userConfig, $this->appConfig);
		$this->plugin->initialize($this->server);
	}

	public function testInitialize(): void {
		$calls = [
			// Sabre\CalDAV\Schedule\Plugin events
			['method:POST', [$this->plugin, 'httpPost'], 100],
			['propFind', [$this->plugin, 'propFind'], 100],
			['propPatch', [$this->plugin, 'propPatch'], 100],
			['calendarObjectChange', [$this->plugin, 'calendarObjectChange'], 100],
			['beforeUnbind', [$this->plugin, 'beforeUnbind'], 100],
			['schedule', [$this->plugin, 'scheduleLocalDelivery'], 100],
			['getSupportedPrivilegeSet', [$this->plugin, 'getSupportedPrivilegeSet'], 100],
			// OCA\DAV\CalDAV\Schedule\Plugin events
			['propFind', [$this->plugin, 'propFindDefaultCalendarUrl'], 90],
			['afterWriteContent', [$this->plugin, 'dispatchSchedulingResponses'], 100],
			['afterCreateFile', [$this->plugin, 'dispatchSchedulingResponses'], 100],
		];
		$this->server->expects($this->exactly(count($calls)))
			->method('on')
			->willReturnCallback(function () use (&$calls): void {
				$expected = array_shift($calls);
				$this->assertEquals($expected, func_get_args());
			});

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

	public static function propFindDefaultCalendarUrlProvider(): array {
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

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'propFindDefaultCalendarUrlProvider')]
	public function testPropFindDefaultCalendarUrl(string $principalUri, ?string $calendarHome, bool $isResource, string $calendarUri, string $displayName, bool $exists, bool $deleted = false, bool $hasExistingCalendars = false, bool $propertiesForPath = true): void {
		$propFind = new PropFind(
			$principalUri,
			[
				Plugin::SCHEDULE_DEFAULT_CALENDAR_URL
			],
			0
		);
		/** @var IPrincipal&MockObject $node */
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

		/** @var Tree&MockObject $tree */
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

	// ----------------------------------------------------------------------------
	// nextcloud/calendar#6315: receive-side default reminder + preserve-on-update
	// ----------------------------------------------------------------------------

	private function makeTestablePlugin(): TestableSchedulePlugin {
		$plugin = new TestableSchedulePlugin(
			$this->config,
			$this->logger,
			$this->calendarValidator,
			$this->caldavBackend,
			$this->userConfig,
			$this->appConfig,
		);
		$plugin->initialize($this->server);
		return $plugin;
	}

	/**
	 * Build an iTip\Message with one or more VEVENT components for testing
	 * scheduleLocalDelivery.
	 *
	 * @param string $method REQUEST|REPLY|CANCEL|REFRESH
	 * @param array $components Each item: ['rid' => null|string, 'isAllDay' => bool, 'valarms' => list of TRIGGER strings]
	 */
	private function makeITipMessage(string $method, array $components, string $organizerUri = 'mailto:organizer@example.org', string $recipientUri = 'mailto:test@example.org'): Message {
		$vCal = new VCalendar();
		foreach ($components as $i => $comp) {
			$rid = $comp['rid'] ?? null;
			$isAllDay = $comp['isAllDay'] ?? false;
			$valarms = $comp['valarms'] ?? [];
			$vevent = $vCal->add('VEVENT', [
				'UID' => 'UID-6315',
				'SUMMARY' => 'Test event',
				'DTSTART' => $isAllDay
					? new \DateTimeImmutable('2026-06-15')
					: new \DateTimeImmutable('2026-06-15T09:00:00'),
				'DTSTAMP' => new \DateTimeImmutable('2026-06-10T10:00:00'),
				'ORGANIZER' => $organizerUri,
				'ATTENDEE' => $recipientUri,
			]);
			if ($isAllDay) {
				$vevent->DTSTART['VALUE'] = 'DATE';
			}
			if ($rid !== null) {
				$vevent->add('RECURRENCE-ID', $rid);
			}
			foreach ($valarms as $trigger) {
				$alarm = $vevent->add('VALARM');
				$alarm->add('ACTION', 'DISPLAY');
				$alarm->add('TRIGGER', $trigger);
			}
		}
		$msg = new Message();
		$msg->method = $method;
		$msg->message = $vCal;
		$msg->uid = 'UID-6315';
		$msg->recipient = $recipientUri;
		$msg->sender = $organizerUri;
		return $msg;
	}

	/**
	 * Stub the ACL plugin lookup and getProperties call (for the recipient-user-type check).
	 */
	private function stubAclAndUserType(string $principalUri = 'principals/users/test', string $userType = 'INDIVIDUAL'): void {
		$aclPlugin = $this->createMock(\Sabre\DAVACL\Plugin::class);
		$aclPlugin->method('getPrincipalByUri')->willReturn($principalUri);
		$this->server->method('getPlugin')->willReturnMap([
			['acl', $aclPlugin],
		]);
		$this->server->method('getProperties')->willReturn([
			'{' . Plugin::NS_CALDAV . '}calendar-user-type' => $userType,
		]);
	}

	private function countValarms(VCalendar $vCal): int {
		$n = 0;
		foreach ($vCal->VEVENT as $vevent) {
			if (isset($vevent->VALARM)) {
				$n += count($vevent->VALARM);
			}
		}
		return $n;
	}

	public function testSkipsNonRequest(): void {
		$plugin = $this->makeTestablePlugin();
		// No mocks needed for REPLY because we return before touching the reminder logic.
		$msg = $this->makeITipMessage('REPLY', [['valarms' => ['-PT15M']]]);
		$plugin->scheduleLocalDelivery($msg);
		// Existing strip still runs on all VEVENTs regardless of method.
		$this->assertSame(0, $this->countValarms($msg->message));
	}

	public function testStripsOrganizerValarmsFromAllComponents(): void {
		$plugin = $this->makeTestablePlugin();
		$this->stubAclAndUserType();
		$this->caldavBackend->method('getCalendarObjectByUID')->willReturn(null);
		// Toggle on, but every reminder source resolves to 'none' so nothing is injected.
		$this->userConfig->method('getValueString')->willReturnMap([
			['test', 'calendar', 'applyDefaultReminderToInvitations', 'yes', 'yes'],
			['test', 'calendar', 'defaultReminderPartDay', 'none', 'none'],
			['test', 'calendar', 'defaultReminder', 'none', 'none'],
		]);
		$this->appConfig->method('getValueString')->willReturnMap([
			['calendar', 'defaultReminder', 'none', 'none'],
		]);

		// Master + override, each with one organizer VALARM.
		$msg = $this->makeITipMessage('REQUEST', [
			['valarms' => ['PT0S']],
			['rid' => '20260616T090000Z', 'valarms' => ['PT0S']],
		]);
		$plugin->scheduleLocalDelivery($msg);

		// All organizer VALARMs stripped, no default injected.
		$this->assertSame(0, $this->countValarms($msg->message));
	}

	public function testFirstReceiptInjectsTypedPartDayDefault(): void {
		$plugin = $this->makeTestablePlugin();
		$this->stubAclAndUserType();
		$this->caldavBackend->method('getCalendarObjectByUID')->willReturn(null);
		$this->userConfig->method('getValueString')->willReturnMap([
			['test', 'calendar', 'applyDefaultReminderToInvitations', 'yes', 'yes'],
			['test', 'calendar', 'defaultReminderPartDay', 'none', '-900'],
		]);

		$msg = $this->makeITipMessage('REQUEST', [['valarms' => []]]);
		$plugin->scheduleLocalDelivery($msg);

		$this->assertSame(1, $this->countValarms($msg->message));
		$trigger = (string)$msg->message->VEVENT[0]->VALARM->TRIGGER;
		$this->assertSame('-PT15M', $trigger);
	}

	public function testFirstReceiptInjectsTypedFullDayDefault(): void {
		$plugin = $this->makeTestablePlugin();
		$this->stubAclAndUserType();
		$this->caldavBackend->method('getCalendarObjectByUID')->willReturn(null);
		$this->userConfig->method('getValueString')->willReturnMap([
			['test', 'calendar', 'applyDefaultReminderToInvitations', 'yes', 'yes'],
			['test', 'calendar', 'defaultReminderFullDay', 'none', '-3600'],
		]);

		$msg = $this->makeITipMessage('REQUEST', [['isAllDay' => true]]);
		$plugin->scheduleLocalDelivery($msg);

		$this->assertSame(1, $this->countValarms($msg->message));
		$this->assertSame('-PT1H', (string)$msg->message->VEVENT[0]->VALARM->TRIGGER);
	}

	public function testFirstReceiptFallsBackToLegacyAndAppValue(): void {
		$plugin = $this->makeTestablePlugin();
		$this->stubAclAndUserType();
		$this->caldavBackend->method('getCalendarObjectByUID')->willReturn(null);
		// Toggle 'yes', typed PartDay 'none', legacy 'none' -> falls through to app value.
		$this->userConfig->method('getValueString')->willReturnMap([
			['test', 'calendar', 'applyDefaultReminderToInvitations', 'yes', 'yes'],
			['test', 'calendar', 'defaultReminderPartDay', 'none', 'none'],
			['test', 'calendar', 'defaultReminder', 'none', 'none'],
		]);
		$this->appConfig->method('getValueString')->willReturnMap([
			['calendar', 'defaultReminder', 'none', '-300'],
		]);

		$msg = $this->makeITipMessage('REQUEST', [[]]);
		$plugin->scheduleLocalDelivery($msg);

		$this->assertSame(1, $this->countValarms($msg->message));
		$this->assertSame('-PT5M', (string)$msg->message->VEVENT[0]->VALARM->TRIGGER);
	}

	public function testFirstReceiptSkippedWhenToggleOff(): void {
		$plugin = $this->makeTestablePlugin();
		$this->stubAclAndUserType();
		$this->caldavBackend->method('getCalendarObjectByUID')->willReturn(null);
		$this->userConfig->method('getValueString')->willReturnMap([
			['test', 'calendar', 'applyDefaultReminderToInvitations', 'yes', 'no'],
			['test', 'calendar', 'defaultReminderPartDay', 'none', '-900'],
		]);

		$msg = $this->makeITipMessage('REQUEST', [[]]);
		$plugin->scheduleLocalDelivery($msg);

		$this->assertSame(0, $this->countValarms($msg->message));
	}

	public function testPreservesExistingValarmsOnUpdate(): void {
		$plugin = $this->makeTestablePlugin();
		$this->stubAclAndUserType();
		$this->caldavBackend->method('getCalendarObjectByUID')->willReturn('personal/UID-6315.ics');
		$this->caldavBackend->method('getCalendarByUri')->willReturn(['id' => 42]);
		$this->caldavBackend->method('getCalendarObject')->willReturn([
			'calendardata' => "BEGIN:VCALENDAR\nVERSION:2.0\nBEGIN:VEVENT\nUID:UID-6315\nDTSTART:20260615T090000Z\nDTSTAMP:20260610T100000Z\nBEGIN:VALARM\nACTION:DISPLAY\nTRIGGER:-PT45M\nDESCRIPTION:User reminder\nEND:VALARM\nEND:VEVENT\nEND:VCALENDAR\n",
		]);
		// No user-config calls should happen on update path; nothing to mock.

		$msg = $this->makeITipMessage('REQUEST', [['valarms' => ['PT0S']]]);
		$plugin->scheduleLocalDelivery($msg);

		// Organizer VALARM stripped, recipient's existing -PT45M preserved.
		$this->assertSame(1, $this->countValarms($msg->message));
		$this->assertSame('-PT45M', (string)$msg->message->VEVENT[0]->VALARM->TRIGGER);
	}

	public function testExistingCopyWithoutAlarmsSkipsInjection(): void {
		$plugin = $this->makeTestablePlugin();
		$this->stubAclAndUserType();
		$this->caldavBackend->method('getCalendarObjectByUID')->willReturn('personal/UID-6315.ics');
		$this->caldavBackend->method('getCalendarByUri')->willReturn(['id' => 42]);
		$this->caldavBackend->method('getCalendarObject')->willReturn([
			'calendardata' => "BEGIN:VCALENDAR\nVERSION:2.0\nBEGIN:VEVENT\nUID:UID-6315\nDTSTART:20260615T090000Z\nDTSTAMP:20260610T100000Z\nEND:VEVENT\nEND:VCALENDAR\n",
		]);
		// Recipient deleted their reminder; an organizer update must not re-inject a default.
		$this->userConfig->expects($this->never())->method('getValueString');

		$msg = $this->makeITipMessage('REQUEST', [['valarms' => ['PT0S']]]);
		$plugin->scheduleLocalDelivery($msg);

		$this->assertSame(0, $this->countValarms($msg->message));
	}

	public function testPreservesPerOverrideValarms(): void {
		$plugin = $this->makeTestablePlugin();
		$this->stubAclAndUserType();
		$this->caldavBackend->method('getCalendarObjectByUID')->willReturn('personal/UID-6315.ics');
		$this->caldavBackend->method('getCalendarByUri')->willReturn(['id' => 42]);
		$this->caldavBackend->method('getCalendarObject')->willReturn([
			'calendardata' => "BEGIN:VCALENDAR\nVERSION:2.0\n"
				. "BEGIN:VEVENT\nUID:UID-6315\nDTSTART:20260615T090000Z\nDTSTAMP:20260610T100000Z\n"
				. "BEGIN:VALARM\nACTION:DISPLAY\nTRIGGER:-PT15M\nDESCRIPTION:r\nEND:VALARM\nEND:VEVENT\n"
				. "BEGIN:VEVENT\nUID:UID-6315\nDTSTART:20260616T090000Z\nDTSTAMP:20260610T100000Z\nRECURRENCE-ID:20260616T090000Z\n"
				. "BEGIN:VALARM\nACTION:DISPLAY\nTRIGGER:-PT5M\nDESCRIPTION:r\nEND:VALARM\nEND:VEVENT\n"
				. "END:VCALENDAR\n",
		]);

		// Incoming master + same override, each with organizer VALARM that must be stripped.
		$msg = $this->makeITipMessage('REQUEST', [
			['valarms' => ['PT0S']],
			['rid' => '20260616T090000Z', 'valarms' => ['PT0S']],
		]);
		$plugin->scheduleLocalDelivery($msg);

		$this->assertSame(2, $this->countValarms($msg->message));
		// Master kept its existing -PT15M, override kept its existing -PT5M.
		$this->assertSame('-PT15M', (string)$msg->message->VEVENT[0]->VALARM->TRIGGER);
		$this->assertSame('-PT5M', (string)$msg->message->VEVENT[1]->VALARM->TRIGGER);
	}

	public function testSecondsToIso8601Duration(): void {
		$plugin = $this->makeTestablePlugin();
		$ref = new \ReflectionMethod($plugin, 'secondsToIso8601Duration');
		$ref->setAccessible(true);
		$this->assertSame('PT0S', $ref->invoke($plugin, 0));
		$this->assertSame('PT30S', $ref->invoke($plugin, 30));
		$this->assertSame('PT15M', $ref->invoke($plugin, 900));
		$this->assertSame('PT1H', $ref->invoke($plugin, 3600));
		$this->assertSame('P1D', $ref->invoke($plugin, 86400));
		$this->assertSame('P7D', $ref->invoke($plugin, 604800));
	}

	public function testPrincipalUriToUserId(): void {
		$plugin = $this->makeTestablePlugin();
		$ref = new \ReflectionMethod($plugin, 'principalUriToUserId');
		$ref->setAccessible(true);
		$this->assertSame('alice', $ref->invoke($plugin, 'principals/users/alice'));
		$this->assertNull($ref->invoke($plugin, 'principals/groups/staff'));
		$this->assertNull($ref->invoke($plugin, 'principals/users/'));
		$this->assertNull($ref->invoke($plugin, 'other/path'));
	}
}

/**
 * Subclass that no-ops the delegation to Sabre's parent::scheduleLocalDelivery,
 * so test cases can exercise the NC hook logic without setting up a full DAV tree.
 */
class TestableSchedulePlugin extends Plugin {
	protected function delegateToSabre(Message $iTipMessage): void {
		// no-op
	}
}
