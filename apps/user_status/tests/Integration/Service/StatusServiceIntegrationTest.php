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
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IDBConnection;
use OCP\IUser;
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
	private IUser $user;

	protected function setUp(): void {
		parent::setUp();

		$this->service = Server::get(StatusService::class);

		$db = Server::get(IDBConnection::class);
		$qb = $db->getQueryBuilder();
		$qb->delete('user_status')->executeStatement();
		$qb->delete('users')->executeStatement();
		$userId = 'userstatus_testuser';
		$this->user = \OC::$server->getUserManager()->createUser($userId, 'testPassword456');
		static::loginAsUser($userId);
	}

	protected function tearDown(): void {
		parent::tearDown();
		$this->user->delete();
	}

	public function testNoStatusYet(): void {
		$this->expectException(DoesNotExistException::class);

		$this->service->findByUserId('userstatus_testuser');
	}

	public function testCustomStatusMessageTimestamp(): void {
		$this->service->setCustomMessage(
			'userstatus_testuser',
			'🍕',
			'Lunch',
			null,
		);

		$status = $this->service->findByUserId('userstatus_testuser');

		self::assertSame('Lunch', $status->getCustomMessage());
		self::assertGreaterThanOrEqual(time(), $status->getStatusMessageTimestamp());
	}

	public function testOnlineStatusKeepsMessageTimestamp(): void {
		$this->service->setStatus(
			'userstatus_testuser',
			IUserStatus::OFFLINE,
			time() + 1000,
			false,
		);
		$this->service->setCustomMessage(
			'userstatus_testuser',
			'🍕',
			'Lunch',
			null,
		);
		$timeAfterInsert = time();
		sleep(1);
		$this->service->setStatus(
			'userstatus_testuser',
			IUserStatus::ONLINE,
			time() + 2000,
			false,
		);
		$status = $this->service->findByUserId('userstatus_testuser');

		self::assertSame('Lunch', $status->getCustomMessage());
		self::assertLessThanOrEqual($timeAfterInsert, $status->getStatusMessageTimestamp());
	}

	public function testCreateRestoreBackupAutomatically(): void {
//		$this->service->setStatus(
//			'userstatus_testuser',
//			IUserStatus::ONLINE,
//			null,
//			false,
//		);
//		$this->service->setUserStatus(
//			'userstatus_testuser',
//			IUserStatus::DND,
//			'meeting',
//			true,
//		);
//		self::assertSame(
//			'meeting',
//			$this->service->findByUserId('userstatus_testuser')->getMessageId(),
//		);
//
//		$this->service->revertUserStatus(
//			'userstatus_testuser',
//		);
//		self::assertSame(
//			IUserStatus::ONLINE,
//			$this->service->findByUserId('userstatus_testuser')->getStatus(),
//		);
	}

	public function testCi(): void {
		// TODO: remove if CI turns red
		self::assertTrue(false);
	}

}
