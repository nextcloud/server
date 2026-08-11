<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\UserStatus\Tests\Integration\Service;

use OCA\UserStatus\Db\UserStatus;
use OCA\UserStatus\Db\UserStatusMapper;
use OCA\UserStatus\Service\StatusService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\IDBConnection;
use OCP\Server;
use OCP\UserStatus\IUserStatus;
use Test\TestCase;
use function sleep;
use function time;

#[\PHPUnit\Framework\Attributes\Group(name: 'DB')]
class StatusServiceIntegrationTest extends TestCase {

	private StatusService $service;
	private UserStatusMapper $mapper;
	private IDBConnection $db;

	protected function setUp(): void {
		parent::setUp();

		$this->service = Server::get(StatusService::class);
		$this->mapper = Server::get(UserStatusMapper::class);

		$this->db = Server::get(IDBConnection::class);
		$qb = $this->db->getQueryBuilder();
		$qb->delete('user_status')->executeStatement();
	}

	/**
	 * Reads a row without going through StatusService::processStatus(), which
	 * would rewrite a stale status before the assertion can see it.
	 */
	private function readRaw(string $userId): ?UserStatus {
		try {
			return $this->mapper->findByUserId($userId);
		} catch (DoesNotExistException) {
			return null;
		}
	}

	public function testNoStatusYet(): void {
		$this->expectException(DoesNotExistException::class);

		$this->service->findByUserId('test123');
	}

	public function testCustomStatusMessageTimestamp(): void {
		$before = time();
		$this->service->setCustomMessage(
			'test123',
			'🍕',
			'Lunch',
			null,
		);
		$after = time();

		$status = $this->service->findByUserId('test123');

		self::assertSame('Lunch', $status->getCustomMessage());
		self::assertGreaterThanOrEqual($before, $status->getStatusMessageTimestamp());
		self::assertLessThanOrEqual($after, $status->getStatusMessageTimestamp());
	}

	public function testOnlineStatusKeepsMessageTimestamp(): void {
		$this->service->setStatus(
			'test123',
			IUserStatus::OFFLINE,
			time() + 1000,
			false,
		);
		$this->service->setCustomMessage(
			'test123',
			'🍕',
			'Lunch',
			null,
		);
		$timeAfterInsert = time();
		sleep(1);
		$this->service->setStatus(
			'test123',
			IUserStatus::ONLINE,
			time() + 2000,
			false,
		);
		$status = $this->service->findByUserId('test123');

		self::assertSame('Lunch', $status->getCustomMessage());
		self::assertLessThanOrEqual($timeAfterInsert, $status->getStatusMessageTimestamp());
	}

	public function testCreateRestoreBackupAutomatically(): void {
		$this->service->setStatus(
			'test123',
			IUserStatus::ONLINE,
			null,
			false,
		);
		$this->service->setUserStatus(
			'test123',
			IUserStatus::DND,
			'meeting',
			true,
		);

		self::assertSame(
			'meeting',
			$this->service->findByUserId('test123')->getMessageId(),
		);
		self::assertSame(
			IUserStatus::ONLINE,
			$this->service->findByUserId('_test123')->getStatus(),
		);

		$revertedStatus = $this->service->revertUserStatus(
			'test123',
			'meeting',
		);

		self::assertNotNull($revertedStatus, 'Status should have been reverted');

		try {
			$this->service->findByUserId('_test123');
			$this->fail('Expected DoesNotExistException() to be thrown when finding backup status after reverting');
		} catch (DoesNotExistException) {
		}

		self::assertSame(
			IUserStatus::ONLINE,
			$this->service->findByUserId('test123')->getStatus(),
		);
	}

	public function testCallOverwritesMeetingStatus(): void {
		$this->service->setStatus(
			'test123',
			IUserStatus::ONLINE,
			null,
			false,
		);
		$this->service->setUserStatus(
			'test123',
			IUserStatus::BUSY,
			IUserStatus::MESSAGE_CALENDAR_BUSY,
			true,
		);
		self::assertSame(
			'meeting',
			$this->service->findByUserId('test123')->getMessageId(),
		);

		$this->service->setUserStatus(
			'test123',
			IUserStatus::BUSY,
			IUserStatus::MESSAGE_CALL,
			true,
		);
		self::assertSame(
			IUserStatus::BUSY,
			$this->service->findByUserId('test123')->getStatus(),
		);

		self::assertSame(
			IUserStatus::MESSAGE_CALL,
			$this->service->findByUserId('test123')->getMessageId(),
		);
	}

