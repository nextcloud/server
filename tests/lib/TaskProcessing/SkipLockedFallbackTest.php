<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\TaskProcessing;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MariaDb1027Platform;
use Doctrine\DBAL\Platforms\MariaDb1052Platform;
use Doctrine\DBAL\Platforms\MariaDb1060Platform;
use Doctrine\DBAL\Platforms\MariaDb110700Platform;
use Doctrine\DBAL\Platforms\MySQL57Platform;
use Doctrine\DBAL\Platforms\MySQL80Platform;
use Doctrine\DBAL\Platforms\MySQL84Platform;
use OC\TaskProcessing\Db\Task as TaskEntity;
use OC\TaskProcessing\Db\TaskMapper;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IDBConnection;
use OCP\Server;
use OCP\TaskProcessing\Task;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

/**
 * Claim routing on MySQL/MariaDB servers with and without SKIP LOCKED support.
 *
 * The connection handed to the mapper reports a MySQL/MariaDB platform of the given
 * version and forwards everything else to the real test database, so the fallback
 * claim runs against real rows on whatever database the suite uses.
 */
#[\PHPUnit\Framework\Attributes\Group('DB')]
class SkipLockedFallbackTest extends TestCase {
	private const TASK_TYPE = 'test:skip-locked-fallback';

	private IDBConnection $realDb;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();
		$this->realDb = Server::get(IDBConnection::class);
		$this->deleteTestTasks();
	}

	#[\Override]
	protected function tearDown(): void {
		$this->deleteTestTasks();
		parent::tearDown();
	}

	public static function platformsWithoutSkipLocked(): array {
		return [
			'MariaDB 10.2-10.4' => [MariaDb1027Platform::class],
			'MariaDB 10.5' => [MariaDb1052Platform::class],
			'MySQL 5.7' => [MySQL57Platform::class],
		];
	}

	public static function platformsWithSkipLocked(): array {
		return [
			'MariaDB 10.6' => [MariaDb1060Platform::class],
			'MariaDB 11.7' => [MariaDb110700Platform::class],
			'MySQL 8.0' => [MySQL80Platform::class],
			'MySQL 8.4' => [MySQL84Platform::class],
		];
	}

	/**
	 * @param class-string<AbstractPlatform> $platformClass
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('platformsWithoutSkipLocked')]
	public function testClaimFallsBackWithoutSkipLocked(string $platformClass): void {
		$db = $this->connectionReporting(new $platformClass());
		// Only the SKIP LOCKED claim opens a transaction.
		$db->expects($this->never())->method('beginTransaction');
		$mapper = new TaskMapper($db, Server::get(ITimeFactory::class));

		$taskId = $this->insertScheduledTask();

		$claimed = $mapper->claimOldestScheduledTask([self::TASK_TYPE]);
		self::assertNotNull($claimed);
		self::assertSame($taskId, $claimed->getId());
		self::assertSame(Task::STATUS_RUNNING, $claimed->getStatus());
		self::assertNotNull($claimed->getStartedAt());

		self::assertNull($mapper->claimOldestScheduledTask([self::TASK_TYPE]));
	}

	/**
	 * @param class-string<AbstractPlatform> $platformClass
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('platformsWithSkipLocked')]
	public function testClaimUsesSkipLockedWhereSupported(string $platformClass): void {
		$db = $this->connectionReporting(new $platformClass());
		// The test database may not support SKIP LOCKED itself, so stop the claim as soon
		// as it enters the SKIP LOCKED path.
		$db->expects($this->once())
			->method('beginTransaction')
			->willThrowException(new \RuntimeException('SKIP LOCKED path'));
		$mapper = new TaskMapper($db, Server::get(ITimeFactory::class));

		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage('SKIP LOCKED path');
		$mapper->claimOldestScheduledTask([self::TASK_TYPE]);
	}

	/**
	 * A connection that reports the given MySQL/MariaDB platform and forwards all
	 * other calls (except transaction handling) to the real test database.
	 */
	private function connectionReporting(AbstractPlatform $platform): IDBConnection&MockObject {
		$db = $this->createMock(IDBConnection::class);
		$own = ['getDatabaseProvider', 'getDatabasePlatform', 'beginTransaction', 'commit', 'rollBack'];
		foreach ((new \ReflectionClass(IDBConnection::class))->getMethods() as $method) {
			$name = $method->getName();
			if (in_array($name, $own, true)) {
				continue;
			}
			$db->method($name)->willReturnCallback(fn (...$args) => $this->realDb->$name(...$args));
		}
		$db->method('getDatabaseProvider')->willReturn(IDBConnection::PLATFORM_MYSQL);
		$db->method('getDatabasePlatform')->willReturn($platform);
		return $db;
	}

	private function insertScheduledTask(): int {
		$task = new Task(self::TASK_TYPE, ['input' => 'Hello'], 'test', null);
		$task->setStatus(Task::STATUS_SCHEDULED);
		$entity = TaskEntity::fromPublicTask($task);
		Server::get(TaskMapper::class)->insert($entity);
		return $entity->getId();
	}

	private function deleteTestTasks(): void {
		$qb = $this->realDb->getQueryBuilder();
		$qb->delete('taskprocessing_tasks')
			->where($qb->expr()->eq('type', $qb->createNamedParameter(self::TASK_TYPE)))
			->executeStatement();
	}
}
