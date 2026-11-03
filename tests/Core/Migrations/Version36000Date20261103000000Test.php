<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Tests\Core\Migrations;

use Doctrine\DBAL\Schema\Table as DBALTable;
use OC\Core\Migrations\Version36000Date20261103000000;
use OC\DB\Schema\Table;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use Test\TestCase;

class Version36000Date20261103000000Test extends TestCase {
	public function testAddsNullableAddressAndSkipsExistingColumn(): void {
		$table = new Table(new DBALTable('login_flow_v2'));
		$schema = $this->createMock(ISchemaWrapper::class);
		$schema->method('getTable')->with('login_flow_v2')->willReturn($table);
		$output = $this->createMock(IOutput::class);
		$migration = new Version36000Date20261103000000();

		$this->assertSame($schema, $migration->changeSchema($output, fn () => $schema, []));
		$column = $table->getColumn('client_ip');
		$this->assertFalse($column->getNotnull());
		$this->assertNull($column->getDefault());
		$this->assertSame(45, $column->getLength());
		$this->assertNull($migration->changeSchema($output, fn () => $schema, []));
	}
}
