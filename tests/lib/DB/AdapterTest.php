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
		$this->appId = uniqid('test_db_adapter', true);
	}

	public function tearDown(): void {
		$qb = $this->connection->getQueryBuilder();

		$qb->delete('appconfig')
			->from('appconfig')
			->where($qb->expr()->eq('appid', $qb->createNamedParameter($this->appId)))
			->execute();
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
			->execute()
			->fetchAll();
	}
}
