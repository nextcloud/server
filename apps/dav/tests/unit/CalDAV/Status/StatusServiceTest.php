<?php
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Tests\unit\CalDAV\Status;

use OC\Calendar\CalendarQuery;
use OCA\DAV\CalDAV\CalendarImpl;
use OCA\DAV\CalDAV\Status\StatusService;
use OCA\UserStatus\Db\UserStatus;
use OCA\UserStatus\Service\StatusService as UserStatusService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Calendar\IManager;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IUser;
use OCP\IUserManager;
use OCP\User\IAvailabilityCoordinator;
use OCP\User\IOutOfOfficeData;
use OCP\UserStatus\IUserStatus;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class StatusServiceTest extends TestCase {
	private ITimeFactory|MockObject $timeFactory;
	private IManager|MockObject $calendarManager;
	private IUserManager|MockObject $userManager;
	private UserStatusService|MockObject $userStatusService;
	private IAvailabilityCoordinator|MockObject $availabilityCoordinator;
	private ICacheFactory|MockObject $cacheFactory;
	private LoggerInterface|MockObject $logger;
	private ICache|MockObject $cache;
	private StatusService $service;

	protected function setUp(): void {
		parent::setUp();

		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->calendarManager = $this->createMock(IManager::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->userStatusService = $this->createMock(UserStatusService::class);
		$this->availabilityCoordinator = $this->createMock(IAvailabilityCoordinator::class);
		$this->cacheFactory = $this->createMock(ICacheFactory::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->cache = $this->createMock(ICache::class);
		$this->cacheFactory->expects(self::once())
			->method('createLocal')
			->with('CalendarStatusService')
			->willReturn($this->cache);

		$this->service = new StatusService($this->timeFactory,
			$this->calendarManager,
			$this->userManager,
			$this->userStatusService,
			$this->availabilityCoordinator,
			$this->cacheFactory,
			$this->logger,
		);
	}

	public function testNoUser(): void {
		$this->userManager->expects(self::once())
			->method('get')
			->willReturn(null);
		$this->availabilityCoordinator->expects(self::never())
			->method('getCurrentOutOfOfficeData');
		$this->availabilityCoordinator->expects(self::never())
			->method('isInEffect');
		$this->logger->expects(self::never())
			->method('debug');
		$this->cache->expects(self::never())
			->method('get');
		$this->cache->expects(self::never())
			->method('set');
		$this->calendarManager->expects(self::never())
			->method('getCalendarsForPrincipal');
		$this->calendarManager->expects(self::never())
			->method('newQuery');
		$this->timeFactory->expects(self::never())
			->method('getDateTime');
		$this->calendarManager->expects(self::never())
			->method('searchForPrincipal');
		$this->userStatusService->expects(self::never())
			->method('revertUserStatus');
		$this->userStatusService->expects(self::never())
			->method('setUserStatus');
		$this->userStatusService->expects(self::never())
			->method('findByUserId');

		$this->service->processCalendarStatus('admin');
	}

	public function testOOOInEffect(): void {
		$user = $this->createConfiguredMock(IUser::class, [
			'getUID' => 'admin',
		]);

		$this->userManager->expects(self::once())
			->method('get')
			->willReturn($user);
		$this->availabilityCoordinator->expects(self::once())
			->method('getCurrentOutOfOfficeData')
			->willReturn($this->createMock(IOutOfOfficeData::class));
		$this->availabilityCoordinator->expects(self::once())
			->method('isInEffect')
			->willReturn(true);
		$this->logger->expects(self::once())
			->method('debug');
		$this->cache->expects(self::never())
			->method('get');
		$this->cache->expects(self::never())
			->method('set');
		$this->calendarManager->expects(self::never())
			->method('getCalendarsForPrincipal');
		$this->calendarManager->expects(self::never())
			->method('newQuery');
		$this->timeFactory->expects(self::never())
			->method('getDateTime');
		$this->calendarManager->expects(self::never())
			->method('searchForPrincipal');
		$this->userStatusService->expects(self::never())
			->method('revertUserStatus');
		$this->userStatusService->expects(self::never())
			->method('setUserStatus');
		$this->userStatusService->expects(self::never())
			->method('findByUserId');

		$this->service->processCalendarStatus('admin');
	}

	public function testNoCalendars(): void {
		$user = $this->createConfiguredMock(IUser::class, [
			'getUID' => 'admin',
		]);

		$this->userManager->expects(self::once())
			->method('get')
			->willReturn($user);
		$this->availabilityCoordinator->expects(self::once())
			->method('getCurrentOutOfOfficeData')
			->willReturn(null);
		$this->availabilityCoordinator->expects(self::never())
			->method('isInEffect');
		$this->cache->expects(self::once())
			->method('get')
			->willReturn(null);
		$this->cache->expects(self::once())
			->method('set');
		$this->calendarManager->expects(self::once())
			->method('getCalendarsForPrincipal')
			->willReturn([]);
		$this->calendarManager->expects(self::never())
			->method('newQuery');
		$this->timeFactory->expects(self::never())
			->method('getDateTime');
		$this->calendarManager->expects(self::never())
			->method('searchForPrincipal');
		$this->userStatusService->expects(self::once())
			->method('revertUserStatus');
		$this->logger->expects(self::once())
			->method('debug');
		$this->userStatusService->expects(self::never())
			->method('setUserStatus');
		$this->userStatusService->expects(self::never())
			->method('findByUserId');

		$this->service->processCalendarStatus('admin');
	}

	public function testNoCalendarEvents(): void {
		$user = $this->createConfiguredMock(IUser::class, [
			'getUID' => 'admin',
		]);

		$this->userManager->expects(self::once())
			->method('get')
			->willReturn($user);
		$this->availabilityCoordinator->expects(self::once())
			->method('getCurrentOutOfOfficeData')
			->willReturn(null);
		$this->availabilityCoordinator->expects(self::never())
			->method('isInEffect');
		$this->cache->expects(self::once())
			->method('get')
			->willReturn(null);
		$this->cache->expects(self::once())
			->method('set');
		$this->calendarManager->expects(self::once())
			->method('getCalendarsForPrincipal')
			->willReturn([$this->createMock(CalendarImpl::class)]);
		$this->calendarManager->expects(self::once())
			->method('newQuery')
			->willReturn(new CalendarQuery('admin'));
		$this->timeFactory->expects(self::exactly(2))
			->method('getDateTime')
			->willReturn(new \DateTime());
		$this->calendarManager->expects(self::once())
			->method('searchForPrincipal')
			->willReturn([]);
		$this->userStatusService->expects(self::once())
			->method('revertUserStatus');
		$this->logger->expects(self::once())
			->method('debug');
		$this->userStatusService->expects(self::never())
			->method('setUserStatus');
		$this->userStatusService->expects(self::never())
			->method('findByUserId');

		$this->service->processCalendarStatus('admin');
	}

	public function testCalendarNoEventObjects(): void {
		$user = $this->createConfiguredMock(IUser::class, [
			'getUID' => 'admin',
		]);

		$this->userManager->expects(self::once())
			->method('get')
			->willReturn($user);
		$this->availabilityCoordinator->expects(self::once())
			->method('getCurrentOutOfOfficeData')
			->willReturn(null);
		$this->availabilityCoordinator->expects(self::never())
			->method('isInEffect');
		$this->cache->expects(self::once())
			->method('get')
			->willReturn(null);
		$this->cache->expects(self::once())
			->method('set');
		$this->calendarManager->expects(self::once())
			->method('getCalendarsForPrincipal')
			->willReturn([$this->createMock(CalendarImpl::class)]);
		$this->calendarManager->expects(self::once())
			->method('newQuery')
			->willReturn(new CalendarQuery('admin'));
		$this->timeFactory->expects(self::exactly(2))
			->method('getDateTime')
			->willReturn(new \DateTime());
		$this->userStatusService->expects(self::once())
			->method('findByUserId')
			->willThrowException(new DoesNotExistException(''));
		$this->calendarManager->expects(self::once())
			->method('searchForPrincipal')
			->willReturn([['objects' => []]]);
		$this->userStatusService->expects(self::once())
			->method('revertUserStatus');
		$this->logger->expects(self::once())
			->method('debug');
		$this->userStatusService->expects(self::never())
			->method('setUserStatus');


		$this->service->processCalendarStatus('admin');
	}

	public function testCalendarEvent(): void {
		$user = $this->createConfiguredMock(IUser::class, [
			'getUID' => 'admin',
		]);

		$this->userManager->expects(self::once())
			->method('get')
			->willReturn($user);
		$this->availabilityCoordinator->expects(self::once())
			->method('getCurrentOutOfOfficeData')
			->willReturn(null);
		$this->availabilityCoordinator->expects(self::never())
			->method('isInEffect');
		$this->cache->expects(self::once())
			->method('get')
			->willReturn(null);
		$this->cache->expects(self::once())
			->method('set');
		$this->calendarManager->expects(self::once())
			->method('getCalendarsForPrincipal')
			->willReturn([$this->createMock(CalendarImpl::class)]);
		$this->calendarManager->expects(self::once())
			->method('newQuery')
			->willReturn(new CalendarQuery('admin'));
		$this->timeFactory->expects(self::exactly(2))
			->method('getDateTime')
			->willReturn(new \DateTime());
		$this->userStatusService->expects(self::once())
			->method('findByUserId')
			->willThrowException(new DoesNotExistException(''));
		$this->calendarManager->expects(self::once())
			->method('searchForPrincipal')
			->willReturn([['objects' => [[]]]]);
		$this->userStatusService->expects(self::never())
			->method('revertUserStatus');
		$this->logger->expects(self::once())
			->method('debug');
		$this->userStatusService->expects(self::once())
			->method('setUserStatus');


		$this->service->processCalendarStatus('admin');
	}

	public function testCallStatus(): void {
		$user = $this->createConfiguredMock(IUser::class, [
			'getUID' => 'admin',
		]);

		$this->userManager->expects(self::once())
			->method('get')
			->willReturn($user);
		$this->availabilityCoordinator->expects(self::once())
			->method('getCurrentOutOfOfficeData')
			->willReturn(null);
		$this->availabilityCoordinator->expects(self::never())
			->method('isInEffect');
		$this->cache->expects(self::once())
			->method('get')
			->willReturn(null);
		$this->cache->expects(self::once())
			->method('set');
		$this->calendarManager->expects(self::once())
			->method('getCalendarsForPrincipal')
			->willReturn([$this->createMock(CalendarImpl::class)]);
		$this->calendarManager->expects(self::once())
			->method('newQuery')
			->willReturn(new CalendarQuery('admin'));
		$this->timeFactory->expects(self::exactly(2))
			->method('getDateTime')
			->willReturn(new \DateTime());
		$this->calendarManager->expects(self::once())
			->method('searchForPrincipal')
			->willReturn([['objects' => [[]]]]);
		$userStatus = new UserStatus();
		$userStatus->setMessageId(IUserStatus::MESSAGE_CALL);
		$userStatus->setStatusTimestamp(123456);
		$this->userStatusService->expects(self::once())
			->method('findByUserId')
			->willReturn($userStatus);
		$this->logger->expects(self::once())
			->method('debug');
		$this->userStatusService->expects(self::never())
			->method('revertUserStatus');
		$this->userStatusService->expects(self::never())
			->method('setUserStatus');


		$this->service->processCalendarStatus('admin');
	}

	public function testInvisibleStatus(): void {
		$user = $this->createConfiguredMock(IUser::class, [
			'getUID' => 'admin',
		]);

		$this->userManager->expects(self::once())
			->method('get')
			->willReturn($user);
		$this->availabilityCoordinator->expects(self::once())
			->method('getCurrentOutOfOfficeData')
			->willReturn(null);
		$this->availabilityCoordinator->expects(self::never())
			->method('isInEffect');
		$this->cache->expects(self::once())
			->method('get')
			->willReturn(null);
		$this->cache->expects(self::once())
			->method('set');
		$this->calendarManager->expects(self::once())
			->method('getCalendarsForPrincipal')
			->willReturn([$this->createMock(CalendarImpl::class)]);
		$this->calendarManager->expects(self::once())
			->method('newQuery')
			->willReturn(new CalendarQuery('admin'));
		$this->timeFactory->expects(self::exactly(2))
			->method('getDateTime')
			->willReturn(new \DateTime());
		$this->calendarManager->expects(self::once())
			->method('searchForPrincipal')
			->willReturn([['objects' => [[]]]]);
		$userStatus = new UserStatus();
		$userStatus->setStatus(IUserStatus::INVISIBLE);
		$userStatus->setStatusTimestamp(123456);
		$this->userStatusService->expects(self::once())
			->method('findByUserId')
			->willReturn($userStatus);
		$this->logger->expects(self::once())
			->method('debug');
		$this->userStatusService->expects(self::never())
			->method('revertUserStatus');
		$this->userStatusService->expects(self::never())
			->method('setUserStatus');


		$this->service->processCalendarStatus('admin');
	}

	public function testDNDStatus(): void {
		$user = $this->createConfiguredMock(IUser::class, [
			'getUID' => 'admin',
		]);

		$this->userManager->expects(self::once())
			->method('get')
			->willReturn($user);
		$this->availabilityCoordinator->expects(self::once())
			->method('getCurrentOutOfOfficeData')
			->willReturn(null);
		$this->availabilityCoordinator->expects(self::never())
			->method('isInEffect');
		$this->cache->expects(self::once())
			->method('get')
			->willReturn(null);
		$this->cache->expects(self::once())
			->method('set');
		$this->calendarManager->expects(self::once())
			->method('getCalendarsForPrincipal')
			->willReturn([$this->createMock(CalendarImpl::class)]);
		$this->calendarManager->expects(self::once())
			->method('newQuery')
			->willReturn(new CalendarQuery('admin'));
		$this->timeFactory->expects(self::exactly(2))
			->method('getDateTime')
			->willReturn(new \DateTime());
		$this->calendarManager->expects(self::once())
			->method('searchForPrincipal')
			->willReturn([['objects' => [[]]]]);
		$userStatus = new UserStatus();
		$userStatus->setStatus(IUserStatus::DND);
		$userStatus->setStatusTimestamp(123456);
		$this->userStatusService->expects(self::once())
			->method('findByUserId')
			->willReturn($userStatus);
		$this->logger->expects(self::once())
			->method('debug');
		$this->userStatusService->expects(self::never())
			->method('revertUserStatus');
		$this->userStatusService->expects(self::never())
			->method('setUserStatus');


		$this->service->processCalendarStatus('admin');
	}
}
