<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Tests\Core\Migrations;

use Doctrine\DBAL\Schema\Table as DBALTable;
use OC\Core\Migrations\Version30000Date20240815080800;
use OC\DB\Schema\Table;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use Test\TestCase;

class Version30000Date20240815080800Test extends TestCase {
	private function runMigration(Table $table): void {
		$schema = $this->createMock(ISchemaWrapper::class);
		$schema->method('getTable')
			->with('webauthn')
			->willReturn($table);

		$migration = new Version30000Date20240815080800();
		$this->assertSame($schema, $migration->changeSchema($this->createMock(IOutput::class), fn () => $schema, []));
	}

	public function testChangeSchemaAddsColumn(): void {
		$table = new Table(new DBALTable('webauthn'));

		$this->runMigration($table);

		$this->assertTrue($table->hasColumn('user_verification'));
	}

	public function testChangeSchemaWithExistingColumn(): void {
		$table = new Table(new DBALTable('webauthn'));
		$table->addColumn('user_verification', Types::BOOLEAN, ['notnull' => false, 'default' => false]);

		$this->runMigration($table);

		$this->assertCount(1, $table->getColumns());
	}
}
