<?php
/**
 * @copyright 2023 Anna Larch <anna.larch@gmx.net>
 *
 * @author Anna Larch <anna.larch@gmx.net>
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
namespace OCA\DAV\Tests\unit\CalDAV\Status;

use OC\Calendar\CalendarQuery;
use OCA\DAV\CalDAV\CalendarImpl;
use OCA\DAV\CalDAV\FreeBusy\FreeBusyGenerator;
use OCA\DAV\CalDAV\InvitationResponse\InvitationResponseServer;
use OCA\DAV\CalDAV\Schedule\Plugin;
use OCA\DAV\CalDAV\Status\Status;
use OCA\DAV\CalDAV\Status\StatusService;
use OCA\DAV\Connector\Sabre\Server;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Calendar\IManager;
use OCP\IL10N;
use OCP\IUser;
use OCP\UserStatus\IUserStatus;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\CalDAV\Xml\Property\ScheduleCalendarTransp;
use Sabre\DAV\Exception\NotAuthenticated;
use Sabre\DAV\Xml\Property\LocalHref;
use Sabre\DAVACL\Exception\NeedPrivileges;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Component\VTimeZone;
use Sabre\VObject\Document;
use Sabre\VObject\Reader;
use Test\TestCase;

class StatusServiceTest extends TestCase {
	private ITimeFactory|MockObject $timeFactory;
	private IManager|MockObject $calendarManager;
	private InvitationResponseServer|MockObject $server;
	private IL10N|MockObject $l10n;
	private FreeBusyGenerator|MockObject $generator;

	protected function setUp(): void {
		parent::setUp();

		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->calendarManager = $this->createMock(IManager::class);
		$this->server = $this->createMock(InvitationResponseServer::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->generator = $this->createMock(FreeBusyGenerator::class);

		$this->service = new StatusService($this->timeFactory,
			$this->calendarManager,
			$this->server,
			$this->l10n,
			$this->generator);
	}

	public function testNoEmail(): void {
		$user = $this->createConfiguredMock(IUser::class, [
			'getUID' => 'admin',
			'getEMailAddress' => null,
		]);
		$availability = '';

		$user->expects(self::once())
			->method('getUID')
			->willReturn('admin');
		$user->expects(self::once())
			->method('getEMailAddress')
			->willReturn(null);
		$this->server->expects(self::never())
			->method('getServer');
		$this->timeFactory->expects(self::never())
			->method('now');
		$this->timeFactory->expects(self::never())
			->method('getDateTime');
		$this->calendarManager->expects(self::never())
			->method('getCalendarsForPrincipal');
		$this->calendarManager->expects(self::never())
			->method('newQuery');
		$this->calendarManager->expects(self::never())
			->method('searchForPrincipal');
		$this->generator->expects(self::never())
			->method('getVCalendar');
		$this->generator->expects(self::never())
			->method('setObjects');
		$this->generator->expects(self::never())
			->method('setTimeRange');
		$this->generator->expects(self::never())
			->method('setTimeZone');
		$this->generator->expects(self::never())
			->method('setVAvailability');
		$this->generator->expects(self::never())
			->method('getResult');

		$status = $this->service->processCalendarAvailability($user, $availability);
		$this->assertNull($status);
	}

	public function testNoAcl(): void {
		$user = $this->createConfiguredMock(IUser::class, [
			'getUID' => 'admin',
			'getEMailAddress' => 'test@test.com',
		]);
		$availability = '';
		$server = $this->createMock(Server::class);
		$schedulingPlugin = $this->createMock(Plugin::class);
		$aclPlugin = $this->createMock(\Sabre\DAVACL\Plugin::class);

		$user->expects(self::once())
			->method('getUID')
			->willReturn('admin');
		$user->expects(self::once())
			->method('getEMailAddress')
			->willReturn('test@test.com');
		$this->server->expects(self::once())
			->method('getServer')
			->willReturn($server);
		$server->expects(self::exactly(2))
			->method('getPlugin')
			->withConsecutive(
				['caldav-schedule'],
				['acl'],
			)->willReturnOnConsecutiveCalls($schedulingPlugin, $aclPlugin);
		$aclPlugin->expects(self::once())
			->method('principalSearch')
			->with([ '{http://sabredav.org/ns}email-address' => 'test@test.com'])
			->willReturn([]);
		$aclPlugin->expects(self::never())
			->method('checkPrivileges');
		$this->timeFactory->expects(self::never())
			->method('now');
		$this->timeFactory->expects(self::never())
			->method('getDateTime');
		$this->calendarManager->expects(self::never())
			->method('getCalendarsForPrincipal');
		$this->calendarManager->expects(self::never())
			->method('newQuery');
		$this->calendarManager->expects(self::never())
			->method('searchForPrincipal');
		$this->generator->expects(self::never())
			->method('getVCalendar');
		$this->generator->expects(self::never())
			->method('setObjects');
		$this->generator->expects(self::never())
			->method('setTimeRange');
		$this->generator->expects(self::never())
			->method('setTimeZone');
		$this->generator->expects(self::never())
			->method('setVAvailability');
		$this->generator->expects(self::never())
			->method('getResult');

		$status = $this->service->processCalendarAvailability($user, $availability);
		$this->assertNull($status);
	}

	public function testNoInbox(): void {
		$user = $this->createConfiguredMock(IUser::class, [
			'getUID' => 'admin',
			'getEMailAddress' => 'test@test.com',
		]);
		$availability = '';
		$server = $this->createMock(Server::class);
		$schedulingPlugin = $this->createMock(Plugin::class);
		$aclPlugin = $this->createMock(\Sabre\DAVACL\Plugin::class);

		$user->expects(self::once())
			->method('getUID')
			->willReturn('admin');
		$user->expects(self::once())
			->method('getEMailAddress')
			->willReturn('test@test.com');
		$this->server->expects(self::once())
			->method('getServer')
			->willReturn($server);
		$server->expects(self::exactly(2))
			->method('getPlugin')
			->withConsecutive(
				['caldav-schedule'],
				['acl'],
			)->willReturnOnConsecutiveCalls($schedulingPlugin, $aclPlugin);
		$aclPlugin->expects(self::once())
			->method('principalSearch')
			->with([ '{http://sabredav.org/ns}email-address' => 'test@test.com'])
			->willReturn([]);
		$aclPlugin->expects(self::never())
			->method('checkPrivileges');
		$this->timeFactory->expects(self::never())
			->method('now');
		$this->timeFactory->expects(self::never())
			->method('getDateTime');
		$this->calendarManager->expects(self::never())
			->method('getCalendarsForPrincipal');
		$this->calendarManager->expects(self::never())
			->method('newQuery');
		$this->calendarManager->expects(self::never())
			->method('searchForPrincipal');
		$this->generator->expects(self::never())
			->method('getVCalendar');
		$this->generator->expects(self::never())
			->method('setObjects');
		$this->generator->expects(self::never())
			->method('setTimeRange');
		$this->generator->expects(self::never())
			->method('setTimeZone');
		$this->generator->expects(self::never())
			->method('setVAvailability');
		$this->generator->expects(self::never())
			->method('getResult');

		$status = $this->service->processCalendarAvailability($user, $availability);
		$this->assertNull($status);
	}

	public function testNoPrivilegesAcl(): void {
		$user = $this->createConfiguredMock(IUser::class, [
			'getUID' => 'admin',
			'getEMailAddress' => 'test@test.com',
		]);
		$availability = '';
		$server = $this->createMock(Server::class);
		$schedulingPlugin = $this->createMock(Plugin::class);
		$aclPlugin = $this->createMock(\Sabre\DAVACL\Plugin::class);
		$principal = 'principals/users/admin';
		$calendarHome = $this->createMock(LocalHref::class);
		$acl = [[200 => ['{urn:ietf:params:xml:ns:caldav}schedule-inbox-URL' => $calendarHome]]];

		$user->expects(self::once())
			->method('getUID')
			->willReturn('admin');
		$user->expects(self::once())
			->method('getEMailAddress')
			->willReturn('test@test.com');
		$this->server->expects(self::once())
			->method('getServer')
			->willReturn($server);
		$server->expects(self::exactly(2))
			->method('getPlugin')
			->withConsecutive(
				['caldav-schedule'],
				['acl'],
			)->willReturnOnConsecutiveCalls($schedulingPlugin, $aclPlugin);
		$aclPlugin->expects(self::once())
			->method('principalSearch')
			->with([ '{http://sabredav.org/ns}email-address' => 'test@test.com'])
			->willReturn($acl);
		$calendarHome->expects(self::once())
			->method('getHref')
			->willReturn('calendars/admin/inbox/');
		$aclPlugin->expects(self::once())
			->method('checkPrivileges')
			->willThrowException(new NeedPrivileges($principal, ['{DAV:}all']));
		$this->timeFactory->expects(self::never())
			->method('now');
		$this->timeFactory->expects(self::never())
			->method('getDateTime');
		$this->calendarManager->expects(self::never())
			->method('getCalendarsForPrincipal');
		$this->calendarManager->expects(self::never())
			->method('newQuery');
		$this->calendarManager->expects(self::never())
			->method('searchForPrincipal');
		$this->generator->expects(self::never())
			->method('getVCalendar');
		$this->generator->expects(self::never())
			->method('setObjects');
		$this->generator->expects(self::never())
			->method('setTimeRange');
		$this->generator->expects(self::never())
			->method('setTimeZone');
		$this->generator->expects(self::never())
			->method('setVAvailability');
		$this->generator->expects(self::never())
			->method('getResult');

		$status = $this->service->processCalendarAvailability($user, $availability);
		$this->assertNull($status);
	}

	public function testNotAuthenticated(): void {
		$user = $this->createConfiguredMock(IUser::class, [
			'getUID' => 'admin',
			'getEMailAddress' => 'test@test.com',
		]);
		$availability = '';
		$server = $this->createMock(Server::class);
		$schedulingPlugin = $this->createMock(Plugin::class);
		$aclPlugin = $this->createMock(\Sabre\DAVACL\Plugin::class);
		$calendarHome = $this->createMock(LocalHref::class);
		$acl = [[200 => ['{urn:ietf:params:xml:ns:caldav}schedule-inbox-URL' => $calendarHome]]];

		$user->expects(self::once())
			->method('getUID')
			->willReturn('admin');
		$user->expects(self::once())
			->method('getEMailAddress')
			->willReturn('test@test.com');
		$this->server->expects(self::once())
			->method('getServer')
			->willReturn($server);
		$server->expects(self::exactly(2))
			->method('getPlugin')
			->withConsecutive(
				['caldav-schedule'],
				['acl'],
			)->willReturnOnConsecutiveCalls($schedulingPlugin, $aclPlugin);
		$aclPlugin->expects(self::once())
			->method('principalSearch')
			->with([ '{http://sabredav.org/ns}email-address' => 'test@test.com'])
			->willReturn($acl);
		$calendarHome->expects(self::once())
			->method('getHref')
			->willReturn('calendars/admin/inbox/');
		$aclPlugin->expects(self::once())
			->method('checkPrivileges')
			->willThrowException(new NotAuthenticated());
		$this->timeFactory->expects(self::never())
			->method('now');
		$this->timeFactory->expects(self::never())
			->method('getDateTime');
		$this->calendarManager->expects(self::never())
			->method('getCalendarsForPrincipal');
		$this->calendarManager->expects(self::never())
			->method('newQuery');
		$this->calendarManager->expects(self::never())
			->method('searchForPrincipal');
		$this->generator->expects(self::never())
			->method('getVCalendar');
		$this->generator->expects(self::never())
			->method('setObjects');
		$this->generator->expects(self::never())
			->method('setTimeRange');
		$this->generator->expects(self::never())
			->method('setTimeZone');
		$this->generator->expects(self::never())
			->method('setVAvailability');
		$this->generator->expects(self::never())
			->method('getResult');

		$status = $this->service->processCalendarAvailability($user, $availability);
		$this->assertNull($status);
	}

	public function testNoCalendars(): void {
		$user = $this->createConfiguredMock(IUser::class, [
			'getUID' => 'admin',
			'getEMailAddress' => 'test@test.com',
		]);
		$availability = '';
		$server = $this->createMock(Server::class);
		$schedulingPlugin = $this->createMock(Plugin::class);
		$aclPlugin = $this->createMock(\Sabre\DAVACL\Plugin::class);
		$calendarHome = $this->createMock(LocalHref::class);
		$acl = [[200 => ['{urn:ietf:params:xml:ns:caldav}schedule-inbox-URL' => $calendarHome]]];
		$now = new \DateTimeImmutable('1970-1-1', new \DateTimeZone('UTC'));
		$principal = 'principals/users/admin';

		$user->expects(self::once())
			->method('getUID')
			->willReturn('admin');
		$user->expects(self::once())
			->method('getEMailAddress')
			->willReturn('test@test.com');
		$this->server->expects(self::once())
			->method('getServer')
			->willReturn($server);
		$server->expects(self::exactly(2))
			->method('getPlugin')
			->withConsecutive(
				['caldav-schedule'],
				['acl'],
			)->willReturnOnConsecutiveCalls($schedulingPlugin, $aclPlugin);
		$aclPlugin->expects(self::once())
			->method('principalSearch')
			->with([ '{http://sabredav.org/ns}email-address' => 'test@test.com'])
			->willReturn($acl);
		$calendarHome->expects(self::once())
			->method('getHref')
			->willReturn('calendars/admin/inbox/');
		$aclPlugin->expects(self::once())
			->method('checkPrivileges')
			->willReturn(true);
		$this->timeFactory->expects(self::once())
			->method('now')
			->willReturn($now);
		$this->calendarManager->expects(self::once())
			->method('getCalendarsForPrincipal')
			->with($principal)
			->willReturn([]);
		$this->timeFactory->expects(self::never())
			->method('getDateTime');
		$this->calendarManager->expects(self::never())
			->method('newQuery');
		$this->calendarManager->expects(self::never())
			->method('searchForPrincipal');
		$this->generator->expects(self::never())
			->method('getVCalendar');
		$this->generator->expects(self::never())
			->method('setObjects');
		$this->generator->expects(self::never())
			->method('setTimeRange');
		$this->generator->expects(self::never())
			->method('setTimeZone');
		$this->generator->expects(self::never())
			->method('setVAvailability');
		$this->generator->expects(self::never())
			->method('getResult');

		$status = $this->service->processCalendarAvailability($user, $availability);
		$this->assertNull($status);
	}

	public function testEmptyAvailabilityAndNoSearchCalendars(): void {
		$user = $this->createConfiguredMock(IUser::class, [
			'getUID' => 'admin',
			'getEMailAddress' => 'test@test.com',
		]);
		$availability = '';
		$server = $this->createMock(Server::class);
		$schedulingPlugin = $this->createMock(Plugin::class);
		$aclPlugin = $this->createMock(\Sabre\DAVACL\Plugin::class);
		$calendarHome = $this->createMock(LocalHref::class);
		$now = new \DateTimeImmutable('1970-1-1', new \DateTimeZone('UTC'));
		$inTenMinutes = new \DateTime('1970-1-1 01:00');
		$acl = [[200 => ['{urn:ietf:params:xml:ns:caldav}schedule-inbox-URL' => $calendarHome]]];
		$principal = 'principals/users/admin';
		$calendar = $this->createMock(CalendarImpl::class);
		$query = $this->createMock(CalendarQuery::class);

		$user->expects(self::once())
			->method('getUID')
			->willReturn('admin');
		$user->expects(self::once())
			->method('getEMailAddress')
			->willReturn('test@test.com');
		$this->server->expects(self::once())
			->method('getServer')
			->willReturn($server);
		$server->expects(self::exactly(2))
			->method('getPlugin')
			->withConsecutive(
				['caldav-schedule'],
				['acl'],
			)->willReturnOnConsecutiveCalls($schedulingPlugin, $aclPlugin);
		$aclPlugin->expects(self::once())
			->method('principalSearch')
			->with([ '{http://sabredav.org/ns}email-address' => 'test@test.com'])
			->willReturn($acl);
		$calendarHome->expects(self::once())
			->method('getHref')
			->willReturn('calendars/admin/inbox/');
		$aclPlugin->expects(self::once())
			->method('checkPrivileges')
			->willReturn(true);
		$this->timeFactory->expects(self::once())
			->method('now')
			->willReturn($now);
		$this->calendarManager->expects(self::once())
			->method('getCalendarsForPrincipal')
			->with($principal)
			->willReturn([$calendar]);
		$this->calendarManager->expects(self::once())
			->method('newQuery')
			->with($principal)
			->willReturn($query);
		$calendar->expects(self::once())
			->method('getSchedulingTransparency')
			->willReturn(new ScheduleCalendarTransp('transparent'));
		$this->timeFactory->expects(self::once())
			->method('getDateTime')
			->with('+10 minutes')
			->willReturn($inTenMinutes);
		$this->calendarManager->expects(self::never())
			->method('searchForPrincipal');
		$this->generator->expects(self::never())
			->method('getVCalendar');
		$this->generator->expects(self::never())
			->method('setObjects');
		$this->generator->expects(self::never())
			->method('setTimeRange');
		$this->generator->expects(self::never())
			->method('setTimeZone');
		$this->generator->expects(self::never())
			->method('setVAvailability');
		$this->generator->expects(self::never())
			->method('getResult');

		$status = $this->service->processCalendarAvailability($user, $availability);
		$this->assertNull($status);
	}

	public function testEmptyAvailabilityAndSearchCalendarsNoResults(): void {
		$user = $this->createConfiguredMock(IUser::class, [
			'getUID' => 'admin',
			'getEMailAddress' => 'test@test.com',
		]);
		$availability = '';
		$server = $this->createMock(Server::class);
		$schedulingPlugin = $this->createMock(Plugin::class);
		$aclPlugin = $this->createMock(\Sabre\DAVACL\Plugin::class);
		$calendarHome = $this->createMock(LocalHref::class);
		$acl = [[200 => ['{urn:ietf:params:xml:ns:caldav}schedule-inbox-URL' => $calendarHome]]];
		$now = new \DateTimeImmutable('1970-1-1 00:00', new \DateTimeZone('UTC'));
		$inTenMinutes = new \DateTime('1970-1-1 01:00');
		$immutableInTenMinutes = \DateTimeImmutable::createFromMutable($inTenMinutes);
		$principal = 'principals/users/admin';
		$query = $this->createMock(CalendarQuery::class);
		$timezone = new \DateTimeZone('UTC');
		$timezoneObj = $this->createMock(VTimeZone::class);
		$calendar = $this->createMock(CalendarImpl::class);

		$user->expects(self::once())
			->method('getUID')
			->willReturn('admin');
		$user->expects(self::once())
			->method('getEMailAddress')
			->willReturn('test@test.com');
		$this->server->expects(self::once())
			->method('getServer')
			->willReturn($server);
		$server->expects(self::exactly(2))
			->method('getPlugin')
			->withConsecutive(
				['caldav-schedule'],
				['acl'],
			)->willReturnOnConsecutiveCalls($schedulingPlugin, $aclPlugin);
		$aclPlugin->expects(self::once())
			->method('principalSearch')
			->with(['{http://sabredav.org/ns}email-address' => 'test@test.com'])
			->willReturn($acl);
		$calendarHome->expects(self::once())
			->method('getHref')
			->willReturn('calendars/admin/inbox/');
		$aclPlugin->expects(self::once())
			->method('checkPrivileges')
			->willReturn(true);
		$this->timeFactory->expects(self::once())
			->method('now')
			->willReturn($now);
		$this->calendarManager->expects(self::once())
			->method('getCalendarsForPrincipal')
			->with($principal)
			->willReturn([$calendar]);
		$this->calendarManager->expects(self::once())
			->method('newQuery')
			->with($principal)
			->willReturn($query);
		$calendar->expects(self::once())
			->method('getSchedulingTransparency')
			->willReturn(new ScheduleCalendarTransp('opaque'));
		$calendar->expects(self::once())
			->method('getSchedulingTimezone')
			->willReturn($timezoneObj);
		$timezoneObj->expects(self::once())
			->method('getTimeZone')
			->willReturn($timezone);
		$calendar->expects(self::once())
			->method('getUri');
		$query->expects(self::once())
			->method('addSearchCalendar');
		$query->expects(self::once())
			->method('getCalendarUris')
			->willReturn([$calendar]);
		$this->timeFactory->expects(self::once())
			->method('getDateTime')
			->with('+10 minutes')
			->willReturn($inTenMinutes);
		$query->expects(self::once())
			->method('setTimerangeStart')
			->with($now);
		$query->expects(self::once())
			->method('setTimerangeEnd')
			->with($immutableInTenMinutes);
		$this->calendarManager->expects(self::once())
			->method('searchForPrincipal')
			->with($query)
			->willReturn([]);
		$this->generator->expects(self::never())
			->method('getVCalendar');
		$this->generator->expects(self::never())
			->method('setObjects');
		$this->generator->expects(self::never())
			->method('setTimeRange');
		$this->generator->expects(self::never())
			->method('setTimeZone');
		$this->generator->expects(self::never())
			->method('setVAvailability');
		$this->generator->expects(self::never())
			->method('getResult');

		$status = $this->service->processCalendarAvailability($user, $availability);
		$this->assertNull($status);
	}

	public function testAvailabilityAndSearchCalendarsNoResults(): void {
		$user = $this->createConfiguredMock(IUser::class, [
			'getUID' => 'admin',
			'getEMailAddress' => 'test@test.com',
		]);
		$server = $this->createMock(Server::class);
		$schedulingPlugin = $this->createMock(Plugin::class);
		$aclPlugin = $this->createMock(\Sabre\DAVACL\Plugin::class);
		$calendarHome = $this->createMock(LocalHref::class);
		$acl = [[200 => ['{urn:ietf:params:xml:ns:caldav}schedule-inbox-URL' => $calendarHome]]];
		$now = new \DateTimeImmutable('1970-1-1 00:00', new \DateTimeZone('UTC'));
		$inTenMinutes = new \DateTime('1970-1-1 01:00');
		$immutableInTenMinutes = \DateTimeImmutable::createFromMutable($inTenMinutes);
		$principal = 'principals/users/admin';
		$query = $this->createMock(CalendarQuery::class);
		$timezone = new \DateTimeZone('UTC');
		$timezoneObj = $this->createMock(VTimeZone::class);
		$calendar = $this->createMock(CalendarImpl::class);
		$vCalendar = $this->createMock(VCalendar::class);
		$availability = $this->getVAvailability();
		$result = Reader::read('BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Sabre//Sabre VObject 4.5.3//EN
		CALSCALE:GREGORIAN
METHOD:REQUEST
END:VCALENDAR');

		$user->expects(self::once())
			->method('getUID')
			->willReturn('admin');
		$user->expects(self::once())
			->method('getEMailAddress')
			->willReturn('test@test.com');
		$this->server->expects(self::once())
			->method('getServer')
			->willReturn($server);
		$server->expects(self::exactly(2))
			->method('getPlugin')
			->withConsecutive(
				['caldav-schedule'],
				['acl'],
			)->willReturnOnConsecutiveCalls($schedulingPlugin, $aclPlugin);
		$aclPlugin->expects(self::once())
			->method('principalSearch')
			->with(['{http://sabredav.org/ns}email-address' => 'test@test.com'])
			->willReturn($acl);
		$calendarHome->expects(self::once())
			->method('getHref')
			->willReturn('calendars/admin/inbox/');
		$aclPlugin->expects(self::once())
			->method('checkPrivileges')
			->willReturn(true);
		$this->timeFactory->expects(self::once())
			->method('now')
			->willReturn($now);
		$this->calendarManager->expects(self::once())
			->method('getCalendarsForPrincipal')
			->with($principal)
			->willReturn([$calendar]);
		$this->calendarManager->expects(self::once())
			->method('newQuery')
			->with($principal)
			->willReturn($query);
		$calendar->expects(self::once())
			->method('getSchedulingTransparency')
			->willReturn(new ScheduleCalendarTransp('opaque'));
		$calendar->expects(self::once())
			->method('getSchedulingTimezone')
			->willReturn($timezoneObj);
		$timezoneObj->expects(self::once())
			->method('getTimeZone')
			->willReturn($timezone);
		$calendar->expects(self::once())
			->method('getUri');
		$query->expects(self::once())
			->method('addSearchCalendar');
		$query->expects(self::once())
			->method('getCalendarUris')
			->willReturn([$calendar]);
		$this->timeFactory->expects(self::once())
			->method('getDateTime')
			->with('+10 minutes')
			->willReturn($inTenMinutes);
		$query->expects(self::once())
			->method('setTimerangeStart')
			->with($now);
		$query->expects(self::once())
			->method('setTimerangeEnd')
			->with($immutableInTenMinutes);
		$this->calendarManager->expects(self::once())
			->method('searchForPrincipal')
			->with($query)
			->willReturn([]);
		$this->generator->expects(self::once())
			->method('getVCalendar')
			->willReturn($vCalendar);
		$vCalendar->expects(self::never())
			->method('add');
		$this->generator->expects(self::once())
			->method('setObjects')
			->with($vCalendar);
		$this->generator->expects(self::once())
			->method('setTimeRange')
			->with($now, $immutableInTenMinutes);
		$this->generator->expects(self::once())
			->method('setTimeZone')
			->with($timezone);
		$this->generator->expects(self::once())
			->method('setVAvailability')
			->with($availability);
		$this->generator->expects(self::once())
			->method('getResult')
			->willReturn($result);

		$status = $this->service->processCalendarAvailability($user, $availability->serialize());
		$this->assertNull($status);
	}

	public function testAvailabilityAndSearchCalendarsStatusOnline(): void {
		$user = $this->createConfiguredMock(IUser::class, [
			'getUID' => 'admin',
			'getEMailAddress' => 'test@test.com',
		]);
		$server = $this->createMock(Server::class);
		$schedulingPlugin = $this->createMock(Plugin::class);
		$aclPlugin = $this->createMock(\Sabre\DAVACL\Plugin::class);
		$calendarHome = $this->createMock(LocalHref::class);
		$acl = [[200 => ['{urn:ietf:params:xml:ns:caldav}schedule-inbox-URL' => $calendarHome]]];
		$now = new \DateTimeImmutable('1970-1-1 00:00', new \DateTimeZone('UTC'));
		$inTenMinutes = new \DateTime('1970-1-1 01:00');
		$immutableInTenMinutes = \DateTimeImmutable::createFromMutable($inTenMinutes);
		$principal = 'principals/users/admin';
		$query = $this->createMock(CalendarQuery::class);
		$timezone = new \DateTimeZone('UTC');
		$timezoneObj = $this->createMock(VTimeZone::class);
		$calendar = $this->createMock(CalendarImpl::class);
		$vCalendar = $this->createMock(VCalendar::class);
		$availability = $this->getVAvailability();
		$result = Reader::read('BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Sabre//Sabre VObject 4.5.3//EN
		CALSCALE:GREGORIAN
METHOD:REQUEST
BEGIN:VFREEBUSY
DTSTART:19700101T000000Z
DTEND:19700101T003600Z
DTSTAMP:19700101T000200Z
END:VFREEBUSY
END:VCALENDAR');

		$user->expects(self::once())
			->method('getUID')
			->willReturn('admin');
		$user->expects(self::once())
			->method('getEMailAddress')
			->willReturn('test@test.com');
		$this->server->expects(self::once())
			->method('getServer')
			->willReturn($server);
		$server->expects(self::exactly(2))
			->method('getPlugin')
			->withConsecutive(
				['caldav-schedule'],
				['acl'],
			)->willReturnOnConsecutiveCalls($schedulingPlugin, $aclPlugin);
		$aclPlugin->expects(self::once())
			->method('principalSearch')
			->with(['{http://sabredav.org/ns}email-address' => 'test@test.com'])
			->willReturn($acl);
		$calendarHome->expects(self::once())
			->method('getHref')
			->willReturn('calendars/admin/inbox/');
		$aclPlugin->expects(self::once())
			->method('checkPrivileges')
			->willReturn(true);
		$this->timeFactory->expects(self::once())
			->method('now')
			->willReturn($now);
		$this->calendarManager->expects(self::once())
			->method('getCalendarsForPrincipal')
			->with($principal)
			->willReturn([$calendar]);
		$this->calendarManager->expects(self::once())
			->method('newQuery')
			->with($principal)
			->willReturn($query);
		$calendar->expects(self::once())
			->method('getSchedulingTransparency')
			->willReturn(new ScheduleCalendarTransp('opaque'));
		$calendar->expects(self::once())
			->method('getSchedulingTimezone')
			->willReturn($timezoneObj);
		$timezoneObj->expects(self::once())
			->method('getTimeZone')
			->willReturn($timezone);
		$calendar->expects(self::once())
			->method('getUri');
		$query->expects(self::once())
			->method('addSearchCalendar');
		$query->expects(self::once())
			->method('getCalendarUris')
			->willReturn([$calendar]);
		$this->timeFactory->expects(self::once())
			->method('getDateTime')
			->with('+10 minutes')
			->willReturn($inTenMinutes);
		$query->expects(self::once())
			->method('setTimerangeStart')
			->with($now);
		$query->expects(self::once())
			->method('setTimerangeEnd')
			->with($immutableInTenMinutes);
		$this->calendarManager->expects(self::once())
			->method('searchForPrincipal')
			->with($query)
			->willReturn([]);
		$this->generator->expects(self::once())
			->method('getVCalendar')
			->willReturn($vCalendar);
		$vCalendar->expects(self::never())
			->method('add');
		$this->generator->expects(self::once())
			->method('setObjects')
			->with($vCalendar);
		$this->generator->expects(self::once())
			->method('setTimeRange')
			->with($now, $immutableInTenMinutes);
		$this->generator->expects(self::once())
			->method('setTimeZone')
			->with($timezone);
		$this->generator->expects(self::once())
			->method('setVAvailability')
			->with($availability);
		$this->generator->expects(self::once())
			->method('getResult')
			->willReturn($result);

		$status = $this->service->processCalendarAvailability($user, $availability->serialize());
		$this->assertEquals(new Status(IUserStatus::ONLINE), $status);
	}

	public function testAvailabilityAndSearchCalendarsStatusBusyNoFBType(): void {
		$user = $this->createConfiguredMock(IUser::class, [
			'getUID' => 'admin',
			'getEMailAddress' => 'test@test.com',
		]);
		$server = $this->createMock(Server::class);
		$schedulingPlugin = $this->createMock(Plugin::class);
		$aclPlugin = $this->createMock(\Sabre\DAVACL\Plugin::class);
		$calendarHome = $this->createMock(LocalHref::class);
		$acl = [[200 => ['{urn:ietf:params:xml:ns:caldav}schedule-inbox-URL' => $calendarHome]]];
		$now = new \DateTimeImmutable('1970-1-1 00:00', new \DateTimeZone('UTC'));
		$inTenMinutes = new \DateTime('1970-1-1 01:00');
		$immutableInTenMinutes = \DateTimeImmutable::createFromMutable($inTenMinutes);
		$principal = 'principals/users/admin';
		$query = $this->createMock(CalendarQuery::class);
		$timezone = new \DateTimeZone('UTC');
		$timezoneObj = $this->createMock(VTimeZone::class);
		$calendar = $this->createMock(CalendarImpl::class);
		$vCalendar = $this->createMock(VCalendar::class);
		$availability = $this->getVAvailability();
		$result = Reader::read('BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Sabre//Sabre VObject 4.5.3//EN
		CALSCALE:GREGORIAN
METHOD:REQUEST
BEGIN:VFREEBUSY
DTSTART:19700101T000000Z
DTEND:19700101T003600Z
DTSTAMP:19700101T000200Z
FREEBUSY:19700101T000000Z/19700101T003600Z
END:VFREEBUSY
END:VCALENDAR');

		$user->expects(self::once())
			->method('getUID')
			->willReturn('admin');
		$user->expects(self::once())
			->method('getEMailAddress')
			->willReturn('test@test.com');
		$this->server->expects(self::once())
			->method('getServer')
			->willReturn($server);
		$server->expects(self::exactly(2))
			->method('getPlugin')
			->withConsecutive(
				['caldav-schedule'],
				['acl'],
			)->willReturnOnConsecutiveCalls($schedulingPlugin, $aclPlugin);
		$aclPlugin->expects(self::once())
			->method('principalSearch')
			->with(['{http://sabredav.org/ns}email-address' => 'test@test.com'])
			->willReturn($acl);
		$calendarHome->expects(self::once())
			->method('getHref')
			->willReturn('calendars/admin/inbox/');
		$aclPlugin->expects(self::once())
			->method('checkPrivileges')
			->willReturn(true);
		$this->timeFactory->expects(self::once())
			->method('now')
			->willReturn($now);
		$this->calendarManager->expects(self::once())
			->method('getCalendarsForPrincipal')
			->with($principal)
			->willReturn([$calendar]);
		$this->calendarManager->expects(self::once())
			->method('newQuery')
			->with($principal)
			->willReturn($query);
		$calendar->expects(self::once())
			->method('getSchedulingTransparency')
			->willReturn(new ScheduleCalendarTransp('opaque'));
		$calendar->expects(self::once())
			->method('getSchedulingTimezone')
			->willReturn($timezoneObj);
		$timezoneObj->expects(self::once())
			->method('getTimeZone')
			->willReturn($timezone);
		$calendar->expects(self::once())
			->method('getUri');
		$query->expects(self::once())
			->method('addSearchCalendar');
		$query->expects(self::once())
			->method('getCalendarUris')
			->willReturn([$calendar]);
		$this->timeFactory->expects(self::once())
			->method('getDateTime')
			->with('+10 minutes')
			->willReturn($inTenMinutes);
		$query->expects(self::once())
			->method('setTimerangeStart')
			->with($now);
		$query->expects(self::once())
			->method('setTimerangeEnd')
			->with($immutableInTenMinutes);
		$this->calendarManager->expects(self::once())
			->method('searchForPrincipal')
			->with($query)
			->willReturn([]);
		$this->generator->expects(self::once())
			->method('getVCalendar')
			->willReturn($vCalendar);
		$vCalendar->expects(self::never())
			->method('add');
		$this->generator->expects(self::once())
			->method('setObjects')
			->with($vCalendar);
		$this->generator->expects(self::once())
			->method('setTimeRange')
			->with($now, $immutableInTenMinutes);
		$this->generator->expects(self::once())
			->method('setTimeZone')
			->with($timezone);
		$this->generator->expects(self::once())
			->method('setVAvailability')
			->with($availability);
		$this->generator->expects(self::once())
			->method('getResult')
			->willReturn($result);

		$status = $this->service->processCalendarAvailability($user, $availability->serialize());
		$this->assertEquals(new Status(IUserStatus::BUSY, IUserStatus::MESSAGE_CALENDAR_BUSY), $status);
	}

	public function testAvailabilityAndSearchCalendarsStatusBusy(): void {
		$user = $this->createConfiguredMock(IUser::class, [
			'getUID' => 'admin',
			'getEMailAddress' => 'test@test.com',
		]);
		$server = $this->createMock(Server::class);
		$schedulingPlugin = $this->createMock(Plugin::class);
		$aclPlugin = $this->createMock(\Sabre\DAVACL\Plugin::class);
		$calendarHome = $this->createMock(LocalHref::class);
		$acl = [[200 => ['{urn:ietf:params:xml:ns:caldav}schedule-inbox-URL' => $calendarHome]]];
		$now = new \DateTimeImmutable('1970-1-1 00:00', new \DateTimeZone('UTC'));
		$inTenMinutes = new \DateTime('1970-1-1 01:00');
		$immutableInTenMinutes = \DateTimeImmutable::createFromMutable($inTenMinutes);
		$principal = 'principals/users/admin';
		$query = $this->createMock(CalendarQuery::class);
		$timezone = new \DateTimeZone('UTC');
		$timezoneObj = $this->createMock(VTimeZone::class);
		$calendar = $this->createMock(CalendarImpl::class);
		$vCalendar = $this->createMock(VCalendar::class);
		$availability = $this->getVAvailability();
		$result = Reader::read('BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Sabre//Sabre VObject 4.5.3//EN
		CALSCALE:GREGORIAN
METHOD:REQUEST
BEGIN:VFREEBUSY
DTSTART:19700101T000000Z
DTEND:19700101T003600Z
DTSTAMP:19700101T000200Z
FREEBUSY;FBTYPE=BUSY:19700101T000000Z/19700101T003600Z
END:VFREEBUSY
END:VCALENDAR');

		$user->expects(self::once())
			->method('getUID')
			->willReturn('admin');
		$user->expects(self::once())
			->method('getEMailAddress')
			->willReturn('test@test.com');
		$this->server->expects(self::once())
			->method('getServer')
			->willReturn($server);
		$server->expects(self::exactly(2))
			->method('getPlugin')
			->withConsecutive(
				['caldav-schedule'],
				['acl'],
			)->willReturnOnConsecutiveCalls($schedulingPlugin, $aclPlugin);
		$aclPlugin->expects(self::once())
			->method('principalSearch')
			->with(['{http://sabredav.org/ns}email-address' => 'test@test.com'])
			->willReturn($acl);
		$calendarHome->expects(self::once())
			->method('getHref')
			->willReturn('calendars/admin/inbox/');
		$aclPlugin->expects(self::once())
			->method('checkPrivileges')
			->willReturn(true);
		$this->timeFactory->expects(self::once())
			->method('now')
			->willReturn($now);
		$this->calendarManager->expects(self::once())
			->method('getCalendarsForPrincipal')
			->with($principal)
			->willReturn([$calendar]);
		$this->calendarManager->expects(self::once())
			->method('newQuery')
			->with($principal)
			->willReturn($query);
		$calendar->expects(self::once())
			->method('getSchedulingTransparency')
			->willReturn(new ScheduleCalendarTransp('opaque'));
		$calendar->expects(self::once())
			->method('getSchedulingTimezone')
			->willReturn($timezoneObj);
		$timezoneObj->expects(self::once())
			->method('getTimeZone')
			->willReturn($timezone);
		$calendar->expects(self::once())
			->method('getUri');
		$query->expects(self::once())
			->method('addSearchCalendar');
		$query->expects(self::once())
			->method('getCalendarUris')
			->willReturn([$calendar]);
		$this->timeFactory->expects(self::once())
			->method('getDateTime')
			->with('+10 minutes')
			->willReturn($inTenMinutes);
		$query->expects(self::once())
			->method('setTimerangeStart')
			->with($now);
		$query->expects(self::once())
			->method('setTimerangeEnd')
			->with($immutableInTenMinutes);
		$this->calendarManager->expects(self::once())
			->method('searchForPrincipal')
			->with($query)
			->willReturn([]);
		$this->generator->expects(self::once())
			->method('getVCalendar')
			->willReturn($vCalendar);
		$vCalendar->expects(self::never())
			->method('add');
		$this->generator->expects(self::once())
			->method('setObjects')
			->with($vCalendar);
		$this->generator->expects(self::once())
			->method('setTimeRange')
			->with($now, $immutableInTenMinutes);
		$this->generator->expects(self::once())
			->method('setTimeZone')
			->with($timezone);
		$this->generator->expects(self::once())
			->method('setVAvailability')
			->with($availability);
		$this->generator->expects(self::once())
			->method('getResult')
			->willReturn($result);
		$this->l10n->expects(self::once())
			->method('t')
			->with('In a meeting')
			->willReturn('In a meeting');

		$status = $this->service->processCalendarAvailability($user, $availability->serialize());
		$this->assertEquals(new Status(IUserStatus::BUSY, IUserStatus::MESSAGE_CALENDAR_BUSY, 'In a meeting'), $status);
	}

	public function testAvailabilityAndSearchCalendarsStatusBusyUnavailable(): void {
		$user = $this->createConfiguredMock(IUser::class, [
			'getUID' => 'admin',
			'getEMailAddress' => 'test@test.com',
		]);
		$server = $this->createMock(Server::class);
		$schedulingPlugin = $this->createMock(Plugin::class);
		$aclPlugin = $this->createMock(\Sabre\DAVACL\Plugin::class);
		$calendarHome = $this->createMock(LocalHref::class);
		$acl = [[200 => ['{urn:ietf:params:xml:ns:caldav}schedule-inbox-URL' => $calendarHome]]];
		$now = new \DateTimeImmutable('1970-1-1 00:00', new \DateTimeZone('UTC'));
		$inTenMinutes = new \DateTime('1970-1-1 01:00');
		$immutableInTenMinutes = \DateTimeImmutable::createFromMutable($inTenMinutes);
		$principal = 'principals/users/admin';
		$query = $this->createMock(CalendarQuery::class);
		$timezone = new \DateTimeZone('UTC');
		$timezoneObj = $this->createMock(VTimeZone::class);
		$calendar = $this->createMock(CalendarImpl::class);
		$vCalendar = $this->createMock(VCalendar::class);
		$availability = $this->getVAvailability();
		$result = Reader::read('BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Sabre//Sabre VObject 4.5.3//EN
		CALSCALE:GREGORIAN
METHOD:REQUEST
BEGIN:VFREEBUSY
DTSTART:19700101T000000Z
DTEND:19700101T003600Z
DTSTAMP:19700101T000200Z
FREEBUSY;FBTYPE=BUSY-UNAVAILABLE:19700101T000000Z/19700101T003600Z
END:VFREEBUSY
END:VCALENDAR');

		$user->expects(self::once())
			->method('getUID')
			->willReturn('admin');
		$user->expects(self::once())
			->method('getEMailAddress')
			->willReturn('test@test.com');
		$this->server->expects(self::once())
			->method('getServer')
			->willReturn($server);
		$server->expects(self::exactly(2))
			->method('getPlugin')
			->withConsecutive(
				['caldav-schedule'],
				['acl'],
			)->willReturnOnConsecutiveCalls($schedulingPlugin, $aclPlugin);
		$aclPlugin->expects(self::once())
			->method('principalSearch')
			->with(['{http://sabredav.org/ns}email-address' => 'test@test.com'])
			->willReturn($acl);
		$calendarHome->expects(self::once())
			->method('getHref')
			->willReturn('calendars/admin/inbox/');
		$aclPlugin->expects(self::once())
			->method('checkPrivileges')
			->willReturn(true);
		$this->timeFactory->expects(self::once())
			->method('now')
			->willReturn($now);
		$this->calendarManager->expects(self::once())
			->method('getCalendarsForPrincipal')
			->with($principal)
			->willReturn([$calendar]);
		$this->calendarManager->expects(self::once())
			->method('newQuery')
			->with($principal)
			->willReturn($query);
		$calendar->expects(self::once())
			->method('getSchedulingTransparency')
			->willReturn(new ScheduleCalendarTransp('opaque'));
		$calendar->expects(self::once())
			->method('getSchedulingTimezone')
			->willReturn($timezoneObj);
		$timezoneObj->expects(self::once())
			->method('getTimeZone')
			->willReturn($timezone);
		$calendar->expects(self::once())
			->method('getUri');
		$query->expects(self::once())
			->method('addSearchCalendar');
		$query->expects(self::once())
			->method('getCalendarUris')
			->willReturn([$calendar]);
		$this->timeFactory->expects(self::once())
			->method('getDateTime')
			->with('+10 minutes')
			->willReturn($inTenMinutes);
		$query->expects(self::once())
			->method('setTimerangeStart')
			->with($now);
		$query->expects(self::once())
			->method('setTimerangeEnd')
			->with($immutableInTenMinutes);
		$this->calendarManager->expects(self::once())
			->method('searchForPrincipal')
			->with($query)
			->willReturn([]);
		$this->generator->expects(self::once())
			->method('getVCalendar')
			->willReturn($vCalendar);
		$vCalendar->expects(self::never())
			->method('add');
		$this->generator->expects(self::once())
			->method('setObjects')
			->with($vCalendar);
		$this->generator->expects(self::once())
			->method('setTimeRange')
			->with($now, $immutableInTenMinutes);
		$this->generator->expects(self::once())
			->method('setTimeZone')
			->with($timezone);
		$this->generator->expects(self::once())
			->method('setVAvailability')
			->with($availability);
		$this->generator->expects(self::once())
			->method('getResult')
			->willReturn($result);
		$this->l10n->expects(self::never())
			->method('t');
		$status = $this->service->processCalendarAvailability($user, $availability->serialize());
		$this->assertEquals(new Status(IUserStatus::AWAY, IUserStatus::MESSAGE_AVAILABILITY), $status);
	}

	public function testAvailabilityAndSearchCalendarsStatusBusyTentative(): void {
		$user = $this->createConfiguredMock(IUser::class, [
			'getUID' => 'admin',
			'getEMailAddress' => 'test@test.com',
		]);
		$server = $this->createMock(Server::class);
		$schedulingPlugin = $this->createMock(Plugin::class);
		$aclPlugin = $this->createMock(\Sabre\DAVACL\Plugin::class);
		$calendarHome = $this->createMock(LocalHref::class);
		$acl = [[200 => ['{urn:ietf:params:xml:ns:caldav}schedule-inbox-URL' => $calendarHome]]];
		$now = new \DateTimeImmutable('1970-1-1 00:00', new \DateTimeZone('UTC'));
		$inTenMinutes = new \DateTime('1970-1-1 01:00');
		$immutableInTenMinutes = \DateTimeImmutable::createFromMutable($inTenMinutes);
		$principal = 'principals/users/admin';
		$query = $this->createMock(CalendarQuery::class);
		$timezone = new \DateTimeZone('UTC');
		$timezoneObj = $this->createMock(VTimeZone::class);
		$calendar = $this->createMock(CalendarImpl::class);
		$vCalendar = $this->createMock(VCalendar::class);
		$availability = $this->getVAvailability();
		$result = Reader::read('BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Sabre//Sabre VObject 4.5.3//EN
		CALSCALE:GREGORIAN
METHOD:REQUEST
BEGIN:VFREEBUSY
DTSTART:19700101T000000Z
DTEND:19700101T003600Z
DTSTAMP:19700101T000200Z
FREEBUSY;FBTYPE=BUSY-TENTATIVE:19700101T000000Z/19700101T003600Z
END:VFREEBUSY
END:VCALENDAR');

		$user->expects(self::once())
			->method('getUID')
			->willReturn('admin');
		$user->expects(self::once())
			->method('getEMailAddress')
			->willReturn('test@test.com');
		$this->server->expects(self::once())
			->method('getServer')
			->willReturn($server);
		$server->expects(self::exactly(2))
			->method('getPlugin')
			->withConsecutive(
				['caldav-schedule'],
				['acl'],
			)->willReturnOnConsecutiveCalls($schedulingPlugin, $aclPlugin);
		$aclPlugin->expects(self::once())
			->method('principalSearch')
			->with(['{http://sabredav.org/ns}email-address' => 'test@test.com'])
			->willReturn($acl);
		$calendarHome->expects(self::once())
			->method('getHref')
			->willReturn('calendars/admin/inbox/');
		$aclPlugin->expects(self::once())
			->method('checkPrivileges')
			->willReturn(true);
		$this->timeFactory->expects(self::once())
			->method('now')
			->willReturn($now);
		$this->calendarManager->expects(self::once())
			->method('getCalendarsForPrincipal')
			->with($principal)
			->willReturn([$calendar]);
		$this->calendarManager->expects(self::once())
			->method('newQuery')
			->with($principal)
			->willReturn($query);
		$calendar->expects(self::once())
			->method('getSchedulingTransparency')
			->willReturn(new ScheduleCalendarTransp('opaque'));
		$calendar->expects(self::once())
			->method('getSchedulingTimezone')
			->willReturn($timezoneObj);
		$timezoneObj->expects(self::once())
			->method('getTimeZone')
			->willReturn($timezone);
		$calendar->expects(self::once())
			->method('getUri');
		$query->expects(self::once())
			->method('addSearchCalendar');
		$query->expects(self::once())
			->method('getCalendarUris')
			->willReturn([$calendar]);
		$this->timeFactory->expects(self::once())
			->method('getDateTime')
			->with('+10 minutes')
			->willReturn($inTenMinutes);
		$query->expects(self::once())
			->method('setTimerangeStart')
			->with($now);
		$query->expects(self::once())
			->method('setTimerangeEnd')
			->with($immutableInTenMinutes);
		$this->calendarManager->expects(self::once())
			->method('searchForPrincipal')
			->with($query)
			->willReturn([]);
		$this->generator->expects(self::once())
			->method('getVCalendar')
			->willReturn($vCalendar);
		$vCalendar->expects(self::never())
			->method('add');
		$this->generator->expects(self::once())
			->method('setObjects')
			->with($vCalendar);
		$this->generator->expects(self::once())
			->method('setTimeRange')
			->with($now, $immutableInTenMinutes);
		$this->generator->expects(self::once())
			->method('setTimeZone')
			->with($timezone);
		$this->generator->expects(self::once())
			->method('setVAvailability')
			->with($availability);
		$this->generator->expects(self::once())
			->method('getResult')
			->willReturn($result);
		$this->l10n->expects(self::never())
			->method('t');
		$status = $this->service->processCalendarAvailability($user, $availability->serialize());
		$this->assertEquals(new Status(IUserStatus::AWAY, IUserStatus::MESSAGE_CALENDAR_BUSY_TENTATIVE), $status);
	}

	public function testAvailabilityAndSearchCalendarsStatusBusyUnknownProperty(): void {
		$user = $this->createConfiguredMock(IUser::class, [
			'getUID' => 'admin',
			'getEMailAddress' => 'test@test.com',
		]);
		$server = $this->createMock(Server::class);
		$schedulingPlugin = $this->createMock(Plugin::class);
		$aclPlugin = $this->createMock(\Sabre\DAVACL\Plugin::class);
		$calendarHome = $this->createMock(LocalHref::class);
		$acl = [[200 => ['{urn:ietf:params:xml:ns:caldav}schedule-inbox-URL' => $calendarHome]]];
		$now = new \DateTimeImmutable('1970-1-1 00:00', new \DateTimeZone('UTC'));
		$inTenMinutes = new \DateTime('1970-1-1 01:00');
		$immutableInTenMinutes = \DateTimeImmutable::createFromMutable($inTenMinutes);
		$principal = 'principals/users/admin';
		$query = $this->createMock(CalendarQuery::class);
		$timezone = new \DateTimeZone('UTC');
		$timezoneObj = $this->createMock(VTimeZone::class);
		$calendar = $this->createMock(CalendarImpl::class);
		$vCalendar = $this->createMock(VCalendar::class);
		$availability = $this->getVAvailability();
		$result = Reader::read('BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Sabre//Sabre VObject 4.5.3//EN
		CALSCALE:GREGORIAN
METHOD:REQUEST
BEGIN:VFREEBUSY
DTSTART:19700101T000000Z
DTEND:19700101T003600Z
DTSTAMP:19700101T000200Z
FREEBUSY;FBTYPE=X-MEETING:19700101T000000Z/19700101T003600Z
END:VFREEBUSY
END:VCALENDAR');

		$user->expects(self::once())
			->method('getUID')
			->willReturn('admin');
		$user->expects(self::once())
			->method('getEMailAddress')
			->willReturn('test@test.com');
		$this->server->expects(self::once())
			->method('getServer')
			->willReturn($server);
		$server->expects(self::exactly(2))
			->method('getPlugin')
			->withConsecutive(
				['caldav-schedule'],
				['acl'],
			)->willReturnOnConsecutiveCalls($schedulingPlugin, $aclPlugin);
		$aclPlugin->expects(self::once())
			->method('principalSearch')
			->with(['{http://sabredav.org/ns}email-address' => 'test@test.com'])
			->willReturn($acl);
		$calendarHome->expects(self::once())
			->method('getHref')
			->willReturn('calendars/admin/inbox/');
		$aclPlugin->expects(self::once())
			->method('checkPrivileges')
			->willReturn(true);
		$this->timeFactory->expects(self::once())
			->method('now')
			->willReturn($now);
		$this->calendarManager->expects(self::once())
			->method('getCalendarsForPrincipal')
			->with($principal)
			->willReturn([$calendar]);
		$this->calendarManager->expects(self::once())
			->method('newQuery')
			->with($principal)
			->willReturn($query);
		$calendar->expects(self::once())
			->method('getSchedulingTransparency')
			->willReturn(new ScheduleCalendarTransp('opaque'));
		$calendar->expects(self::once())
			->method('getSchedulingTimezone')
			->willReturn($timezoneObj);
		$timezoneObj->expects(self::once())
			->method('getTimeZone')
			->willReturn($timezone);
		$calendar->expects(self::once())
			->method('getUri');
		$query->expects(self::once())
			->method('addSearchCalendar');
		$query->expects(self::once())
			->method('getCalendarUris')
			->willReturn([$calendar]);
		$this->timeFactory->expects(self::once())
			->method('getDateTime')
			->with('+10 minutes')
			->willReturn($inTenMinutes);
		$query->expects(self::once())
			->method('setTimerangeStart')
			->with($now);
		$query->expects(self::once())
			->method('setTimerangeEnd')
			->with($immutableInTenMinutes);
		$this->calendarManager->expects(self::once())
			->method('searchForPrincipal')
			->with($query)
			->willReturn([]);
		$this->generator->expects(self::once())
			->method('getVCalendar')
			->willReturn($vCalendar);
		$vCalendar->expects(self::never())
			->method('add');
		$this->generator->expects(self::once())
			->method('setObjects')
			->with($vCalendar);
		$this->generator->expects(self::once())
			->method('setTimeRange')
			->with($now, $immutableInTenMinutes);
		$this->generator->expects(self::once())
			->method('setTimeZone')
			->with($timezone);
		$this->generator->expects(self::once())
			->method('setVAvailability')
			->with($availability);
		$this->generator->expects(self::once())
			->method('getResult')
			->willReturn($result);
		$this->l10n->expects(self::never())
			->method('t');
		$status = $this->service->processCalendarAvailability($user, $availability->serialize());
		$this->assertNull($status);
	}

	private function getVAvailability(): Document {
		return Reader::read('BEGIN:VCALENDAR
PRODID:Nextcloud DAV app
BEGIN:VTIMEZONE
TZID:Europe/Vienna
BEGIN:STANDARD
TZNAME:CET
TZOFFSETFROM:+0200
TZOFFSETTO:+0100
DTSTART:19701025T030000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
BEGIN:DAYLIGHT
TZNAME:CEST
TZOFFSETFROM:+0100
TZOFFSETTO:+0200
DTSTART:19700329T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
END:DAYLIGHT
END:VTIMEZONE
BEGIN:VAVAILABILITY
BEGIN:AVAILABLE
DTSTART;TZID=Europe/Vienna:20231025T000000
DTEND;TZID=Europe/Vienna:20231025T235900
UID:d866782e-e003-4906-9ece-303f270a2c6b
RRULE:FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR,SA,SU
END:AVAILABLE
END:VAVAILABILITY
END:VCALENDAR');
	}
}
