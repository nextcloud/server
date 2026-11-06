<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Tests\Core\Migrations;

use Doctrine\DBAL\Schema\Table as DBALTable;
use OC\Core\Migrations\Version33000Date20251023120529;
use OC\DB\Schema\Table;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use Test\TestCase;

class Version33000Date20251023120529Test extends TestCase {
	private function createPreviewLocationsTable(): Table {
		$table = new Table(new DBALTable('preview_locations'));
		$table->addColumn('bucket_name', Types::STRING, ['length' => 40]);
		$table->addColumn('object_store_name', Types::STRING, ['length' => 40]);
		return $table;
	}

	private function runMigration(Table $table): void {
		$schema = $this->createMock(ISchemaWrapper::class);
		$schema->method('hasTable')
			->with('preview_locations')
			->willReturn(true);
		$schema->method('getTable')
			->with('preview_locations')
			->willReturn($table);

		$migration = new Version33000Date20251023120529($this->createMock(IDBConnection::class));
		$this->assertSame($schema, $migration->changeSchema($this->createMock(IOutput::class), fn () => $schema, []));
	}

	public function testChangeSchemaAddsUniqueIndex(): void {
		$table = $this->createPreviewLocationsTable();

		$this->runMigration($table);

		$this->assertTrue($table->getIndex('unique_bucket_store')->isUnique());
	}

	public function testChangeSchemaWithExistingUniqueIndex(): void {
		$table = $this->createPreviewLocationsTable();
		$table->addUniqueIndex(['bucket_name', 'object_store_name'], 'unique_bucket_store');

		$this->runMigration($table);

		$this->assertCount(1, $table->getIndexes());
	}
}
