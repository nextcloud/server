<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Tests\Core\Migrations;

use Doctrine\DBAL\Schema\Table as DBALTable;
use OC\Core\Migrations\Version31000Date20240814184402;
use OC\DB\Schema\Table;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use Test\TestCase;

class Version31000Date20240814184402Test extends TestCase {
	private function createPreferencesTable(): Table {
		$table = new Table(new DBALTable('preferences'));
		$table->addColumn('userid', Types::STRING, ['length' => 64]);
		$table->addColumn('appid', Types::STRING, ['length' => 32]);
		$table->addColumn('configkey', Types::STRING, ['length' => 64]);
		return $table;
	}

	private function runMigration(Table $table): void {
		$schema = $this->createMock(ISchemaWrapper::class);
		$schema->method('getTable')
			->with('preferences')
			->willReturn($table);

		$migration = new Version31000Date20240814184402();
		$this->assertSame($schema, $migration->changeSchema($this->createMock(IOutput::class), fn () => $schema, []));
	}

	public function testChangeSchemaAddsColumnsAndIndexes(): void {
		$table = $this->createPreferencesTable();
		$table->addIndex(['appid', 'configkey'], 'preferences_app_key');

		$this->runMigration($table);

		foreach (['lazy', 'type', 'flags', 'indexed'] as $column) {
			$this->assertTrue($table->hasColumn($column), "Column $column is missing");
		}
		$this->assertFalse($table->hasIndex('preferences_app_key'));
		$this->assertSame(['userid', 'lazy'], $table->getIndex('prefs_uid_lazy_i')->getColumns());
		$this->assertSame(['appid', 'configkey', 'indexed', 'flags'], $table->getIndex('prefs_app_key_ind_fl_i')->getColumns());
	}

	public function testChangeSchemaWithExistingColumnsAndIndexes(): void {
		$table = $this->createPreferencesTable();
		$table->addColumn('lazy', Types::SMALLINT, ['notnull' => true, 'default' => 0, 'length' => 1, 'unsigned' => true]);
		$table->addColumn('type', Types::SMALLINT, ['notnull' => true, 'default' => 0, 'unsigned' => true]);
		$table->addColumn('flags', Types::INTEGER, ['notnull' => true, 'default' => 0, 'unsigned' => true]);
		$table->addColumn('indexed', Types::STRING, ['notnull' => false, 'default' => '', 'length' => 64]);
		$table->addIndex(['userid', 'lazy'], 'prefs_uid_lazy_i');
		$table->addIndex(['appid', 'configkey', 'indexed', 'flags'], 'prefs_app_key_ind_fl_i');

		$this->runMigration($table);

		$this->assertCount(7, $table->getColumns());
		$this->assertCount(2, $table->getIndexes());
	}
}
