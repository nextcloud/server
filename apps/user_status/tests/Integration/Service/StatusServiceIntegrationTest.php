<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\UserStatus\Tests\Integration\Service;

use OCA\UserStatus\Db\UserStatus;
use OCA\UserStatus\Db\UserStatusMapper;
use OCA\UserStatus\Service\StatusRepairService;
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
	private StatusRepairService $repairService;
	private UserStatusMapper $mapper;
	private IDBConnection $db;

	protected function setUp(): void {
		parent::setUp();

		$this->service = Server::get(StatusService::class);
		$this->repairService = Server::get(StatusRepairService::class);
		$this->mapper = Server::get(UserStatusMapper::class);

		$this->db = Server::get(IDBConnection::class);
		$qb = $this->db->getQueryBuilder();
		$qb->delete('user_status')->executeStatement();
	}

	/** Reads a row without processStatus() rewriting a stale status first. */
	private function readRaw(string $userId): ?UserStatus {
		try {
			return $this->mapper->findByUserId($userId);
		} catch (DoesNotExistException) {
			return null;
		}
	}

	/** Simulates elapsed time by ageing the stored timestamps backwards. */
	private function age(string $userId, int $seconds): void {
		$this->db->executeStatement(
			'UPDATE `*PREFIX*user_status` SET `status_timestamp` = `status_timestamp` - ? WHERE `user_id` IN (?, ?)',
			[$seconds, $userId, '_' . $userId],
		);
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

	/* An automated status with no backup must still be cleared. */

	public function testRevertWithoutBackupClearsAutomatedStatus(): void {
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

		$reverted = $this->service->revertUserStatus('test123', IUserStatus::MESSAGE_CALENDAR_BUSY);

		self::assertNull($reverted);
		self::assertSame(
			IUserStatus::MESSAGE_CALL,
			$this->readRaw('test123')?->getMessageId(),
			'An unrelated automated status must be left alone',
		);
	}

	public function testFreshUserAutomatedStatusIsClearedOnRevert(): void {
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

	public function testRevertAfterLongMeetingRefreshesTimestamp(): void {
		$this->service->setStatus('test123', IUserStatus::ONLINE, null, false);
		$this->service->setUserStatus(
			'test123',
			IUserStatus::BUSY,
			IUserStatus::MESSAGE_CALENDAR_BUSY,
			true,
		);

		$this->age('test123', StatusService::INVALIDATE_STATUS_THRESHOLD * 2);

		$before = time();
		$reverted = $this->service->revertUserStatus('test123', IUserStatus::MESSAGE_CALENDAR_BUSY);

		self::assertNotNull($reverted);
		self::assertGreaterThanOrEqual(
			$before,
			$this->readRaw('test123')?->getStatusTimestamp(),
			'A restored status must not carry the stale timestamp from before the meeting',
		);
	}

	public function testRevertAfterLongMeetingDoesNotFallBackToOffline(): void {
		$this->service->setStatus('test123', IUserStatus::ONLINE, null, false);
		$this->service->setUserStatus(
			'test123',
			IUserStatus::BUSY,
			IUserStatus::MESSAGE_CALENDAR_BUSY,
			true,
		);
		$this->age('test123', StatusService::INVALIDATE_STATUS_THRESHOLD * 2);

		$this->service->revertUserStatus('test123', IUserStatus::MESSAGE_CALENDAR_BUSY);

		// findByUserId() runs processStatus(), which cleans stale statuses.
		self::assertSame(
			IUserStatus::ONLINE,
			$this->service->findByUserId('test123')->getStatus(),
			'The user was online before the meeting and must not be flipped to offline by reading the status',
		);
	}

	public function testRevertMultipleAfterLongMeetingRefreshesTimestamp(): void {
		$this->service->setStatus('test123', IUserStatus::ONLINE, null, false);
		$this->service->setUserStatus(
			'test123',
			IUserStatus::BUSY,
			IUserStatus::MESSAGE_CALENDAR_BUSY,
			true,
		);

		$this->age('test123', StatusService::INVALIDATE_STATUS_THRESHOLD * 2);

		$before = time();
		$this->service->revertMultipleUserStatus(['test123'], IUserStatus::MESSAGE_CALENDAR_BUSY);

		self::assertGreaterThanOrEqual(
			$before,
			$this->readRaw('test123')?->getStatusTimestamp(),
			'A bulk-restored status must not carry the stale timestamp from before the meeting',
		);
	}

	public function testRevertMultipleAfterLongMeetingDoesNotFallBackToOffline(): void {
		$this->service->setStatus('test123', IUserStatus::ONLINE, null, false);
		$this->service->setUserStatus(
			'test123',
			IUserStatus::BUSY,
			IUserStatus::MESSAGE_CALENDAR_BUSY,
			true,
		);
		$this->age('test123', StatusService::INVALIDATE_STATUS_THRESHOLD * 2);

		$this->service->revertMultipleUserStatus(['test123'], IUserStatus::MESSAGE_CALENDAR_BUSY);

		// findByUserId() runs processStatus(), which cleans stale statuses.
		self::assertSame(
			IUserStatus::ONLINE,
			$this->service->findByUserId('test123')->getStatus(),
			'The user was online before the meeting and must not be flipped to offline after bulk status update followed by reading the status',
		);
	}

	public function testRevertMultipleWithoutBackupClearsAutomatedStatus(): void {
		$this->service->setUserStatus(
			'test123',
			IUserStatus::BUSY,
			IUserStatus::MESSAGE_CALENDAR_BUSY,
			true,
		);

		$this->service->revertMultipleUserStatus(['test123'], IUserStatus::MESSAGE_CALENDAR_BUSY);

		self::assertNull(
			$this->readRaw('test123'),
			'A bulk revert must clear an automated status that has no backup, or the user stays stuck on it',
		);
	}

	public function testRevertMultipleKeepsStatusTheUserChangedThemselves(): void {
		$this->service->setStatus('test123', IUserStatus::ONLINE, null, false);
		$this->service->setUserStatus(
			'test123',
			IUserStatus::BUSY,
			IUserStatus::MESSAGE_CALENDAR_BUSY,
			true,
		);
		// The user replaces the automated message with their own.
		$this->service->setCustomMessage('test123', '🍕', 'Lunch', null);

		$this->service->revertMultipleUserStatus(['test123'], IUserStatus::MESSAGE_CALENDAR_BUSY);

		self::assertSame(
			'Lunch',
			$this->readRaw('test123')?->getCustomMessage(),
			'A status the user set themselves must survive the bulk revert',
		);
	}

	/* A backup whose live row moved off the automated status is stranded. */

	public function testStrandedBackupIsCleanedUp(): void {
		$this->service->setStatus('test123', IUserStatus::ONLINE, null, false);
		$this->service->setUserStatus(
			'test123',
			IUserStatus::BUSY,
			IUserStatus::MESSAGE_CALENDAR_BUSY,
			true,
		);
		$this->service->clearMessage('test123');
		self::assertNotNull($this->readRaw('_test123'), 'Precondition: the backup is stranded');

		$deleted = $this->repairService->deleteStrandedBackups();

		self::assertSame(1, $deleted);
		self::assertNull($this->readRaw('_test123'), 'The stranded backup must be removed');
		self::assertNotNull($this->readRaw('test123'), 'The live status must be untouched');
	}

	public function testBackupOfAnOngoingMeetingSurvivesCleanup(): void {
		$this->service->setStatus('test123', IUserStatus::ONLINE, null, false);
		$this->service->setUserStatus(
			'test123',
			IUserStatus::BUSY,
			IUserStatus::MESSAGE_CALENDAR_BUSY,
			true,
		);

		$deleted = $this->repairService->deleteStrandedBackups();

		self::assertSame(0, $deleted);
		self::assertNotNull(
			$this->readRaw('_test123'),
			'The backup for a meeting that is still running must survive',
		);
	}

	public function testLongOutOfOfficeBackupSurvivesCleanup(): void {
		$this->service->setStatus('test123', IUserStatus::ONLINE, null, false);
		$this->service->setUserStatus(
			'test123',
			IUserStatus::DND,
			IUserStatus::MESSAGE_OUT_OF_OFFICE,
			true,
		);
		$this->age('test123', StatusService::INVALIDATE_STATUS_THRESHOLD * 100);

		$deleted = $this->repairService->deleteStrandedBackups();

		self::assertSame(0, $deleted);
		self::assertNotNull(
			$this->readRaw('_test123'),
			'A long running out-of-office backup must not be treated as stranded',
		);
	}

	public function testAutomatedStatusWorksAgainAfterStrandedBackupCleanup(): void {
		$this->service->setStatus('test123', IUserStatus::ONLINE, null, false);
		$this->service->setUserStatus(
			'test123',
			IUserStatus::BUSY,
			IUserStatus::MESSAGE_CALENDAR_BUSY,
			true,
		);
		$this->service->clearMessage('test123');

		self::assertNull(
			$this->service->setUserStatus('test123', IUserStatus::BUSY, IUserStatus::MESSAGE_CALL, true),
			'Precondition: the stranded backup blocks automated statuses',
		);

		$this->repairService->deleteStrandedBackups();

		self::assertNotNull(
			$this->service->setUserStatus('test123', IUserStatus::BUSY, IUserStatus::MESSAGE_CALL, true),
			'Automated statuses must work again once the stranded backup is gone',
		);
	}

	public function testCleanupLeavesUsersWithoutBackupsAlone(): void {
		$this->service->setStatus('test123', IUserStatus::ONLINE, null, false);
		$this->service->setCustomMessage('test123', '🍕', 'Lunch', null);

		$deleted = $this->repairService->deleteStrandedBackups();

		self::assertSame(0, $deleted);
		self::assertSame('Lunch', $this->readRaw('test123')?->getCustomMessage());
	}

	/** The prefix concatenation is SQL, so it needs a real database. */
	public function testFindsOrphanedAutomatedStatusOnARealDatabase(): void {
		$this->service->setUserStatus(
			'test123',
			IUserStatus::BUSY,
			IUserStatus::MESSAGE_CALENDAR_BUSY,
			true,
		);
		self::assertNull($this->readRaw('_test123'), 'Precondition: there is no backup');

		$this->service->setStatus('test456', IUserStatus::ONLINE, null, false);
		$this->service->setUserStatus(
			'test456',
			IUserStatus::BUSY,
			IUserStatus::MESSAGE_CALENDAR_BUSY,
			true,
		);

		$orphaned = $this->repairService->findOrphanedAutomatedStatusIds();

		self::assertSame([$this->readRaw('test123')?->getId()], $orphaned);
	}

	public function testFindsStatusesWithoutBackupFlagOnARealDatabase(): void {
		$this->service->setStatus('test123', IUserStatus::ONLINE, null, false);
		$this->db->executeStatement(
			'UPDATE `*PREFIX*user_status` SET `is_backup` = NULL WHERE `user_id` = ?',
			['test123'],
		);

		$ids = $this->mapper->findStatusesWithoutBackupFlagIds();

		self::assertSame([$this->readRaw('test123')?->getId()], $ids);
		self::assertSame(1, $this->mapper->normalizeBackupFlagByIds($ids));
		self::assertSame([], $this->mapper->findStatusesWithoutBackupFlagIds());
	}
}
