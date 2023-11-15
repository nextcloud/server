<?php

declare(strict_types=1);

/*
 * @copyright 2023 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2023 Christoph Wurst <christoph@winzerhof-wurst.at>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace OCA\DAV\Tests\Unit\Listener;

use OCA\DAV\CalDAV\Calendar;
use OCA\DAV\CalDAV\CalendarHome;
use OCA\DAV\CalDAV\CalendarObject;
use OCA\DAV\CalDAV\InvitationResponse\InvitationResponseServer;
use OCA\DAV\CalDAV\Plugin;
use OCA\DAV\Connector\Sabre\Server;
use OCA\DAV\Listener\OutOfOfficeListener;
use OCA\DAV\ServerFactory;
use OCP\EventDispatcher\Event;
use OCP\IConfig;
use OCP\IUser;
use OCP\User\Events\OutOfOfficeChangedEvent;
use OCP\User\Events\OutOfOfficeClearedEvent;
use OCP\User\Events\OutOfOfficeScheduledEvent;
use OCP\User\IOutOfOfficeData;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\Tree;
use Test\TestCase;

/**
 * @covers \OCA\DAV\Listener\OutOfOfficeListener
 */
class OutOfOfficeListenerTest extends TestCase {

	private ServerFactory|MockObject $serverFactory;
	private IConfig|MockObject $appConfig;
	private LoggerInterface|MockObject $loggerInterface;
	private OutOfOfficeListener $listener;

	protected function setUp(): void {
		parent::setUp();

		$this->serverFactory = $this->createMock(ServerFactory::class);
		$this->appConfig = $this->createMock(IConfig::class);
		$this->loggerInterface = $this->createMock(LoggerInterface::class);

		$this->listener = new OutOfOfficeListener(
			$this->serverFactory,
			$this->appConfig,
			$this->loggerInterface,
		);
	}

	public function testHandleUnrelated(): void {
		$event = new Event();

		$this->listener->handle($event);

		$this->addToAssertionCount(1);
	}

	public function testHandleSchedulingNoCalendarHome(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('user123');
		$data = $this->createMock(IOutOfOfficeData::class);
		$data->method('getUser')->willReturn($user);
		$davServer = $this->createMock(Server::class);
		$invitationServer = $this->createMock(InvitationResponseServer::class);
		$invitationServer->method('getServer')->willReturn($davServer);
		$this->serverFactory->method('createInviationResponseServer')->willReturn($invitationServer);
		$caldavPlugin = $this->createMock(Plugin::class);
		$davServer->expects(self::once())
			->method('getPlugin')
			->with('caldav')
			->willReturn($caldavPlugin);
		$event = new OutOfOfficeScheduledEvent($data);

		$this->listener->handle($event);
	}

	public function testHandleSchedulingNoCalendarHomeNode(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('user123');
		$data = $this->createMock(IOutOfOfficeData::class);
		$data->method('getUser')->willReturn($user);
		$davServer = $this->createMock(Server::class);
		$invitationServer = $this->createMock(InvitationResponseServer::class);
		$invitationServer->method('getServer')->willReturn($davServer);
		$this->serverFactory->method('createInviationResponseServer')->willReturn($invitationServer);
		$caldavPlugin = $this->createMock(Plugin::class);
		$davServer->expects(self::once())
			->method('getPlugin')
			->with('caldav')
			->willReturn($caldavPlugin);
		$caldavPlugin->expects(self::once())
			->method('getCalendarHomeForPrincipal')
			->willReturn('/home/calendar');
		$tree = $this->createMock(Tree::class);
		$davServer->tree = $tree;
		$tree->expects(self::once())
			->method('getNodeForPath')
			->with('/home/calendar')
			->willThrowException(new NotFound('nope'));
		$event = new OutOfOfficeScheduledEvent($data);

		$this->listener->handle($event);
	}

