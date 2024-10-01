<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Command\Db;

use OC\DB\Connection;
use OC\DB\SchemaWrapper;
use OCP\DB\Events\AddMissingIndicesEvent;
use OCP\EventDispatcher\IEventDispatcher;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class AddMissingIndices
 *
 * if you added any new indices to the database, this is the right place to add
 * your update routine for existing instances
 *
 * @package OC\Core\Command\Db
 */
class AddMissingIndices extends Command {
	public function __construct(
		private Connection $connection,
		private IEventDispatcher $dispatcher,
	) {
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('db:add-missing-indices')
			->setDescription('Add missing indices to the database tables')
			->addOption('dry-run', null, InputOption::VALUE_NONE, 'Output the SQL queries instead of running them.');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$dryRun = $input->getOption('dry-run');

		// Dispatch event so apps can also update indexes if needed
		$event = new AddMissingIndicesEvent();
		$this->dispatcher->dispatchTyped($event);

		$missingIndices = $event->getMissingIndices();
		$toReplaceIndices = $event->getIndicesToReplace();

		if ($missingIndices !== [] || $toReplaceIndices !== []) {
			$schema = new SchemaWrapper($this->connection);

			foreach ($missingIndices as $missingIndex) {
				if ($schema->hasTable($missingIndex['tableName'])) {
					$table = $schema->getTable($missingIndex['tableName']);
					if (!$table->hasIndex($missingIndex['indexName'])) {
						$output->writeln('<info>Adding additional ' . $missingIndex['indexName'] . ' index to the ' . $table->getName() . ' table, this can take some time...</info>');

						if ($missingIndex['dropUnnamedIndex']) {
							foreach ($table->getIndexes() as $index) {
								$columns = $index->getColumns();
								if ($columns === $missingIndex['columns']) {
									$table->dropIndex($index->getName());
								}
							}
						}

						if ($missingIndex['uniqueIndex']) {
							$table->addUniqueIndex($missingIndex['columns'], $missingIndex['indexName'], $missingIndex['options']);
						} else {
							$table->addIndex($missingIndex['columns'], $missingIndex['indexName'], [], $missingIndex['options']);
						}

						if (!$dryRun) {
							$this->connection->migrateToSchema($schema->getWrappedSchema());
						}
						$output->writeln('<info>' . $table->getName() . ' table updated successfully.</info>');
					}
				}
			}

			foreach ($toReplaceIndices as $toReplaceIndex) {
				if ($schema->hasTable($toReplaceIndex['tableName'])) {
					$table = $schema->getTable($toReplaceIndex['tableName']);

					$allOldIndicesExists = true;
					foreach ($toReplaceIndex['oldIndexNames'] as $oldIndexName) {
						if (!$table->hasIndex($oldIndexName)) {
							$allOldIndicesExists = false;
						}
					}

					if (!$allOldIndicesExists) {
						continue;
					}

					$output->writeln('<info>Adding additional ' . $toReplaceIndex['newIndexName'] . ' index to the ' . $table->getName() . ' table, this can take some time...</info>');

					if ($toReplaceIndex['uniqueIndex']) {
						$table->addUniqueIndex($toReplaceIndex['columns'], $toReplaceIndex['newIndexName'], $toReplaceIndex['options']);
					} else {
						$table->addIndex($toReplaceIndex['columns'], $toReplaceIndex['newIndexName'], [], $toReplaceIndex['options']);
					}

					if (!$dryRun) {
						$this->connection->migrateToSchema($schema->getWrappedSchema());
					}

					foreach ($toReplaceIndex['oldIndexNames'] as $oldIndexName) {
						$output->writeln('<info>Removing ' . $oldIndexName . ' index from the ' . $table->getName() . ' table</info>');
						$table->dropIndex($oldIndexName);
					}

					if (!$dryRun) {
						$this->connection->migrateToSchema($schema->getWrappedSchema());
					}
					$output->writeln('<info>' . $table->getName() . ' table updated successfully.</info>');
				}
			}

			if ($dryRun) {
				$sqlQueries = $this->connection->migrateToSchema($schema->getWrappedSchema(), $dryRun);
				if ($sqlQueries !== null) {
					$output->writeln($sqlQueries);
				}
			}
		}

		return 0;
	}
}
