<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Tests\Core\Migrations;

use OC\Core\Migrations\Version36000Date20261028100000;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Schema\ITable;
use OCP\Migration\IOutput;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class Version36000Date20261028100000Test extends TestCase {
	private ISchemaWrapper&MockObject $schema;
	private ITable&MockObject $table;
	private Version36000Date20261028100000 $migration;

	protected function setUp(): void {
		parent::setUp();

		$this->table = $this->createMock(ITable::class);
		$this->schema = $this->createMock(ISchemaWrapper::class);
		$this->schema->method('getTable')
			->with('bruteforce_attempts')
			->willReturn($this->table);
		$this->migration = new Version36000Date20261028100000();
	}

	private function runMigration(): ?ISchemaWrapper {
		return $this->migration->changeSchema(
			$this->createMock(IOutput::class),
			fn () => $this->schema,
			[],
		);
	}

	public function testDropsIpIndex(): void {
		$this->table->method('hasIndex')
			->with('bruteforce_attempts_ip')
			->willReturn(true);
		$this->table->expects($this->once())
			->method('dropIndex')
			->with('bruteforce_attempts_ip');

		$this->assertSame($this->schema, $this->runMigration());
	}

	public function testNoChangeWhenIndexIsAlreadyGone(): void {
		$this->table->method('hasIndex')
			->with('bruteforce_attempts_ip')
			->willReturn(false);
		$this->table->expects($this->never())
			->method('dropIndex');

		$this->assertNull($this->runMigration());
	}
}
