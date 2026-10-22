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

#[\PHPUnit\Framework\Attributes\Group('DB')]
class SharedQueryBuilderTest extends TestCase {
	private IDBConnection $connection;
	private AutoIncrementHandler $autoIncrementHandler;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

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
				new ShardDefinition($table, $primaryColumn, [], $shardColumn, new RoundRobinShardMapper(), $companionTables, [], 0, 0),
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

	#[\PHPUnit\Framework\Attributes\DoesNotPerformAssertions]
	public function testValidateWithShardKey(): void {
		$query = $this->getQueryBuilder('filecache', 'storage', 'fileid');
		$query->select('fileid', 'path')
			->from('filecache')
			->where($query->expr()->eq('storage', $query->createNamedParameter(10)));

		$query->validate();
	}

	#[\PHPUnit\Framework\Attributes\DoesNotPerformAssertions]
	public function testValidateWithPrimaryKey(): void {
		$query = $this->getQueryBuilder('filecache', 'storage', 'fileid');
		$query->select('fileid', 'path')
			->from('filecache')
			->where($query->expr()->in('fileid', $query->createNamedParameter([10, 11], IQueryBuilder::PARAM_INT)));

		$query->validate();
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

	public function testAddValuesOnShardedTableInsertsPerShard(): void {
		$usedShards = [];
		$shardConnectionManager = $this->createMock(ShardConnectionManager::class);
		$shardConnectionManager->method('getConnection')
			->willReturnCallback(function (ShardDefinition $definition, int $shard) use (&$usedShards) {
				$usedShards[] = $shard;
				return $this->connection;
			});
		$query = new ShardedQueryBuilder(
			$this->connection->getQueryBuilder(),
			[
				new ShardDefinition('filecache_extended', 'fileid', [], 'fileid', new RoundRobinShardMapper(), [], [[], []], 0, 0),
			],
			$shardConnectionManager,
			$this->autoIncrementHandler,
		);

		$fileIds = [9990001, 9990002, 9990003];
		$query->insert('filecache_extended');
		foreach ($fileIds as $fileId) {
			$query->addValues([
				'fileid' => $query->createNamedParameter($fileId, IQueryBuilder::PARAM_INT),
				'upload_time' => $query->createNamedParameter($fileId, IQueryBuilder::PARAM_INT),
			]);
		}

		try {
			$this->assertSame(3, $query->executeStatement());
			$this->assertSame([1, 0, 1], $usedShards);

			$select = $this->connection->getQueryBuilder();
			$result = $select->select('fileid', 'upload_time')
				->from('filecache_extended')
				->where($select->expr()->in('fileid', $select->createNamedParameter($fileIds, IQueryBuilder::PARAM_INT_ARRAY)))
				->orderBy('fileid')
				->executeQuery();
			$this->assertEquals(array_map(static fn (int $id) => ['fileid' => $id, 'upload_time' => $id], $fileIds), $result->fetchAllAssociative());
			$result->closeCursor();
		} finally {
			$delete = $this->connection->getQueryBuilder();
			$delete->delete('filecache_extended')
				->where($delete->expr()->in('fileid', $delete->createNamedParameter($fileIds, IQueryBuilder::PARAM_INT_ARRAY)))
				->executeStatement();
		}
	}

	public function testAddValuesOnNonShardedTable(): void {
		$query = $this->getQueryBuilder('filecache', 'storage', 'fileid');
		$query->insert('appconfig')
			->addValues(['configkey' => $query->createNamedParameter('a')])
			->addValues(['configkey' => $query->createNamedParameter('b')]);

		$this->assertMatchesRegularExpression('/^INSERT (INTO|ALL)/', $query->getSQL());
		$this->assertSame(2, substr_count($query->getSQL(), ':dcValue'));
	}

	#[\PHPUnit\Framework\Attributes\DoesNotPerformAssertions]
	public function testValidateNonSharedTable(): void {
		$query = $this->getQueryBuilder('filecache', 'storage', 'fileid');
		$query->select('configvalue')
			->from('appconfig')
			->where($query->expr()->eq('configkey', $query->createNamedParameter('test')));

		$query->validate();
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