	public function testHandleSchedulingPersonalCalendarNotFound(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('user123');
		$data = $this->createMock(IOutOfOfficeData::class);
		$data->method('getUser')->willReturn($user);
		$davServer = $this->createMock(Server::class);
		$invitationServer = $this->createMock(InvitationResponseServer::class);
		$invitationServer->method('getServer')->willReturn($davServer);
		$this->serverFactory->method('createInviationResponseServer')->willReturn($invitationServer);
		$caldavPlugin = $this->createMock(Plugin::class);
		$davServer->expects(self::once())
			->method('getPlugin')
			->with('caldav')
			->willReturn($caldavPlugin);
		$caldavPlugin->expects(self::once())
			->method('getCalendarHomeForPrincipal')
			->willReturn('/home/calendar');
		$tree = $this->createMock(Tree::class);
		$davServer->tree = $tree;
		$calendarHome = $this->createMock(CalendarHome::class);
		$tree->expects(self::once())
			->method('getNodeForPath')
			->with('/home/calendar')
			->willReturn($calendarHome);
		$this->appConfig->expects(self::once())
			->method('getUserValue')
			->with('user123', 'dav', 'defaultCalendar', 'personal')
			->willReturn('personal-1');
		$calendarHome->expects(self::once())
			->method('getChild')
			->with('personal-1')
			->willThrowException(new NotFound('nope'));
		$event = new OutOfOfficeScheduledEvent($data);

		$this->listener->handle($event);
	}

	public function testHandleScheduling(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('user123');
		$data = $this->createMock(IOutOfOfficeData::class);
		$data->method('getUser')->willReturn($user);
		$davServer = $this->createMock(Server::class);
		$invitationServer = $this->createMock(InvitationResponseServer::class);
		$invitationServer->method('getServer')->willReturn($davServer);
		$this->serverFactory->method('createInviationResponseServer')->willReturn($invitationServer);
		$caldavPlugin = $this->createMock(Plugin::class);
		$davServer->expects(self::once())
			->method('getPlugin')
			->with('caldav')
			->willReturn($caldavPlugin);
		$caldavPlugin->expects(self::once())
			->method('getCalendarHomeForPrincipal')
			->willReturn('/home/calendar');
		$tree = $this->createMock(Tree::class);
		$davServer->tree = $tree;
		$calendarHome = $this->createMock(CalendarHome::class);
		$tree->expects(self::once())
			->method('getNodeForPath')
			->with('/home/calendar')
			->willReturn($calendarHome);
		$this->appConfig->expects(self::once())
			->method('getUserValue')
			->with('user123', 'dav', 'defaultCalendar', 'personal')
			->willReturn('personal-1');
		$calendar = $this->createMock(Calendar::class);
		$calendarHome->expects(self::once())
			->method('getChild')
			->with('personal-1')
			->willReturn($calendar);
		$calendar->expects(self::once())
			->method('createFile');
		$event = new OutOfOfficeScheduledEvent($data);

		$this->listener->handle($event);
	}

	public function testHandleChangeNoCalendarHome(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('user123');
		$data = $this->createMock(IOutOfOfficeData::class);
		$data->method('getUser')->willReturn($user);
		$davServer = $this->createMock(Server::class);
		$invitationServer = $this->createMock(InvitationResponseServer::class);
		$invitationServer->method('getServer')->willReturn($davServer);
		$this->serverFactory->method('createInviationResponseServer')->willReturn($invitationServer);
		$caldavPlugin = $this->createMock(Plugin::class);
		$davServer->expects(self::once())
			->method('getPlugin')
			->with('caldav')
			->willReturn($caldavPlugin);
		$event = new OutOfOfficeChangedEvent($data);

		$this->listener->handle($event);
	}

