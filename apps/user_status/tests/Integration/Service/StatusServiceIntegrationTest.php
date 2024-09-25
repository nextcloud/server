<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\UserStatus\Tests\Integration\Service;

use OCA\UserStatus\Service\StatusService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\IDBConnection;
use OCP\Server;
use OCP\UserStatus\IUserStatus;
use Test\TestCase;
use function sleep;
use function time;

/**
 * @group DB
 */
class StatusServiceIntegrationTest extends TestCase {

	private StatusService $service;

	protected function setUp(): void {
		parent::setUp();

		$this->service = Server::get(StatusService::class);

		$db = Server::get(IDBConnection::class);
		$qb = $db->getQueryBuilder();
		$qb->delete('user_status')->executeStatement();
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
			IUserStatus::AWAY,
			IUserStatus::MESSAGE_CALENDAR_BUSY,
			true,
		);
		self::assertSame(
			'meeting',
			$this->service->findByUserId('test123')->getMessageId(),
		);

		$this->service->setUserStatus(
			'test123',
			IUserStatus::AWAY,
			IUserStatus::MESSAGE_CALL,
			true,
		);
		self::assertSame(
			IUserStatus::AWAY,
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
			IUserStatus::AWAY,
			IUserStatus::MESSAGE_CALENDAR_BUSY,
			true,
		);

		self::assertNull($nostatus);
		self::assertSame(
			IUserStatus::MESSAGE_AVAILABILITY,
			$this->service->findByUserId('test123')->getMessageId(),
		);
	}
}
