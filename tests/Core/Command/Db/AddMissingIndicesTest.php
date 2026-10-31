<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Tests\Core\Command\Db;

use Doctrine\DBAL\Schema\Schema;
use OC\Core\Command\Db\AddMissingIndices;
use OC\DB\Connection;
use OCP\DB\Events\AddMissingIndicesEvent;
use OCP\EventDispatcher\IEventDispatcher;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Test\TestCase;

class AddMissingIndicesTest extends TestCase {
	private Connection&MockObject $connection;
	private AddMissingIndices $command;

	protected function setUp(): void {
		parent::setUp();

		$schema = new Schema();
		$table = $schema->createTable('oc_share');
		$table->addColumn('id', 'integer');
		$table->addColumn('share_type', 'smallint');
		$table->addIndex(['id'], 'share_old_idx');

		$this->connection = $this->createMock(Connection::class);
		$this->connection->method('getPrefix')->willReturn('oc_');
		$this->connection->method('createSchema')->willReturn($schema);

		$dispatcher = $this->createMock(IEventDispatcher::class);
		$dispatcher->method('dispatchTyped')
			->willReturnCallback(function (AddMissingIndicesEvent $event): void {
				$event->addMissingIndex('share', 'share_type_idx', ['share_type']);
				$event->replaceIndex('share', ['share_old_idx'], 'share_id_idx', ['id'], false);
			});

		$this->command = new AddMissingIndices($this->connection, $dispatcher);
	}

	private function runCommand(array $options): string {
		$output = new BufferedOutput();
		$this->command->run(new ArrayInput($options), $output);
		return $output->fetch();
	}

	public function testDryRunDoesNotClaimChanges(): void {
		$this->connection->expects($this->once())
			->method('migrateToSchema')
			->with($this->isInstanceOf(Schema::class), true)
			->willReturn('CREATE INDEX share_type_idx ON oc_share (share_type);');

		$output = $this->runCommand(['--dry-run' => true]);

		$this->assertStringContainsString('Missing share_type_idx index on the oc_share table', $output);
		$this->assertStringContainsString('Missing share_id_idx index on the oc_share table', $output);
		$this->assertStringContainsString('Index share_old_idx on the oc_share table would be removed', $output);
		$this->assertStringContainsString('CREATE INDEX share_type_idx ON oc_share (share_type);', $output);
		$this->assertStringNotContainsString('this can take some time', $output);
		$this->assertStringNotContainsString('updated successfully', $output);
	}

	public function testRunReportsChanges(): void {
		$this->connection->expects($this->exactly(3))
			->method('migrateToSchema')
			->with($this->isInstanceOf(Schema::class));

		$output = $this->runCommand([]);

		$this->assertStringContainsString('Adding additional share_type_idx index to the oc_share table, this can take some time...', $output);
		$this->assertStringContainsString('Removing share_old_idx index from the oc_share table', $output);
		$this->assertSame(2, substr_count($output, 'oc_share table updated successfully.'));
	}
}
