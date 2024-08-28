<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\UserStatus\Tests\Listener;

use OCA\DAV\CalDAV\Status\StatusService as CalendarStatusService;
use OCA\UserStatus\Db\UserStatus;
use OCA\UserStatus\Db\UserStatusMapper;
use OCA\UserStatus\Listener\UserDeletedListener;
use OCA\UserStatus\Listener\UserLiveStatusListener;
use OCA\UserStatus\Service\StatusService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\EventDispatcher\GenericEvent;
use OCP\IUser;
use OCP\User\Events\UserLiveStatusEvent;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class UserLiveStatusListenerTest extends TestCase {

	/** @var UserStatusMapper|MockObject */
	private $mapper;
	/** @var StatusService|MockObject */
	private $statusService;
	/** @var ITimeFactory|MockObject */
	private $timeFactory;

	/** @var UserDeletedListener */
	private $listener;

	private CalendarStatusService|MockObject $calendarStatusService;

	private LoggerInterface|MockObject $logger;

	protected function setUp(): void {
		parent::setUp();

		$this->mapper = $this->createMock(UserStatusMapper::class);
		$this->statusService = $this->createMock(StatusService::class);
		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->calendarStatusService = $this->createMock(CalendarStatusService::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		$this->listener = new UserLiveStatusListener(
			$this->mapper,
			$this->statusService,
			$this->timeFactory,
			$this->calendarStatusService,
			$this->logger,
		);
	}

	/**
	 * @param string $userId
	 * @param string $previousStatus
	 * @param int $previousTimestamp
	 * @param bool $previousIsUserDefined
	 * @param string $eventStatus
	 * @param int $eventTimestamp
	 * @param bool $expectExisting
	 * @param bool $expectUpdate
	 *
	 * @dataProvider handleEventWithCorrectEventDataProvider
	 */
	public function testHandleWithCorrectEvent(string $userId,
		string $previousStatus,
		int $previousTimestamp,
		bool $previousIsUserDefined,
		string $eventStatus,
		int $eventTimestamp,
		bool $expectExisting,
		bool $expectUpdate): void {
		$userStatus = new UserStatus();

		if ($expectExisting) {
			$userStatus->setId(42);
			$userStatus->setUserId($userId);
			$userStatus->setStatus($previousStatus);
			$userStatus->setStatusTimestamp($previousTimestamp);
			$userStatus->setIsUserDefined($previousIsUserDefined);

			$this->statusService->expects($this->once())
				->method('findByUserId')
				->with($userId)
				->willReturn($userStatus);
		} else {
			$this->statusService->expects($this->once())
				->method('findByUserId')
				->with($userId)
				->willThrowException(new DoesNotExistException(''));
		}

		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn($userId);
		$event = new UserLiveStatusEvent($user, $eventStatus, $eventTimestamp);

		$this->timeFactory->expects($this->atMost(1))
			->method('getTime')
			->willReturn(5000);

		if ($expectUpdate) {
			if ($expectExisting) {
				$this->mapper->expects($this->never())
					->method('insert');
				$this->mapper->expects($this->once())
					->method('update')
					->with($this->callback(function ($userStatus) use ($eventStatus, $eventTimestamp) {
						$this->assertEquals($eventStatus, $userStatus->getStatus());
						$this->assertEquals($eventTimestamp, $userStatus->getStatusTimestamp());
						$this->assertFalse($userStatus->getIsUserDefined());

						return true;
					}));
			} else {
				$this->mapper->expects($this->once())
					->method('insert')
					->with($this->callback(function ($userStatus) use ($eventStatus, $eventTimestamp) {
						$this->assertEquals($eventStatus, $userStatus->getStatus());
						$this->assertEquals($eventTimestamp, $userStatus->getStatusTimestamp());
						$this->assertFalse($userStatus->getIsUserDefined());

						return true;
					}));
				$this->mapper->expects($this->never())
					->method('update');
			}

			$this->listener->handle($event);
		} else {
			$this->mapper->expects($this->never())
				->method('insert');
			$this->mapper->expects($this->never())
				->method('update');

			$this->listener->handle($event);
		}
	}

	public function handleEventWithCorrectEventDataProvider(): array {
		return [
			['john.doe', 'offline', 0, false, 'online', 5000, true, true],
			['john.doe', 'offline', 0, false, 'online', 5000, false, true],
			['john.doe', 'online', 5000, false, 'online', 5000, true, false],
			['john.doe', 'online', 5000, false, 'online', 5000, false, true],
			['john.doe', 'away', 5000, false, 'online', 5000, true, true],
			['john.doe', 'online', 5000, false, 'away', 5000, true, false],
			['john.doe', 'away', 5000, true, 'online', 5000, true, false],
			['john.doe', 'online', 5000, true, 'away', 5000, true, false],
		];
	}

	public function testHandleWithWrongEvent(): void {
		$this->mapper->expects($this->never())
			->method('insertOrUpdate');

		$event = new GenericEvent();
		$this->listener->handle($event);
	}
}