	public function testHandleChangeNoCalendarHomeNode(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('user123');
		$data = $this->createMock(IOutOfOfficeData::class);
		$data->method('getUser')->willReturn($user);
		$davServer = $this->createMock(Server::class);
		$invitationServer = $this->createMock(InvitationResponseServer::class);
		$invitationServer->method('getServer')->willReturn($davServer);
		$this->serverFactory->method('createInviationResponseServer')->willReturn($invitationServer);
		$caldavPlugin = $this->createMock(Plugin::class);
		$davServer->expects(self::once())
			->method('getPlugin')
			->with('caldav')
			->willReturn($caldavPlugin);
		$caldavPlugin->expects(self::once())
			->method('getCalendarHomeForPrincipal')
			->willReturn('/home/calendar');
		$tree = $this->createMock(Tree::class);
		$davServer->tree = $tree;
		$tree->expects(self::once())
			->method('getNodeForPath')
			->with('/home/calendar')
			->willThrowException(new NotFound('nope'));
		$event = new OutOfOfficeChangedEvent($data);

		$this->listener->handle($event);
	}

	public function testHandleChangePersonalCalendarNotFound(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('user123');
		$data = $this->createMock(IOutOfOfficeData::class);
		$data->method('getUser')->willReturn($user);
		$davServer = $this->createMock(Server::class);
		$invitationServer = $this->createMock(InvitationResponseServer::class);
		$invitationServer->method('getServer')->willReturn($davServer);
		$this->serverFactory->method('createInviationResponseServer')->willReturn($invitationServer);
		$caldavPlugin = $this->createMock(Plugin::class);
		$davServer->expects(self::once())
			->method('getPlugin')
			->with('caldav')
			->willReturn($caldavPlugin);
		$caldavPlugin->expects(self::once())
			->method('getCalendarHomeForPrincipal')
			->willReturn('/home/calendar');
		$tree = $this->createMock(Tree::class);
		$davServer->tree = $tree;
		$calendarHome = $this->createMock(CalendarHome::class);
		$tree->expects(self::once())
			->method('getNodeForPath')
			->with('/home/calendar')
			->willReturn($calendarHome);
		$this->appConfig->expects(self::once())
			->method('getUserValue')
			->with('user123', 'dav', 'defaultCalendar', 'personal')
			->willReturn('personal-1');
		$calendarHome->expects(self::once())
			->method('getChild')
			->with('personal-1')
			->willThrowException(new NotFound('nope'));
		$event = new OutOfOfficeChangedEvent($data);

		$this->listener->handle($event);
	}

	public function testHandleChangeRecreate(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('user123');
		$data = $this->createMock(IOutOfOfficeData::class);
		$data->method('getUser')->willReturn($user);
		$davServer = $this->createMock(Server::class);
		$invitationServer = $this->createMock(InvitationResponseServer::class);
		$invitationServer->method('getServer')->willReturn($davServer);
		$this->serverFactory->method('createInviationResponseServer')->willReturn($invitationServer);
		$caldavPlugin = $this->createMock(Plugin::class);
		$davServer->expects(self::once())
			->method('getPlugin')
			->with('caldav')
			->willReturn($caldavPlugin);
		$caldavPlugin->expects(self::once())
			->method('getCalendarHomeForPrincipal')
			->willReturn('/home/calendar');
		$tree = $this->createMock(Tree::class);
		$davServer->tree = $tree;
		$calendarHome = $this->createMock(CalendarHome::class);
		$tree->expects(self::once())
			->method('getNodeForPath')
			->with('/home/calendar')
			->willReturn($calendarHome);
		$this->appConfig->expects(self::once())
			->method('getUserValue')
			->with('user123', 'dav', 'defaultCalendar', 'personal')
			->willReturn('personal-1');
		$calendar = $this->createMock(Calendar::class);
		$calendarHome->expects(self::once())
			->method('getChild')
			->with('personal-1')
			->willReturn($calendar);
		$calendar->expects(self::once())
			->method('getChild')
			->willThrowException(new NotFound());
		$calendar->expects(self::once())
			->method('createFile');
		$event = new OutOfOfficeChangedEvent($data);

		$this->listener->handle($event);
	}

