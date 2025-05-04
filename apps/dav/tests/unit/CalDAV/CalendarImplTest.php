<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Tests\unit\CalDAV;

use Generator;
use OCA\DAV\CalDAV\Auth\CustomPrincipalPlugin;
use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\CalDAV\Calendar;
use OCA\DAV\CalDAV\CalendarImpl;
use OCA\DAV\CalDAV\InvitationResponse\InvitationResponseServer;
use OCA\DAV\CalDAV\Schedule\Plugin;
use OCA\DAV\Connector\Sabre\Server;
use OCP\Calendar\Exceptions\CalendarException;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Component\VEvent;
use Sabre\VObject\ITip\Message;

class CalendarImplTest extends \Test\TestCase {
	
	private CalDavBackend|MockObject $backend;
	private Calendar|MockObject $calendar;
	private CalendarImpl|MockObject $calendarImpl;
	private array $mockExportCollection;
	private array $calendarInfo;
	private VCalendar $vCalendar1a;

	protected function setUp(): void {
		parent::setUp();

		$this->backend = $this->createMock(CalDavBackend::class);
		$this->calendar = $this->createMock(Calendar::class);
		$this->calendarInfo = [
			'id' => 1,
			'{DAV:}displayname' => 'user readable name 123',
			'{http://apple.com/ns/ical/}calendar-color' => '#AABBCC',
			'uri' => '/this/is/a/uri',
			'principaluri' => 'principal/users/foobar'
		];
		$this->calendarImpl = new CalendarImpl(
			$this->calendar,
			$this->calendarInfo,
			$this->backend
		);
		// construct calendar with a 1 hour event and same start/end time zones
		$this->vCalendar1a = new VCalendar();
		/** @var VEvent $vEvent */
		$vEvent = $this->vCalendar1a->add('VEVENT', []);
		$vEvent->UID->setValue('96a0e6b1-d886-4a55-a60d-152b31401dcc');
		$vEvent->add('DTSTART', '20240701T080000', ['TZID' => 'America/Toronto']);
		$vEvent->add('DTEND', '20240701T090000', ['TZID' => 'America/Toronto']);
		$vEvent->add('SEQUENCE', 1);
		$vEvent->add('SUMMARY', 'Test Event');
		$vEvent->add('ORGANIZER', 'mailto:organizer@testing.com', ['CN' => 'Organizer']);
		$vEvent->add('ATTENDEE', 'mailto:attendee1@testing.com', [
			'CN' => 'Attendee One',
			'CUTYPE' => 'INDIVIDUAL',
			'PARTSTAT' => 'NEEDS-ACTION',
			'ROLE' => 'REQ-PARTICIPANT',
			'RSVP' => 'TRUE'
		]);
	}


	public function testGetKey(): void {
		$this->assertEquals($this->calendarImpl->getKey(), 1);
	}

	public function testGetDisplayname(): void {
		$this->assertEquals($this->calendarImpl->getDisplayName(), 'user readable name 123');
	}

	public function testGetDisplayColor(): void {
		$this->assertEquals($this->calendarImpl->getDisplayColor(), '#AABBCC');
	}

	public function testSearch(): void {
		$this->backend->expects($this->once())
			->method('search')
			->with($this->calendarInfo, 'abc', ['def'], ['ghi'], 42, 1337)
			->willReturn(['SEARCHRESULTS']);

		$result = $this->calendarImpl->search('abc', ['def'], ['ghi'], 42, 1337);
		$this->assertEquals($result, ['SEARCHRESULTS']);
	}

	public function testGetPermissionRead(): void {
		$this->calendar->expects($this->once())
			->method('getACL')
			->with()
			->willReturn([
				['privilege' => '{DAV:}read', 'principal' => 'principal/users/foobar'],
				['privilege' => '{DAV:}read', 'principal' => 'principal/users/other'],
				['privilege' => '{DAV:}write', 'principal' => 'principal/users/other'],
				['privilege' => '{DAV:}all', 'principal' => 'principal/users/other'],
			]);

		$this->assertEquals(1, $this->calendarImpl->getPermissions());
	}

	public function testGetPermissionWrite(): void {
		$this->calendar->expects($this->once())
			->method('getACL')
			->with()
			->willReturn([
				['privilege' => '{DAV:}write', 'principal' => 'principal/users/foobar'],
				['privilege' => '{DAV:}read', 'principal' => 'principal/users/other'],
				['privilege' => '{DAV:}all', 'principal' => 'principal/users/other'],
			]);

		$this->assertEquals(6, $this->calendarImpl->getPermissions());
	}

