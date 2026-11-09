<?php

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\DB;

use OCP\IDBConnection;
use OCP\Server;
use PHPUnit\Framework\Attributes\Group;
use Test\TestCase;

/**
 * Exercise database adapter behavior via a real IDBConnection.
 */
#[Group('DB')]
class AdapterTest extends TestCase {
	private string $appId;
	private $connection;

	#[\Override]
	public function setUp(): void {
		parent::setUp();

		$this->connection = Server::get(IDBConnection::class);
		$this->appId = substr(uniqid('test_db_adapter', true), 0, 32);
	}

	#[\Override]
	public function tearDown(): void {
		$qb = $this->connection->getQueryBuilder();

		$qb->delete('appconfig')
			->from('appconfig')
			->where($qb->expr()->eq('appid', $qb->createNamedParameter($this->appId)))
			->executeStatement();
		parent::tearDown();
	}

	public function testInsertIgnoreConflictIgnoresDuplicate(): void {
		$configKey = uniqid('key', true);
		$expected = [
			[
				'configkey' => $configKey,
				'configvalue' => '1',
			]
		];
		$result = $this->connection->insertIgnoreConflict('appconfig', [
			'appid' => $this->appId,
			'configkey' => $configKey,
			'configvalue' => '1',
		]);
		$this->assertEquals(1, $result);
		$rows = $this->getRows($configKey);
		$this->assertSame($expected, $rows);

		$result = $this->connection->insertIgnoreConflict('appconfig', [
			'appid' => $this->appId,
			'configkey' => $configKey,
			'configvalue' => '2',
		]);
		$this->assertEquals(0, $result);
		$rows = $this->getRows($configKey);
		$this->assertSame($expected, $rows);
	}

	private function getRows(string $configKey): array {
		$qb = $this->connection->getQueryBuilder();
		return $qb->select(['configkey', 'configvalue'])
			->from('appconfig')
			->where($qb->expr()->eq('appid', $qb->createNamedParameter($this->appId)))
			->andWhere($qb->expr()->eq('configkey', $qb->createNamedParameter($configKey)))
			->executeQuery()
			->fetchAllAssociative();
	}

	private function insertTestRow(): void {
		$insert = $this->connection->getQueryBuilder();
		$insert->insert('appconfig')
			->values([
				'appid' => $insert->createNamedParameter($this->appId),
				'configkey' => $insert->createNamedParameter('test'),
				'configvalue' => $insert->createNamedParameter('1'),			])
			->executeStatement();
	}

	public function testFetchAllAssociative(): void {
		$this->insertTestRow();
		$qb = $this->connection->getQueryBuilder();
		$rows = $qb->select(['appid', 'configkey', 'configvalue'])
			->from('appconfig')
			->where($qb->expr()->eq('appid', $qb->createNamedParameter($this->appId)))
			->andWhere($qb->expr()->eq('configkey', $qb->createNamedParameter('test')))
			->executeQuery()
			->fetchAllAssociative();

		$this->assertSame([[
			'appid' => $this->appId,
			'configkey' => 'test',
			'configvalue' => '1',
		]], $rows);
	}

	public function testFetchAssociative(): void {
		$this->insertTestRow();
		$qb = $this->connection->getQueryBuilder();
		$row = $qb->select(['appid', 'configkey', 'configvalue'])
			->from('appconfig')
			->where($qb->expr()->eq('appid', $qb->createNamedParameter($this->appId)))
			->andWhere($qb->expr()->eq('configkey', $qb->createNamedParameter('test')))
			->executeQuery()
			->fetchAssociative();

		$this->assertSame([
			'appid' => $this->appId,
			'configkey' => 'test',
			'configvalue' => '1',
		], $row);
	}

