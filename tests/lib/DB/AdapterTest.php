<?php

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\DB;

use OCP\IDBConnection;
use OCP\Server;
use Test\TestCase;

class AdapterTest extends TestCase {
	private string $appId;
	private $connection;

	public function setUp(): void {
		$this->connection = Server::get(IDBConnection::class);
		$this->appId = substr(uniqid('test_db_adapter', true), 0, 32);
	}

	public function tearDown(): void {
		$qb = $this->connection->getQueryBuilder();

		$qb->delete('appconfig')
			->from('appconfig')
			->where($qb->expr()->eq('appid', $qb->createNamedParameter($this->appId)))
			->executeStatement();
	}

	public function testInsertIgnoreOnConflictDuplicate(): void {
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

	public function fetchAssociative(): void {
		$insert = $this->connection->getQueryBuilder();
		$insert->insert('appconfig')
			->values([
				'appid' => $this->appId,
				'configkey' => 'test',
				'configvalue' => '1',
			])
			->executeStatement();

		// fetch all associative
		$qb = $this->connection->getQueryBuilder();
		$result = $qb->select(['configkey', 'configvalue', 'appid'])
			->from('appconfig')
			->executeQuery();

		$rows = $result->fetchAllAssociative();
		$this->assertEquals([
			[
				'appid' => $this->appId,
				'configkey' => 'test',
				'configvalue' => '1',
			]
		], $rows);

		// fetch associative
		$qb = $this->connection->getQueryBuilder();
		$result = $qb->select(['configkey', 'configvalue', 'appid'])
			->from('appconfig')
			->executeQuery();
		$row = $result->fetchAssociative();
		$this->assertEquals([
			'appid' => $this->appId,
			'configkey' => 'test',
			'configvalue' => '1',
		], $row);

		// iterate associative
		$qb = $this->connection->getQueryBuilder();
		$result = $qb->select(['configkey', 'configvalue', 'appid'])
			->from('appconfig')
			->executeQuery();
		$row = iterator_to_array($result->iterateAssociative());
		$this->assertEquals([
			'appid' => $this->appId,
			'configkey' => 'test',
			'configvalue' => '1',
		], $row);
	}

	public function fetchNumeric(): void {
		$insert = $this->connection->getQueryBuilder();
		$insert->insert('appconfig')
			->values([
				'appid' => $this->appId,
				'configkey' => 'test',
				'configvalue' => '1',
			])
			->executeStatement();

		// fetch all associative
		$qb = $this->connection->getQueryBuilder();
		$result = $qb->select(['configkey', 'configvalue', 'appid'])
			->from('appconfig')
			->executeQuery();

		$rows = $result->fetchAllNumeric();
		$this->assertEquals([
			[
				0 => $this->appId,
				1 => 'test',
				2 => '1',
			]
		], $rows);

		// fetch associative
		$qb = $this->connection->getQueryBuilder();
		$result = $qb->select(['configkey', 'configvalue', 'appid'])
			->from('appconfig')
			->executeQuery();
		$row = $result->fetchNumeric();
		$this->assertEquals([
			0 => $this->appId,
			1 => 'test',
			2 => '1',
		], $row);

		// iterate associative
		$qb = $this->connection->getQueryBuilder();
		$result = $qb->select(['configkey', 'configvalue', 'appid'])
			->from('appconfig')
			->executeQuery();
		$row = iterator_to_array($result->iterateNumeric());
		$this->assertEquals([
			0 => $this->appId,
			1 => 'test',
			2 => '1',
		], $row);
	}

	public function fetchOne(): void {
		$insert = $this->connection->getQueryBuilder();
		$insert->insert('appconfig')
			->values([
				'appid' => $this->appId,
				'configkey' => 'test',
				'configvalue' => '1',
			])
			->executeStatement();

		// fetch all associative
		$qb = $this->connection->getQueryBuilder();
		$result = $qb->select(['configkey', 'configvalue', 'appid'])
			->from('appconfig')
			->executeQuery();

		$rows = $result->fetchFirstColumn();
		$this->assertEquals($this->appId, $rows);

		// fetch associative
		$qb = $this->connection->getQueryBuilder();
		$result = $qb->select(['configkey', 'configvalue', 'appid'])
			->from('appconfig')
			->executeQuery();
		$row = $result->fetchFirstColumn();
		$this->assertEquals($this->appId, $row);

		// iterate associative
		$qb = $this->connection->getQueryBuilder();
		$result = $qb->select(['configkey', 'configvalue', 'appid'])
			->from('appconfig')
			->executeQuery();
		$rows = iterator_to_array($result->iterateNumeric());
		$this->assertEquals([$this->appId], $rows);
	}
}