	public function testGetPermissionReadWrite(): void {
		$this->calendar->expects($this->once())
			->method('getACL')
			->with()
			->willReturn([
				['privilege' => '{DAV:}write', 'principal' => 'principal/users/foobar'],
				['privilege' => '{DAV:}read', 'principal' => 'principal/users/foobar'],
				['privilege' => '{DAV:}all', 'principal' => 'principal/users/other'],
			]);

		$this->assertEquals(7, $this->calendarImpl->getPermissions());
	}

	public function testGetPermissionAll(): void {
		$this->calendar->expects($this->once())
			->method('getACL')
			->with()
			->willReturn([
				['privilege' => '{DAV:}all', 'principal' => 'principal/users/foobar'],
			]);

		$this->assertEquals(31, $this->calendarImpl->getPermissions());
	}

	public function testHandleImipInvalidMethod(): void {

		$userAddressess = [
			'mailto:attendee1@testing.com',
			'/remote.php/dav/principals/users/attendee1/',
		];

		$vObject = $this->vCalendar1a;
		$vObject->add('METHOD', 'BLAHBLAHBLAH');

		/** @var CustomPrincipalPlugin|MockObject $authPlugin */
		$authPlugin = $this->createMock(CustomPrincipalPlugin::class);
		$authPlugin->expects(self::once())
			->method('setCurrentPrincipal')
			->with($this->calendar->getPrincipalURI());

		/** @var \OCA\DAV\CalDAV\Schedule\Plugin|MockObject $schedulingPlugin */
		$schedulingPlugin = $this->createMock(Plugin::class);
		$schedulingPlugin->expects(self::once())
			->method('getAddressesForPrincipal')
			->with($this->calendar->getPrincipalURI())
			->willReturn($userAddressess);

		$server = $this->createMock(Server::class);
		$server->expects($this->any())
			->method('getPlugin')
			->willReturnMap([
				['auth', $authPlugin],
				['caldav-schedule', $schedulingPlugin]
			]);

		$invitationResponseServer = $this->createMock(InvitationResponseServer::class);
		$invitationResponseServer->server = $server;
		$invitationResponseServer->expects($this->any())
			->method('getServer')
			->willReturn($server);

		$calendarImpl = $this->getMockBuilder(CalendarImpl::class)
			->setConstructorArgs([$this->calendar, $this->calendarInfo, $this->backend])
			->onlyMethods(['getInvitationResponseServer'])
			->getMock();
		$calendarImpl->expects($this->once())
			->method('getInvitationResponseServer')
			->willReturn($invitationResponseServer);

		$this->expectException(CalendarException::class);
		$calendarImpl->handleIMip($vObject);

	}

	public function testHandleImipInvalidAttendee(): void {

		$userAddressess = [
			'mailto:unknown@testing.com',
			'/remote.php/dav/principals/users/unknown/',
		];

		$vObject = $this->vCalendar1a;
		$vObject->add('METHOD', 'REQUEST');

		/** @var CustomPrincipalPlugin|MockObject $authPlugin */
		$authPlugin = $this->createMock(CustomPrincipalPlugin::class);
		$authPlugin->expects(self::once())
			->method('setCurrentPrincipal')
			->with($this->calendar->getPrincipalURI());

		/** @var \OCA\DAV\CalDAV\Schedule\Plugin|MockObject $schedulingPlugin */
		$schedulingPlugin = $this->createMock(Plugin::class);
		$schedulingPlugin->expects(self::once())
			->method('getAddressesForPrincipal')
			->with($this->calendar->getPrincipalURI())
			->willReturn($userAddressess);

		$server = $this->createMock(Server::class);
		$server->expects($this->any())
			->method('getPlugin')
			->willReturnMap([
				['auth', $authPlugin],
				['caldav-schedule', $schedulingPlugin]
			]);

		$invitationResponseServer = $this->createMock(InvitationResponseServer::class);
		$invitationResponseServer->server = $server;
		$invitationResponseServer->expects($this->any())
			->method('getServer')
			->willReturn($server);
			
		$calendarImpl = $this->getMockBuilder(CalendarImpl::class)
			->setConstructorArgs([$this->calendar, $this->calendarInfo, $this->backend])
			->onlyMethods(['getInvitationResponseServer'])
			->getMock();
		$calendarImpl->expects($this->once())
			->method('getInvitationResponseServer')
			->willReturn($invitationResponseServer);
		
		$this->expectException(CalendarException::class);
		$calendarImpl->handleIMip($vObject);
	}

