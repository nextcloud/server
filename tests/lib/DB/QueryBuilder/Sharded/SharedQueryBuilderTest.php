<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Robin Appelman <robin@icewind.nl>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\DB\QueryBuilder\Sharded;

use OC\DB\QueryBuilder\Sharded\AutoIncrementHandler;
use OC\DB\QueryBuilder\Sharded\InvalidShardedQueryException;
use OC\DB\QueryBuilder\Sharded\RoundRobinShardMapper;
use OC\DB\QueryBuilder\Sharded\ShardConnectionManager;
use OC\DB\QueryBuilder\Sharded\ShardDefinition;
use OC\DB\QueryBuilder\Sharded\ShardedQueryBuilder;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\Server;
use Test\TestCase;

/**
 * @group DB
 */
class SharedQueryBuilderTest extends TestCase {
	private IDBConnection $connection;
	private AutoIncrementHandler $autoIncrementHandler;

	protected function setUp(): void {
		if (PHP_INT_SIZE < 8) {
			$this->markTestSkipped('Test requires 64bit');
		}
		$this->connection = Server::get(IDBConnection::class);
		$this->autoIncrementHandler = Server::get(AutoIncrementHandler::class);
	}


	private function getQueryBuilder(string $table, string $shardColumn, string $primaryColumn, array $companionTables = []): ShardedQueryBuilder {
		return new ShardedQueryBuilder(
			$this->connection->getQueryBuilder(),
			[
				new ShardDefinition($table, $primaryColumn, [], $shardColumn, new RoundRobinShardMapper(), $companionTables, []),
			],
			$this->createMock(ShardConnectionManager::class),
			$this->autoIncrementHandler,
		);
	}

	public function testGetShardKeySingleParam(): void {
		$query = $this->getQueryBuilder('filecache', 'storage', 'fileid');
		$query->select('fileid', 'path')
			->from('filecache')
			->where($query->expr()->eq('storage', $query->createNamedParameter(10, IQueryBuilder::PARAM_INT)));

		$this->assertEquals([], $query->getPrimaryKeys());
		$this->assertEquals([10], $query->getShardKeys());
	}

	public function testGetPrimaryKeyParam(): void {
		$query = $this->getQueryBuilder('filecache', 'storage', 'fileid');
		$query->select('fileid', 'path')
			->from('filecache')
			->where($query->expr()->in('fileid', $query->createNamedParameter([10, 11], IQueryBuilder::PARAM_INT)));

		$this->assertEquals([10, 11], $query->getPrimaryKeys());
		$this->assertEquals([], $query->getShardKeys());
	}

	public function testValidateWithShardKey(): void {
		$query = $this->getQueryBuilder('filecache', 'storage', 'fileid');
		$query->select('fileid', 'path')
			->from('filecache')
			->where($query->expr()->eq('storage', $query->createNamedParameter(10)));

		$query->validate();
		$this->assertTrue(true);
	}

	public function testValidateWithPrimaryKey(): void {
		$query = $this->getQueryBuilder('filecache', 'storage', 'fileid');
		$query->select('fileid', 'path')
			->from('filecache')
			->where($query->expr()->in('fileid', $query->createNamedParameter([10, 11], IQueryBuilder::PARAM_INT)));

		$query->validate();
		$this->assertTrue(true);
	}

	public function testValidateWithNoKey(): void {
		$query = $this->getQueryBuilder('filecache', 'storage', 'fileid');
		$query->select('fileid', 'path')
			->from('filecache')
			->where($query->expr()->lt('size', $query->createNamedParameter(0)));

		$this->expectException(InvalidShardedQueryException::class);
		$query->validate();
		$this->fail('exception expected');
	}

	public function testValidateNonSharedTable(): void {
		$query = $this->getQueryBuilder('filecache', 'storage', 'fileid');
		$query->select('configvalue')
			->from('appconfig')
			->where($query->expr()->eq('configkey', $query->createNamedParameter('test')));

		$query->validate();
		$this->assertTrue(true);
	}

	public function testGetShardKeyMultipleSingleParam(): void {
		$query = $this->getQueryBuilder('filecache', 'storage', 'fileid');
		$query->select('fileid', 'path')
			->from('filecache')
			->where($query->expr()->andX(
				$query->expr()->gt('mtime', $query->createNamedParameter(0), IQueryBuilder::PARAM_INT),
				$query->expr()->orX(
					$query->expr()->eq('storage', $query->createNamedParameter(10, IQueryBuilder::PARAM_INT)),
					$query->expr()->andX(
						$query->expr()->eq('storage', $query->createNamedParameter(11, IQueryBuilder::PARAM_INT)),
						$query->expr()->like('path', $query->createNamedParameter('foo/%'))
					)
				)
			));

		$this->assertEquals([], $query->getPrimaryKeys());
		$this->assertEquals([10, 11], $query->getShardKeys());
	}
}
