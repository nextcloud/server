<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Tests\Core\Command\Db;

use Doctrine\DBAL\Schema\Schema;
use OC\Core\Command\Db\ConvertType;
use Test\TestCase;

class ConvertTypeTest extends TestCase {
	private ConvertType $command;

	protected function setUp(): void {
		parent::setUp();
		$this->command = $this->createInstanceWithMocks(ConvertType::class);
	}

	private function addTable(Schema $schema, string $name, array $references = []): void {
		$table = $schema->createTable($name);
		$table->addColumn('id', 'integer');
		$table->setPrimaryKey(['id']);
		foreach ($references as $reference) {
			$table->addColumn($reference . '_id', 'integer');
			$table->addForeignKeyConstraint($reference, [$reference . '_id'], ['id']);
		}
	}

	public function testSortTablesByForeignKeys(): void {
		$schema = new Schema();
		$this->addTable($schema, 'oc_a', ['oc_b']);
		$this->addTable($schema, 'oc_b', ['oc_c']);
		$this->addTable($schema, 'oc_c');
		$this->addTable($schema, 'oc_d');

		$this->assertSame(
			['oc_c', 'oc_b', 'oc_a', 'oc_d'],
			$this->command->sortTablesByForeignKeys($schema, ['oc_a', 'oc_b', 'oc_c', 'oc_d']),
		);
	}

	public function testSortTablesByForeignKeysIgnoresTablesThatAreNotCopied(): void {
		$schema = new Schema();
		$this->addTable($schema, 'oc_a', ['oc_b']);
		$this->addTable($schema, 'oc_b');

		$this->assertSame(['oc_a'], $this->command->sortTablesByForeignKeys($schema, ['oc_a']));
	}

	public function testSortTablesByForeignKeysWithCircularReferences(): void {
		$schema = new Schema();
		$this->addTable($schema, 'oc_a');
		$this->addTable($schema, 'oc_b', ['oc_a']);
		$schema->getTable('oc_a')->addColumn('oc_b_id', 'integer');
		$schema->getTable('oc_a')->addForeignKeyConstraint('oc_b', ['oc_b_id'], ['id']);
		$this->addTable($schema, 'oc_self', ['oc_self']);

		$this->assertSame(
			['oc_b', 'oc_a', 'oc_self'],
			$this->command->sortTablesByForeignKeys($schema, ['oc_a', 'oc_b', 'oc_self']),
		);
	}
}
