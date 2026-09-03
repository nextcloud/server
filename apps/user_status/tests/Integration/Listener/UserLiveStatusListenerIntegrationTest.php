<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\UserStatus\Tests\Integration\Listener;

use OCA\DAV\CalDAV\Status\StatusService as CalendarStatusService;
use OCA\UserStatus\Db\UserStatusMapper;
use OCA\UserStatus\Listener\UserLiveStatusListener;
use OCA\UserStatus\Service\StatusService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IDBConnection;
use OCP\IUser;
use OCP\Server;
use OCP\User\Events\UserLiveStatusEvent;
use OCP\UserStatus\IUserStatus;
use Psr\Log\LoggerInterface;
use Test\TestCase;
use function time;

#[\PHPUnit\Framework\Attributes\Group(name: 'DB')]
class UserLiveStatusListenerIntegrationTest extends TestCase {

	private const USER_ID = 'test123';

	/** HEARTBEAT_INTERVAL in apps/user_status/src/services/heartbeatScheduler.ts */
	private const CLIENT_HEARTBEAT_INTERVAL = 5 * 60;

	/** ClearOldStatusesBackgroundJob::setInterval() */
	private const CLEANUP_JOB_INTERVAL = 60;

	private UserStatusMapper $mapper;
	private StatusService $service;
	private UserLiveStatusListener $listener;

	protected function setUp(): void {
		parent::setUp();

		$this->mapper = Server::get(UserStatusMapper::class);
		$this->service = Server::get(StatusService::class);

		$db = Server::get(IDBConnection::class);
		$qb = $db->getQueryBuilder();
		$qb->delete('user_status')->executeStatement();

		$this->listener = new UserLiveStatusListener(
			$this->mapper,
			$this->service,
			Server::get(ITimeFactory::class),
			$this->createMock(CalendarStatusService::class),
			$this->createMock(LoggerInterface::class),
		);
	}

	public function testActiveUserSurvivesTheInvalidationSweep(): void {
		$this->service->setStatus(self::USER_ID, IUserStatus::ONLINE, time(), false);

		for ($minute = 1; $minute <= 30; $minute++) {
			$this->passTime(self::CLEANUP_JOB_INTERVAL);
			$this->runCleanupJob();

			self::assertSame(
				IUserStatus::ONLINE,
				$this->mapper->findByUserId(self::USER_ID)->getStatus(),
				"User went offline after $minute minutes while still sending heartbeats",
			);

			if ($minute % (self::CLIENT_HEARTBEAT_INTERVAL / self::CLEANUP_JOB_INTERVAL) === 0) {
				$this->heartbeat();
			}
		}
	}

	/** Equivalent to letting time pass, without faking the clock of every collaborator. */
	private function passTime(int $seconds): void {
		$status = $this->mapper->findByUserId(self::USER_ID);
		$status->setStatusTimestamp($status->getStatusTimestamp() - $seconds);
		$this->mapper->update($status);
	}

	private function heartbeat(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn(self::USER_ID);

		$this->listener->handle(new UserLiveStatusEvent($user, IUserStatus::ONLINE, time()));
	}

	private function runCleanupJob(): void {
		$now = time();
		$this->mapper->clearStatusesOlderThan($now - StatusService::INVALIDATE_STATUS_THRESHOLD, $now);
	}
}
