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
use Sabre\DAV\PropFind;
use Sabre\DAV\Server;
use Sabre\DAV\Tree;
use Sabre\DAV\Xml\Property\Href;
use Sabre\DAV\Xml\Property\LocalHref;
use Sabre\DAVACL\IPrincipal;
use Sabre\HTTP\ResponseInterface;
use Sabre\VObject\Parameter;
use Sabre\VObject\Property\ICalendar\CalAddress;
use Sabre\Xml\Service;
use Test\TestCase;

class PluginTest extends TestCase {
	/** @var Plugin */
	private $plugin;
	/** @var Server|MockObject */
	private $server;

	/** @var IConfig|MockObject  */
	private $config;

	protected function setUp(): void {
		parent::setUp();

		$this->server = $this->createMock(Server::class);
		$this->config = $this->createMock(IConfig::class);

		$response = $this->getMockBuilder(ResponseInterface::class)
			->disableOriginalConstructor()
			->getMock();

		$this->server->httpResponse = $response;
		$this->server->xml = new Service();

		$this->plugin = new Plugin($this->config);
		$this->plugin->initialize($this->server);
	}

	public function testInitialize() {
		$plugin = new Plugin($this->config);

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

	public function testGetAddressesForPrincipal() {
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


	public function testGetAddressesForPrincipalEmpty() {
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

	public function testStripOffMailTo() {
		$this->assertEquals('test@example.com', $this->invokePrivate($this->plugin, 'stripOffMailTo', ['test@example.com']));
		$this->assertEquals('test@example.com', $this->invokePrivate($this->plugin, 'stripOffMailTo', ['mailto:test@example.com']));
	}

	public function testGetAttendeeRSVP() {
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
	 * @param string $principalUri
	 * @param string|null $calendarHome
	 * @param bool $isResource
	 * @param string $calendarUri
	 * @param string $displayName
	 * @param bool $exists
	 * @param bool $propertiesForPath
	 */
	public function testPropFindDefaultCalendarUrl(string $principalUri, ?string $calendarHome, bool $isResource, string $calendarUri, string $displayName, bool $exists, bool $hasExistingCalendars = false, bool $propertiesForPath = true) {
		/** @var PropFind $propFind */
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

		if (!$exists) {
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
}
