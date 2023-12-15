<?php
/**
 * @copyright Copyright (c) 2016 Lukas Reschke <lukas@statuscode.ch>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
namespace OCA\DAV\Tests\unit\CalDAV\Schedule;

use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\CalDAV\Calendar;
use OCA\DAV\CalDAV\CalendarHome;
use OCA\DAV\CalDAV\Plugin as CalDAVPlugin;
use OCA\DAV\CalDAV\Schedule\Plugin;
use OCA\DAV\CalDAV\Trashbin\Plugin as TrashbinPlugin;
use OCP\IConfig;
use OCP\IL10N;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
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
use Sabre\VObject\Component\VEvent;
use Sabre\VObject\ITip\Message;
use Sabre\VObject\Parameter;
use Sabre\VObject\Property\ICalendar\CalAddress;
use Sabre\VObject\Reader;
use Sabre\Xml\Service;
use Test\TestCase;

class PluginTest extends TestCase {
	/** @var Plugin */
	private $plugin;
	/** @var Server|MockObject */
	private $server;

	/** @var IConfig|MockObject  */
	private $config;

	/** @var MockObject|LoggerInterface */
	private $logger;

	protected function setUp(): void {
		parent::setUp();

		$this->server = $this->createMock(Server::class);
		$this->config = $this->createMock(IConfig::class);

		$response = $this->getMockBuilder(ResponseInterface::class)
			->disableOriginalConstructor()
			->getMock();

		$this->server->httpResponse = $response;
		$this->server->xml = new Service();

		$this->logger = $this->createMock(LoggerInterface::class);

		$this->plugin = new Plugin($this->config, $this->logger);
		$this->plugin->initialize($this->server);
	}

	public function testInitialize(): void {
		$plugin = new Plugin($this->config, $this->logger);

		$this->server->expects($this->exactly(10))
			->method('on')
			->withConsecutive(
				// Sabre\CalDAV\Schedule\Plugin events
				['method:POST', [$plugin, 'httpPost']],
				['propFind', [$plugin, 'propFind']],
				['propPatch', [$plugin, 'propPatch']],
				['calendarObjectChange', [$plugin, 'calendarObjectChange']],
				['beforeUnbind', [$plugin, 'beforeUnbind']],
				['schedule', [$plugin, 'scheduleLocalDelivery']],
				['getSupportedPrivilegeSet', [$plugin, 'getSupportedPrivilegeSet']],
				// OCA\DAV\CalDAV\Schedule\Plugin events
				['propFind', [$plugin, 'propFindDefaultCalendarUrl'], 90],
				['afterWriteContent', [$plugin, 'dispatchSchedulingResponses']],
				['afterCreateFile', [$plugin, 'dispatchSchedulingResponses']]
			);

		$plugin->initialize($this->server);
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

				$calendarHomeObject->expects($this->once())
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
			->with($calendarHome .'/' . $calendarUri, [], 1)
			->willReturn($properties);

		$this->plugin->propFindDefaultCalendarUrl($propFind, $node);

		if (!$propertiesForPath) {
			$this->assertNull($propFind->get(Plugin::SCHEDULE_DEFAULT_CALENDAR_URL));
			return;
		}

		/** @var LocalHref $result */
		$result = $propFind->get(Plugin::SCHEDULE_DEFAULT_CALENDAR_URL);
		$this->assertEquals('/remote.php/dav/'. $calendarHome . '/' . $calendarUri, $result->getHref());
	}

	public function testCalendarObjectChangeShared() {
		// Calendar
		$calendarNode = new Calendar(
			$this->createMock(BackendInterface::class),
			[
				'uri' => 'alice-bob_shared_by_alice',
				'principaluri' => 'principals/users/bob',
				'{http://owncloud.org/ns}owner-principal' => 'principals/users/alice'
			],
			$this->createMock(IL10N::class),
			$this->createMock(IConfig::class),
			new NullLogger()
		);

		// Tree
		$tree = $this->createMock(Tree::class);
		$tree->expects($this->once())
			->method('getNodeForPath')
			->with('calendars/bob/alice-bob_shared_by_alice')
			->willReturn($calendarNode);

		$this->server->tree = $tree;

		// Request
		$request = new Request(
			'PUT',
			'/remote.php/dav/calendars/bob/alice-bob_shared_by_alice/B0DC78AE-6DD7-47E3-80BE-89F23E6D5383.ics'
		);
		$request->setBaseUrl('/remote.php/dav/');

		$this->server->httpRequest = $request;

		// Server.getProperties
		$addresses = new LocalHref([
			'mailto:bob@mail.localhost',
			'/remote.php/dav/principals/users/bob/'
		]);

		$this->server->expects($this->once())
			->method('getProperties')
			->with('principals/users/bob', ['{urn:ietf:params:xml:ns:caldav}calendar-user-address-set'])
			->willReturn([
				'{urn:ietf:params:xml:ns:caldav}calendar-user-address-set' => $addresses
			]);

		$response = new Response();

		// VCalendar / VEvent
		$data = 'BEGIN:VCALENDAR
CALSCALE:GREGORIAN
VERSION:2.0
PRODID:-//IDN nextcloud.com//Calendar app 4.5.0-alpha.1//EN
BEGIN:VEVENT
CREATED:20230808T153326Z
DTSTAMP:20230808T164811Z
LAST-MODIFIED:20230808T164811Z
UID:B0DC78AE-6DD7-47E3-80BE-89F23E6D5383
DTSTART:20330810T150000
DTEND:20330810T153000
SUMMARY:Event in shared calendar
ATTENDEE;CN=Jane;CUTYPE=INDIVIDUAL;PARTSTAT=NEEDS-ACTION;ROLE=REQ-PARTICIPANT;RSVP=TRUE;LANGUAGE=en:mailto:jane@mail.localhost
ATTENDEE;CN=John;CUTYPE=INDIVIDUAL;PARTSTAT=NEEDS-ACTION;ROLE=REQ-PARTICIPANT;RSVP=TRUE;LANGUAGE=en:mailto:john@mail.localhost
ORGANIZER;CN=Bob:mailto:bob@mail.localhost
END:VEVENT
END:VCALENDAR
';

		/** @var VCalendar $vCal */
		$vCal = Reader::read($data);

		$modified = false;
		$isNew = true;

		/** @var Message[] $iTipMessages */
		$iTipMessages = [];

		$this->server->expects($this->exactly(2))
			->method('emit')
			->willReturnCallback(function (string $eventName, array $arguments = [], callable $continueCallBack = null) use (&$iTipMessages) {
				$this->assertEquals('schedule', $eventName);
				$this->assertCount(1, $arguments);
				$iTipMessages[] = $arguments[0];
				return true;
			});

		$this->plugin->calendarObjectChange(
			$request,
			$response,
			$vCal,
			'calendars/bob/alice-bob_shared_by_alice',
			$modified,
			$isNew
		);

		/**
		 * VCalendar contains an event organized by Bob with Jane and John as attendees.
		 * The expected outcome is that for Jane and John an iTip message is generated.
		 */
		$this->assertCount(2, $iTipMessages);

		$this->assertEquals('mailto:bob@mail.localhost', $iTipMessages[0]->sender);
		$this->assertEquals('mailto:jane@mail.localhost', $iTipMessages[0]->recipient);
		$this->assertTrue($iTipMessages[0]->significantChange);

		$this->assertEquals('mailto:bob@mail.localhost', $iTipMessages[1]->sender);
		$this->assertEquals('mailto:john@mail.localhost', $iTipMessages[1]->recipient);
		$this->assertTrue($iTipMessages[1]->significantChange);
	}
}
