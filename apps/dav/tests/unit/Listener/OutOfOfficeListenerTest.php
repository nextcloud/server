<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\Listener;

use DateTimeImmutable;
use InvalidArgumentException;
use OCA\DAV\CalDAV\Calendar;
use OCA\DAV\CalDAV\CalendarHome;
use OCA\DAV\CalDAV\CalendarObject;
use OCA\DAV\CalDAV\InvitationResponse\InvitationResponseServer;
use OCA\DAV\CalDAV\Plugin;
use OCA\DAV\CalDAV\TimezoneService;
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
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Component\VEvent;
use Sabre\VObject\Reader;
use Test\TestCase;

/**
 * @covers \OCA\DAV\Listener\OutOfOfficeListener
 */
class OutOfOfficeListenerTest extends TestCase {

	private ServerFactory&MockObject $serverFactory;
	private IConfig&MockObject $appConfig;
	private LoggerInterface&MockObject $loggerInterface;
	private TimezoneService&MockObject $timezoneService;
	private OutOfOfficeListener $listener;

	protected function setUp(): void {
		parent::setUp();

		$this->serverFactory = $this->createMock(ServerFactory::class);
		$this->appConfig = $this->createMock(IConfig::class);
		$this->timezoneService = $this->createMock(TimezoneService::class);
		$this->loggerInterface = $this->createMock(LoggerInterface::class);

		$this->listener = new OutOfOfficeListener(
			$this->serverFactory,
			$this->appConfig,
			$this->timezoneService,
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

	public function testHandleSchedulingWithDefaultTimezone(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('user123');
		$data = $this->createMock(IOutOfOfficeData::class);
		$data->method('getUser')->willReturn($user);
		$data->method('getStartDate')
			->willReturn((new DateTimeImmutable('2023-12-12T00:00:00Z'))->getTimestamp());
		$data->method('getEndDate')
			->willReturn((new DateTimeImmutable('2023-12-13T00:00:00Z'))->getTimestamp());
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
		$this->timezoneService->expects(self::once())
			->method('getUserTimezone')
			->with('user123')
			->willReturn('Europe/Prague');
		$calendarHome->expects(self::once())
			->method('getChild')
			->with('personal-1')
			->willReturn($calendar);
		$calendar->expects(self::once())
			->method('createFile')
			->willReturnCallback(function ($name, $data): void {
				$vcalendar = Reader::read($data);
				if (!($vcalendar instanceof VCalendar)) {
					throw new InvalidArgumentException('Calendar data should be a VCALENDAR');
				}
				$vevent = $vcalendar->VEVENT;
				if ($vevent === null || !($vevent instanceof VEvent)) {
					throw new InvalidArgumentException('Calendar data should contain a VEVENT');
				}
				self::assertSame('Europe/Prague', $vevent->DTSTART['TZID']?->getValue());
				self::assertSame('Europe/Prague', $vevent->DTEND['TZID']?->getValue());
			});
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
		$data->method('getStartDate')
			->willReturn((new DateTimeImmutable('2023-12-12T00:00:00Z'))->getTimestamp());
		$data->method('getEndDate')
			->willReturn((new DateTimeImmutable('2023-12-14T00:00:00Z'))->getTimestamp());
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
		$this->timezoneService->expects(self::once())
			->method('getUserTimezone')
			->with('user123')
			->willReturn(null);
		$this->timezoneService->expects(self::once())
			->method('getDefaultTimezone')
			->willReturn('Europe/Berlin');
		$calendarHome->expects(self::once())
			->method('getChild')
			->with('personal-1')
			->willReturn($calendar);
		$calendar->expects(self::once())
			->method('getChild')
			->willThrowException(new NotFound());
		$calendar->expects(self::once())
			->method('createFile')
			->willReturnCallback(function ($name, $data): void {
				$vcalendar = Reader::read($data);
				if (!($vcalendar instanceof VCalendar)) {
					throw new InvalidArgumentException('Calendar data should be a VCALENDAR');
				}
				$vevent = $vcalendar->VEVENT;
				if ($vevent === null || !($vevent instanceof VEvent)) {
					throw new InvalidArgumentException('Calendar data should contain a VEVENT');
				}
				self::assertSame('Europe/Berlin', $vevent->DTSTART['TZID']?->getValue());
				self::assertSame('Europe/Berlin', $vevent->DTEND['TZID']?->getValue());
			});
		$event = new OutOfOfficeChangedEvent($data);

		$this->listener->handle($event);
	}

	public function testHandleChangeWithoutTimezone(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('user123');
		$data = $this->createMock(IOutOfOfficeData::class);
		$data->method('getUser')->willReturn($user);
		$data->method('getStartDate')
			->willReturn((new DateTimeImmutable('2023-01-12T00:00:00Z'))->getTimestamp());
		$data->method('getEndDate')
			->willReturn((new DateTimeImmutable('2023-12-14T00:00:00Z'))->getTimestamp());
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
		$this->timezoneService->expects(self::once())
			->method('getUserTimezone')
			->with('user123')
			->willReturn(null);
		$this->timezoneService->expects(self::once())
			->method('getDefaultTimezone')
			->willReturn('UTC');
		$calendar->expects(self::once())
			->method('getChild')
			->willReturn($eventNode);
		$eventNode->expects(self::once())
			->method('put')
			->willReturnCallback(function ($data): void {
				$vcalendar = Reader::read($data);
				if (!($vcalendar instanceof VCalendar)) {
					throw new InvalidArgumentException('Calendar data should be a VCALENDAR');
				}
				$vevent = $vcalendar->VEVENT;
				if ($vevent === null || !($vevent instanceof VEvent)) {
					throw new InvalidArgumentException('Calendar data should contain a VEVENT');
				}
				// UTC datetimes are stored without a TZID
				self::assertSame(null, $vevent->DTSTART['TZID']?->getValue());
				self::assertSame(null, $vevent->DTEND['TZID']?->getValue());
			});
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