	public function testHandleChange(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('user123');
		$data = $this->createMock(IOutOfOfficeData::class);
		$data->method('getUser')->willReturn($user);
		$davServer = $this->createMock(Server::class);
		$invitationServer = $this->createMock(InvitationResponseServer::class);
		$invitationServer->method('getServer')->willReturn($davServer);
		$this->serverFactory->method('createInviationResponseServer')->willReturn($invitationServer);
		$caldavPlugin = $this->createMock(Plugin::class);
		$davServer->expects(self::once())
			->method('getPlugin')
			->with('caldav')
			->willReturn($caldavPlugin);
		$caldavPlugin->expects(self::once())
			->method('getCalendarHomeForPrincipal')
			->willReturn('/home/calendar');
		$tree = $this->createMock(Tree::class);
		$davServer->tree = $tree;
		$calendarHome = $this->createMock(CalendarHome::class);
		$tree->expects(self::once())
			->method('getNodeForPath')
			->with('/home/calendar')
			->willReturn($calendarHome);
		$this->appConfig->expects(self::once())
			->method('getUserValue')
			->with('user123', 'dav', 'defaultCalendar', 'personal')
			->willReturn('personal-1');
		$calendar = $this->createMock(Calendar::class);
		$calendarHome->expects(self::once())
			->method('getChild')
			->with('personal-1')
			->willReturn($calendar);
		$eventNode = $this->createMock(CalendarObject::class);
		$calendar->expects(self::once())
			->method('getChild')
			->willReturn($eventNode);
		$eventNode->expects(self::once())
			->method('put');
		$calendar->expects(self::never())
			->method('createFile');
		$event = new OutOfOfficeChangedEvent($data);

		$this->listener->handle($event);
	}

	public function testHandleClearNoCalendarHome(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('user123');
		$data = $this->createMock(IOutOfOfficeData::class);
		$data->method('getUser')->willReturn($user);
		$davServer = $this->createMock(Server::class);
		$invitationServer = $this->createMock(InvitationResponseServer::class);
		$invitationServer->method('getServer')->willReturn($davServer);
		$this->serverFactory->method('createInviationResponseServer')->willReturn($invitationServer);
		$caldavPlugin = $this->createMock(Plugin::class);
		$davServer->expects(self::once())
			->method('getPlugin')
			->with('caldav')
			->willReturn($caldavPlugin);
		$event = new OutOfOfficeClearedEvent($data);

		$this->listener->handle($event);
	}

	public function testHandleClearNoCalendarHomeNode(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('user123');
		$data = $this->createMock(IOutOfOfficeData::class);
		$data->method('getUser')->willReturn($user);
		$davServer = $this->createMock(Server::class);
		$invitationServer = $this->createMock(InvitationResponseServer::class);
		$invitationServer->method('getServer')->willReturn($davServer);
		$this->serverFactory->method('createInviationResponseServer')->willReturn($invitationServer);
		$caldavPlugin = $this->createMock(Plugin::class);
		$davServer->expects(self::once())
			->method('getPlugin')
			->with('caldav')
			->willReturn($caldavPlugin);
		$caldavPlugin->expects(self::once())
			->method('getCalendarHomeForPrincipal')
			->willReturn('/home/calendar');
		$tree = $this->createMock(Tree::class);
		$davServer->tree = $tree;
		$tree->expects(self::once())
			->method('getNodeForPath')
			->with('/home/calendar')
			->willThrowException(new NotFound('nope'));
		$event = new OutOfOfficeClearedEvent($data);

		$this->listener->handle($event);
	}

