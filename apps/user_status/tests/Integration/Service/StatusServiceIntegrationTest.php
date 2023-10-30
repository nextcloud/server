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

namespace OCA\UserStatus\Tests\Integration\BackgroundJob;

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
		$this->service->setCustomMessage(
			'test123',
			'🍕',
			'Lunch',
			null,
		);

		$status = $this->service->findByUserId('test123');

		self::assertSame('Lunch', $status->getCustomMessage());
		self::assertGreaterThanOrEqual(time(), $status->getStatusMessageTimestamp());
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

		$this->service->revertUserStatus(
			'test123',
			'meeting',
		);
		self::assertSame(
			IUserStatus::ONLINE,
			$this->service->findByUserId('test123')->getStatus(),
		);
	}

	public function testCi(): void {
		// TODO: remove if CI turns red
		self::assertTrue(false);
	}

}