	public function testHandleImipInvalidOrganizer(): void {

		$userAddressess = [
			'mailto:unknown@testing.com',
			'/remote.php/dav/principals/users/unknown/',
		];

		$vObject = $this->vCalendar1a;
		$vObject->add('METHOD', 'REPLY');

		/** @var CustomPrincipalPlugin|MockObject $authPlugin */
		$authPlugin = $this->createMock(CustomPrincipalPlugin::class);
		$authPlugin->expects(self::once())
			->method('setCurrentPrincipal')
			->with($this->calendar->getPrincipalURI());

		/** @var \OCA\DAV\CalDAV\Schedule\Plugin|MockObject $schedulingPlugin */
		$schedulingPlugin = $this->createMock(Plugin::class);
		$schedulingPlugin->expects(self::once())
			->method('getAddressesForPrincipal')
			->with($this->calendar->getPrincipalURI())
			->willReturn($userAddressess);

		$server = $this->createMock(Server::class);
		$server->expects($this->any())
			->method('getPlugin')
			->willReturnMap([
				['auth', $authPlugin],
				['caldav-schedule', $schedulingPlugin]
			]);

		$invitationResponseServer = $this->createMock(InvitationResponseServer::class);
		$invitationResponseServer->server = $server;
		$invitationResponseServer->expects($this->any())
			->method('getServer')
			->willReturn($server);
			
		$calendarImpl = $this->getMockBuilder(CalendarImpl::class)
			->setConstructorArgs([$this->calendar, $this->calendarInfo, $this->backend])
			->onlyMethods(['getInvitationResponseServer'])
			->getMock();
		$calendarImpl->expects($this->once())
			->method('getInvitationResponseServer')
			->willReturn($invitationResponseServer);
		
		$this->expectException(CalendarException::class);
		$calendarImpl->handleIMip($vObject);
	}

	public function testHandleImipRequest(): void {

		$userAddressess = [
			'mailto:attendee1@testing.com',
			'/remote.php/dav/principals/users/attendee1/',
		];

		$vObject = $this->vCalendar1a;
		$vObject->add('METHOD', 'REQUEST');

		$iTip = new Message();
		$iTip->method = 'REQUEST';
		$iTip->sender = $vObject->VEVENT->ORGANIZER->getValue();
		$iTip->recipient = $vObject->VEVENT->ATTENDEE->getValue();
		$iTip->component = 'VEVENT';
		$iTip->uid = $vObject->VEVENT->UID->getValue();
		$iTip->sequence = (int)$vObject->VEVENT->SEQUENCE->getValue() ?? 0;
		$iTip->message = $vObject;

		/** @var CustomPrincipalPlugin|MockObject $authPlugin */
		$authPlugin = $this->createMock(CustomPrincipalPlugin::class);
		$authPlugin->expects(self::once())
			->method('setCurrentPrincipal')
			->with($this->calendar->getPrincipalURI());

		/** @var \OCA\DAV\CalDAV\Schedule\Plugin|MockObject $schedulingPlugin */
		$schedulingPlugin = $this->createMock(Plugin::class);
		$schedulingPlugin->expects(self::once())
			->method('getAddressesForPrincipal')
			->with($this->calendar->getPrincipalURI())
			->willReturn($userAddressess);

		$server = $this->createMock(Server::class);
		$server->expects($this->any())
			->method('getPlugin')
			->willReturnMap([
				['auth', $authPlugin],
				['caldav-schedule', $schedulingPlugin]
			]);
		$server->expects(self::once())
			->method('emit')
			->with('schedule', [$iTip]);

		$invitationResponseServer = $this->createMock(InvitationResponseServer::class);
		$invitationResponseServer->server = $server;
		$invitationResponseServer->expects($this->any())
			->method('getServer')
			->willReturn($server);
			
		$calendarImpl = $this->getMockBuilder(CalendarImpl::class)
			->setConstructorArgs([$this->calendar, $this->calendarInfo, $this->backend])
			->onlyMethods(['getInvitationResponseServer'])
			->getMock();
		$calendarImpl->expects($this->once())
			->method('getInvitationResponseServer')
			->willReturn($invitationResponseServer);

		$calendarImpl->handleIMip($vObject);
	}