	public function testOtherAutomationsDoNotOverwriteEachOther(): void {
		$this->service->setStatus(
			'test123',
			IUserStatus::ONLINE,
			null,
			false,
		);
		$this->service->setUserStatus(
			'test123',
			IUserStatus::DND,
			IUserStatus::MESSAGE_AVAILABILITY,
			true,
		);
		self::assertSame(
			'availability',
			$this->service->findByUserId('test123')->getMessageId(),
		);

		$nostatus = $this->service->setUserStatus(
			'test123',
			IUserStatus::BUSY,
			IUserStatus::MESSAGE_CALENDAR_BUSY,
			true,
		);

		self::assertNull($nostatus);
		self::assertSame(
			IUserStatus::MESSAGE_AVAILABILITY,
			$this->service->findByUserId('test123')->getMessageId(),
		);
	}

	/*
	 * Orphaned automated statuses: a live row sits on an automated status but
	 * there is no backup row to revert into, so revertUserStatus() has nothing
	 * to restore. It must still clear the automated status, otherwise the user
	 * is stuck on it forever and the heartbeat can never bring them back
	 * online.
	 */

	public function testRevertWithoutBackupClearsAutomatedStatus(): void {
		// No backup taken, so nothing can ever be restored for this user.
		$this->service->setUserStatus(
			'test123',
			IUserStatus::BUSY,
			IUserStatus::MESSAGE_CALENDAR_BUSY,
			false,
		);
		self::assertSame(
			IUserStatus::MESSAGE_CALENDAR_BUSY,
			$this->readRaw('test123')?->getMessageId(),
		);

		$reverted = $this->service->revertUserStatus('test123', IUserStatus::MESSAGE_CALENDAR_BUSY);

		self::assertNull($reverted, 'Nothing can be restored without a backup');
		self::assertNull(
			$this->readRaw('test123'),
			'The unreachable automated status must be cleared, not left behind',
		);
	}

	public function testRevertWithoutBackupKeepsStatusTheUserChangedThemselves(): void {
		$this->service->setUserStatus(
			'test123',
			IUserStatus::BUSY,
			IUserStatus::MESSAGE_CALENDAR_BUSY,
			false,
		);
		// The user replaces the automated message with their own.
		$this->service->setCustomMessage('test123', '🍕', 'Lunch', null);

		$reverted = $this->service->revertUserStatus('test123', IUserStatus::MESSAGE_CALENDAR_BUSY);

		self::assertNull($reverted);
		$status = $this->readRaw('test123');
		self::assertNotNull($status, 'A status the user set themselves must not be deleted');
		self::assertSame('Lunch', $status->getCustomMessage());
		self::assertNull($status->getMessageId());
	}

	public function testRevertWithoutBackupKeepsOtherAutomatedStatus(): void {
		$this->service->setUserStatus(
			'test123',
			IUserStatus::BUSY,
			IUserStatus::MESSAGE_CALL,
			false,
		);

		// The meeting automation reverts, but the live status belongs to a call.
		$reverted = $this->service->revertUserStatus('test123', IUserStatus::MESSAGE_CALENDAR_BUSY);

		self::assertNull($reverted);
		self::assertSame(
			IUserStatus::MESSAGE_CALL,
			$this->readRaw('test123')?->getMessageId(),
			'An unrelated automated status must be left alone',
		);
	}

	public function testFreshUserAutomatedStatusIsClearedOnRevert(): void {
		// A user who has never had a status row: there is nothing to back up,
		// so the automated status is applied without a backup.
		$applied = $this->service->setUserStatus(
			'test123',
			IUserStatus::BUSY,
			IUserStatus::MESSAGE_CALENDAR_BUSY,
			true,
		);

		self::assertNotNull($applied, 'A user without a previous status should still get the meeting status');
		self::assertNull($this->readRaw('_test123'), 'There was no status to back up');

		$this->service->revertUserStatus('test123', IUserStatus::MESSAGE_CALENDAR_BUSY);

		self::assertNull(
			$this->readRaw('test123'),
			'The meeting status must be cleared when the meeting ends',
		);
	}
}
