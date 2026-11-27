<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Repair;

use OC\Repair\RemoveInvalidSystemTagMappings;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Server;

/**
 * @see \OC\Repair\RemoveInvalidSystemTagMappings
 */
#[\PHPUnit\Framework\Attributes\Group('DB')]
class RemoveInvalidSystemTagMappingsTest extends \Test\TestCase {
	private RemoveInvalidSystemTagMappings $repair;
	private IDBConnection $connection;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->connection = Server::get(IDBConnection::class);
		$this->repair = new RemoveInvalidSystemTagMappings($this->connection);

		$this->cleanUpTable();
	}

	#[\Override]
	protected function tearDown(): void {
		$this->cleanUpTable();
		parent::tearDown();
	}

	private function cleanUpTable(): void {
		$this->connection->getQueryBuilder()->delete('systemtag_object_mapping')->executeStatement();
	}

	private function addMapping(string $objectId, int|string $tagId): void {
		$query = $this->connection->getQueryBuilder();
		$query->insert('systemtag_object_mapping')
			->values([
				'objectid' => $query->createNamedParameter($objectId),
				'objecttype' => $query->createNamedParameter('files'),
				'systemtagid' => $query->createNamedParameter($tagId),
			])
			->executeStatement();
	}

	/**
	 * @return list<array{objectid: string, systemtagid: string}>
	 */
	private function getMappings(): array {
		$query = $this->connection->getQueryBuilder();
		$result = $query->select('objectid', 'systemtagid')
			->from('systemtag_object_mapping')
			->orderBy('objectid')
			->addOrderBy('systemtagid')
			->executeQuery();
		$mappings = [];
		while ($row = $result->fetchAssociative()) {
			$mappings[] = ['objectid' => (string)$row['objectid'], 'systemtagid' => (string)$row['systemtagid']];
		}
		$result->closeCursor();
		return $mappings;
	}

	public function testKeepsValidMappings(): void {
		$this->addMapping('1', 1);
		$this->addMapping('1', 2);
		$this->addMapping('2', 1);

		$output = $this->createMock(IOutput::class);
		$output->expects($this->never())->method('info');
		$this->repair->run($output);

		$this->assertSame([
			['objectid' => '1', 'systemtagid' => '1'],
			['objectid' => '1', 'systemtagid' => '2'],
			['objectid' => '2', 'systemtagid' => '1'],
		], $this->getMappings());
	}

	public function testRemovesMappingsWithInvalidTagId(): void {
		if ($this->connection->getDatabaseProvider() !== IDBConnection::PLATFORM_SQLITE) {
			$this->markTestSkipped('Only SQLite stores a tag id that is not an integer');
		}

		$this->addMapping('1', 1);
		$this->addMapping('1', '1.25');
		$this->addMapping('1', '1.5');
		$this->addMapping('2', '7.5');

		$output = $this->createMock(IOutput::class);
		$output->expects($this->once())->method('info');
		$this->repair->run($output);

		$this->assertSame([
			['objectid' => '1', 'systemtagid' => '1'],
		], $this->getMappings());
	}
}