	public function testHandleClearPersonalCalendarNotFound(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('user123');
		$data = $this->createMock(IOutOfOfficeData::class);
		$data->method('getUser')->willReturn($user);
		$davServer = $this->createMock(Server::class);
		$invitationServer = $this->createMock(InvitationResponseServer::class);
		$invitationServer->method('getServer')->willReturn($davServer);
		$this->serverFactory->method('createInviationResponseServer')->willReturn($invitationServer);
		$caldavPlugin = $this->createMock(Plugin::class);
		$davServer->expects(self::once())
			->method('getPlugin')
			->with('caldav')
			->willReturn($caldavPlugin);
		$caldavPlugin->expects(self::once())
			->method('getCalendarHomeForPrincipal')
			->willReturn('/home/calendar');
		$tree = $this->createMock(Tree::class);
		$davServer->tree = $tree;
		$calendarHome = $this->createMock(CalendarHome::class);
		$tree->expects(self::once())
			->method('getNodeForPath')
			->with('/home/calendar')
			->willReturn($calendarHome);
		$this->appConfig->expects(self::once())
			->method('getUserValue')
			->with('user123', 'dav', 'defaultCalendar', 'personal')
			->willReturn('personal-1');
		$calendarHome->expects(self::once())
			->method('getChild')
			->with('personal-1')
			->willThrowException(new NotFound('nope'));
		$event = new OutOfOfficeClearedEvent($data);

		$this->listener->handle($event);
	}

	public function testHandleClearRecreate(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('user123');
		$data = $this->createMock(IOutOfOfficeData::class);
		$data->method('getUser')->willReturn($user);
		$davServer = $this->createMock(Server::class);
		$invitationServer = $this->createMock(InvitationResponseServer::class);
		$invitationServer->method('getServer')->willReturn($davServer);
		$this->serverFactory->method('createInviationResponseServer')->willReturn($invitationServer);
		$caldavPlugin = $this->createMock(Plugin::class);
		$davServer->expects(self::once())
			->method('getPlugin')
			->with('caldav')
			->willReturn($caldavPlugin);
		$caldavPlugin->expects(self::once())
			->method('getCalendarHomeForPrincipal')
			->willReturn('/home/calendar');
		$tree = $this->createMock(Tree::class);
		$davServer->tree = $tree;
		$calendarHome = $this->createMock(CalendarHome::class);
		$tree->expects(self::once())
			->method('getNodeForPath')
			->with('/home/calendar')
			->willReturn($calendarHome);
		$this->appConfig->expects(self::once())
			->method('getUserValue')
			->with('user123', 'dav', 'defaultCalendar', 'personal')
			->willReturn('personal-1');
		$calendar = $this->createMock(Calendar::class);
		$calendarHome->expects(self::once())
			->method('getChild')
			->with('personal-1')
			->willReturn($calendar);
		$calendar->expects(self::once())
			->method('getChild')
			->willThrowException(new NotFound());
		$event = new OutOfOfficeClearedEvent($data);

		$this->listener->handle($event);
	}

	public function testHandleClear(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('user123');
		$data = $this->createMock(IOutOfOfficeData::class);
		$data->method('getUser')->willReturn($user);
		$davServer = $this->createMock(Server::class);
		$invitationServer = $this->createMock(InvitationResponseServer::class);
		$invitationServer->method('getServer')->willReturn($davServer);
		$this->serverFactory->method('createInviationResponseServer')->willReturn($invitationServer);
		$caldavPlugin = $this->createMock(Plugin::class);
		$davServer->expects(self::once())
			->method('getPlugin')
			->with('caldav')
			->willReturn($caldavPlugin);
		$caldavPlugin->expects(self::once())
			->method('getCalendarHomeForPrincipal')
			->willReturn('/home/calendar');
		$tree = $this->createMock(Tree::class);
		$davServer->tree = $tree;
		$calendarHome = $this->createMock(CalendarHome::class);
		$tree->expects(self::once())
			->method('getNodeForPath')
			->with('/home/calendar')
			->willReturn($calendarHome);
		$this->appConfig->expects(self::once())
			->method('getUserValue')
			->with('user123', 'dav', 'defaultCalendar', 'personal')
			->willReturn('personal-1');
		$calendar = $this->createMock(Calendar::class);
		$calendarHome->expects(self::once())
			->method('getChild')
			->with('personal-1')
			->willReturn($calendar);
		$eventNode = $this->createMock(CalendarObject::class);
		$calendar->expects(self::once())
			->method('getChild')
			->willReturn($eventNode);
		$eventNode->expects(self::once())
			->method('delete');
		$event = new OutOfOfficeClearedEvent($data);

		$this->listener->handle($event);
	}
}