	public function testHandleImipReply(): void {

		$userAddressess = [
			'mailto:organizer@testing.com',
			'/remote.php/dav/principals/users/organizer/',
		];

		$vObject = $this->vCalendar1a;
		$vObject->add('METHOD', 'REPLY');

		$iTip = new Message();
		$iTip->method = 'REPLY';
		$iTip->sender = $vObject->VEVENT->ATTENDEE->getValue();
		$iTip->recipient = $vObject->VEVENT->ORGANIZER->getValue();
		$iTip->component = 'VEVENT';
		$iTip->uid = $vObject->VEVENT->UID->getValue();
		$iTip->sequence = (int)$vObject->VEVENT->SEQUENCE->getValue() ?? 0;
		$iTip->message = $vObject;

		/** @var CustomPrincipalPlugin|MockObject $authPlugin */
		$authPlugin = $this->createMock(CustomPrincipalPlugin::class);
		$authPlugin->expects(self::once())
			->method('setCurrentPrincipal')
			->with($this->calendar->getPrincipalURI());

		/** @var \OCA\DAV\CalDAV\Schedule\Plugin|MockObject $schedulingPlugin */
		$schedulingPlugin = $this->createMock(Plugin::class);
		$schedulingPlugin->expects(self::once())
			->method('getAddressesForPrincipal')
			->with($this->calendar->getPrincipalURI())
			->willReturn($userAddressess);

		$server = $this->createMock(Server::class);
		$server->expects($this->any())
			->method('getPlugin')
			->willReturnMap([
				['auth', $authPlugin],
				['caldav-schedule', $schedulingPlugin]
			]);
		$server->expects(self::once())
			->method('emit')
			->with('schedule', [$iTip]);

		$invitationResponseServer = $this->createMock(InvitationResponseServer::class);
		$invitationResponseServer->server = $server;
		$invitationResponseServer->expects($this->any())
			->method('getServer')
			->willReturn($server);
			
		$calendarImpl = $this->getMockBuilder(CalendarImpl::class)
			->setConstructorArgs([$this->calendar, $this->calendarInfo, $this->backend])
			->onlyMethods(['getInvitationResponseServer'])
			->getMock();
		$calendarImpl->expects($this->once())
			->method('getInvitationResponseServer')
			->willReturn($invitationResponseServer);

		$calendarImpl->handleIMip($vObject);
	}

	protected function mockExportGenerator(): Generator {
		foreach ($this->mockExportCollection as $entry) {
			yield $entry;
		}
	}

	public function testExport(): void {
		// Arrange
		// construct calendar with a 1 hour event and same start/end time zones
		$vCalendar = new VCalendar();
		/** @var VEvent $vEvent */
		$vEvent = $vCalendar->add('VEVENT', []);
		$vEvent->UID->setValue('96a0e6b1-d886-4a55-a60d-152b31401dcc');
		$vEvent->add('DTSTART', '20240701T080000', ['TZID' => 'America/Toronto']);
		$vEvent->add('DTEND', '20240701T090000', ['TZID' => 'America/Toronto']);
		$vEvent->add('SUMMARY', 'Test Recurrence Event');
		$vEvent->add('ORGANIZER', 'mailto:organizer@testing.com', ['CN' => 'Organizer']);
		$vEvent->add('ATTENDEE', 'mailto:attendee1@testing.com', [
			'CN' => 'Attendee One',
			'CUTYPE' => 'INDIVIDUAL',
			'PARTSTAT' => 'NEEDS-ACTION',
			'ROLE' => 'REQ-PARTICIPANT',
			'RSVP' => 'TRUE'
		]);
		// construct data store return
		$this->mockExportCollection[] = [
			'id' => 1,
			'calendardata' => $vCalendar->serialize()
		];
		$this->backend->expects($this->once())
			->method('exportCalendar')
			->with(1, $this->backend::CALENDAR_TYPE_CALENDAR, null)
			->willReturn($this->mockExportGenerator());

		// Act
		foreach ($this->calendarImpl->export(null) as $entry) {
			$exported[] = $entry;
		}
		
		// Assert
		$this->assertCount(1, $exported, 'Invalid exported items count');
	}

}
