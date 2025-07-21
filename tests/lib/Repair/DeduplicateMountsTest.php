<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Repair;

use OC\Repair\DeduplicateMounts;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Server;
use Test\TestCase;

/**
 * @group DB
 *
 * @see DeduplicateMounts
 */
class DeduplicateMountsTest extends TestCase {

	private DeduplicateMounts $repair;
	private IDBConnection $connection;
	private IConfig $config;

	protected function setUp(): void {
		parent::setUp();
		$this->connection = Server::get(IDBConnection::class);
		$this->deleteAllMounts();

		$this->config = $this->createMock(IConfig::class);
		$this->repair = new DeduplicateMounts($this->connection, $this->config);
	}

	protected function tearDown(): void {
		$this->deleteAllMounts();

		parent::tearDown();
	}

	protected function deleteAllMounts(): void {
		$this->connection->getQueryBuilder()->delete('mounts')->executeStatement();
	}

	public function testDeduplicateMounts(): void {
		$rows = [
			// Original mount
			[
				'storage_id' => 1,
				'root_id' => 1,
				'user_id' => 'user1',
				'mount_point' => '/user1/files/1.txt/',
			],
			// Duplicate mount 1
			[
				'storage_id' => 2,
				'root_id' => 1,
				'user_id' => 'user1',
				'mount_point' => '/user1/files/1.txt/',
			],
			// Duplicate mount 2
			[
				'storage_id' => 3,
				'root_id' => 1,
				'user_id' => 'user1',
				'mount_point' => '/user1/files/1.txt/',
			],
			// Different root_id
			[
				'storage_id' => 4,
				'root_id' => 2,
				'user_id' => 'user1',
				'mount_point' => '/user1/files/1.txt/',
			],
			// Different user_id
			[
				'storage_id' => 5,
				'root_id' => 1,
				'user_id' => 'user2',
				'mount_point' => '/user1/files/1.txt/',
			],
			// Different mount_point
			[
				'storage_id' => 6,
				'root_id' => 1,
				'user_id' => 'user1',
				'mount_point' => '/user1/files/2.txt/',
			],
		];

		$qb = $this->connection->getQueryBuilder();
		$qb->insert('mounts')
			->values([
				'storage_id' => $qb->createParameter('storage_id'),
				'root_id' => $qb->createParameter('root_id'),
				'user_id' => $qb->createParameter('user_id'),
				'mount_point' => $qb->createParameter('mount_point'),
			]);

		foreach ($rows as $row) {
			$qb
				->setParameter('storage_id', $row['storage_id'], IQueryBuilder::PARAM_INT)
				->setParameter('root_id', $row['root_id'], IQueryBuilder::PARAM_INT)
				->setParameter('user_id', $row['user_id'], IQueryBuilder::PARAM_STR)
				->setParameter('mount_point', $row['mount_point'], IQueryBuilder::PARAM_STR)
				->executeStatement();
		}

		$this->config
			->expects($this->once())
			->method('getSystemValueInt')
			->with('repair_duplicate_mounts_threshold', 10)
			->willReturn(1);

		$output = $this->createMock(IOutput::class);
		$this->repair->run($output);

		$result = $this->connection->getQueryBuilder()
			->select('storage_id', 'root_id', 'user_id', 'mount_point')
			->from('mounts')
			->orderBy('storage_id', 'ASC')
			->executeQuery();

		$this->assertEquals([
			[
				'storage_id' => 1,
				'root_id' => 1,
				'user_id' => 'user1',
				'mount_point' => '/user1/files/1.txt/',
			],
			// Duplicate mount 1 is removed
			// Duplicate mount 2 is removed
			[
				'storage_id' => 4,
				'root_id' => 2,
				'user_id' => 'user1',
				'mount_point' => '/user1/files/1.txt/',
			],
			[
				'storage_id' => 5,
				'root_id' => 1,
				'user_id' => 'user2',
				'mount_point' => '/user1/files/1.txt/',
			],
			[
				'storage_id' => 6,
				'root_id' => 1,
				'user_id' => 'user1',
				'mount_point' => '/user1/files/2.txt/',
			],
		], $result->fetchAll());

		$result->closeCursor();
	}
}
