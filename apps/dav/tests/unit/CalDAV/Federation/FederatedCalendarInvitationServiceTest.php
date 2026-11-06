<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\CalDAV\Federation;

use OCA\DAV\CalDAV\Federation\CalendarFederationConfig;
use OCA\DAV\CalDAV\Federation\FederatedCalendarEntity;
use OCA\DAV\CalDAV\Federation\FederatedCalendarInvitationService;
use OCA\DAV\CalDAV\Federation\FederatedCalendarMapper;
use OCP\App\IAppManager;
use OCP\BackgroundJob\IJobList;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Federation\ICloudIdManager;
use OCP\IURLGenerator;
use OCP\Log\Audit\CriticalActionPerformedEvent;
use OCP\Notification\IManager as INotificationManager;
use OCP\Notification\INotification;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class FederatedCalendarInvitationServiceTest extends TestCase {
	private FederatedCalendarMapper&MockObject $mapper;
	private IJobList&MockObject $jobList;
	private INotificationManager&MockObject $notificationManager;
	private IEventDispatcher&MockObject $eventDispatcher;
	private FederatedCalendarInvitationService $service;

	protected function setUp(): void {
		parent::setUp();

		$this->mapper = $this->createMock(FederatedCalendarMapper::class);
		$this->jobList = $this->createMock(IJobList::class);
		$this->notificationManager = $this->createMock(INotificationManager::class);
		$this->eventDispatcher = $this->createMock(IEventDispatcher::class);

		$notification = $this->createMock(INotification::class);
		$notification->method('setApp')->willReturnSelf();
		$notification->method('setUser')->willReturnSelf();
		$notification->method('setObject')->willReturnSelf();
		$this->notificationManager->method('createNotification')->willReturn($notification);

		$this->service = new FederatedCalendarInvitationService(
			$this->mapper,
			$this->jobList,
			$this->notificationManager,
			$this->createMock(IURLGenerator::class),
			$this->createMock(ICloudIdManager::class),
			$this->createMock(CalendarFederationConfig::class),
			$this->createMock(IAppManager::class),
			$this->createMock(LoggerInterface::class),
			$this->eventDispatcher,
		);
	}

	private function createCalendar(): FederatedCalendarEntity {
		$calendar = new FederatedCalendarEntity();
		$calendar->setId(10);
		$calendar->setPrincipaluri('principals/users/alice');
		$calendar->setSharedBy('bob@remote.example');
		$calendar->setState(FederatedCalendarEntity::STATE_PENDING);
		return $calendar;
	}

	public function testAccept(): void {
		$calendar = $this->createCalendar();

		$this->mapper->expects($this->once())
			->method('update')
			->with($calendar);
		$this->eventDispatcher->expects($this->once())
			->method('dispatchTyped')
			->with($this->callback(fn (CriticalActionPerformedEvent $event): bool => $event->getOperation() === 'dav.federated_calendar.accepted'
				&& $event->getLogMessage() === 'Federated calendar "%s" shared by "%s" was accepted'
				&& $event->getParameters() === ['federatedCalendarId' => 10, 'remoteOwner' => 'bob@remote.example']));

		$this->service->accept($calendar);

		$this->assertSame(FederatedCalendarEntity::STATE_ACCEPTED, $calendar->getState());
	}

	public function testDecline(): void {
		$calendar = $this->createCalendar();

		$this->mapper->expects($this->once())
			->method('deleteById')
			->with(10);
		$this->eventDispatcher->expects($this->once())
			->method('dispatchTyped')
			->with($this->callback(fn (CriticalActionPerformedEvent $event): bool => $event->getOperation() === 'dav.federated_calendar.declined'
				&& $event->getLogMessage() === 'Federated calendar "%s" shared by "%s" was declined'
				&& $event->getParameters() === ['federatedCalendarId' => 10, 'remoteOwner' => 'bob@remote.example']));

		$this->service->decline($calendar);
	}
}