	public function testIterateAssociative(): void {
		$this->insertTestRow();
		$qb = $this->connection->getQueryBuilder();
		$rows = iterator_to_array($qb->select(['appid', 'configkey', 'configvalue'])
			->from('appconfig')
			->where($qb->expr()->eq('appid', $qb->createNamedParameter($this->appId)))
			->andWhere($qb->expr()->eq('configkey', $qb->createNamedParameter('test')))
			->executeQuery()
			->iterateAssociative());

		$this->assertSame([[
			'appid' => $this->appId,
			'configkey' => 'test',
			'configvalue' => '1',
		]], $rows);
	}

	public function testFetchAllNumeric(): void {
		$this->insertTestRow();
		$qb = $this->connection->getQueryBuilder();
		$rows = $qb->select(['appid', 'configkey', 'configvalue'])
			->from('appconfig')
			->where($qb->expr()->eq('appid', $qb->createNamedParameter($this->appId)))
			->andWhere($qb->expr()->eq('configkey', $qb->createNamedParameter('test')))
			->executeQuery()
			->fetchAllNumeric();

		$this->assertSame([[$this->appId, 'test', '1']], $rows);
	}

	public function testFetchNumeric(): void {
		$this->insertTestRow();
		$qb = $this->connection->getQueryBuilder();
		$row = $qb->select(['appid', 'configkey', 'configvalue'])
			->from('appconfig')
			->where($qb->expr()->eq('appid', $qb->createNamedParameter($this->appId)))
			->andWhere($qb->expr()->eq('configkey', $qb->createNamedParameter('test')))
			->executeQuery()
			->fetchNumeric();

		$this->assertSame([$this->appId, 'test', '1'], $row);
	}

	public function testIterateNumeric(): void {
		$this->insertTestRow();
		$qb = $this->connection->getQueryBuilder();
		$rows = iterator_to_array($qb->select(['appid', 'configkey', 'configvalue'])
			->from('appconfig')
			->where($qb->expr()->eq('appid', $qb->createNamedParameter($this->appId)))
			->andWhere($qb->expr()->eq('configkey', $qb->createNamedParameter('test')))
			->executeQuery()
			->iterateNumeric());

		$this->assertSame([[$this->appId, 'test', '1']], $rows);
	}

	public function testFetchFirstColumn(): void {
		$this->insertTestRow();
		$qb = $this->connection->getQueryBuilder();
		$values = $qb->select(['appid'])
			->from('appconfig')
			->where($qb->expr()->eq('appid', $qb->createNamedParameter($this->appId)))
			->andWhere($qb->expr()->eq('configkey', $qb->createNamedParameter('test')))
			->executeQuery()
			->fetchFirstColumn();

		$this->assertSame([$this->appId], $values);
	}

	public function testFetchOne(): void {
		$this->insertTestRow();
		$qb = $this->connection->getQueryBuilder();
		$value = $qb->select(['appid'])
			->from('appconfig')
			->where($qb->expr()->eq('appid', $qb->createNamedParameter($this->appId)))
			->andWhere($qb->expr()->eq('configkey', $qb->createNamedParameter('test')))
			->executeQuery()
			->fetchOne();

		$this->assertSame($this->appId, $value);
	}

	public function testFetchMethodsWithNoRows(): void {
		$createResult = function () {
			$qb = $this->connection->getQueryBuilder();

			return $qb->select(['appid', 'configkey', 'configvalue'])
				->from('appconfig')
				->where($qb->expr()->eq('appid', $qb->createNamedParameter($this->appId)))
				->andWhere($qb->expr()->eq('configkey', $qb->createNamedParameter('__no_such_adapter_test_row__')))
				->executeQuery();
		};

		$this->assertFalse($createResult()->fetchAssociative());
		$this->assertFalse($createResult()->fetchNumeric());
		$this->assertFalse($createResult()->fetchOne());

		$this->assertSame([], $createResult()->fetchAllAssociative());
		$this->assertSame([], $createResult()->fetchAllNumeric());
		$this->assertSame([], $createResult()->fetchFirstColumn());

		$this->assertSame([], iterator_to_array($createResult()->iterateAssociative()));
		$this->assertSame([], iterator_to_array($createResult()->iterateNumeric()));
	}
}
