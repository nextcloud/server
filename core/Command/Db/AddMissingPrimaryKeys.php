<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Command\Db;

use OC\DB\Connection;
use OC\DB\SchemaWrapper;
use OCP\DB\Events\AddMissingPrimaryKeyEvent;
use OCP\EventDispatcher\IEventDispatcher;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class AddMissingPrimaryKeys
 *
 * if you added primary keys to the database, this is the right place to add
 * your update routine for existing instances
 *
 * @package OC\Core\Command\Db
 */
class AddMissingPrimaryKeys extends Command {
	public function __construct(
		private Connection $connection,
		private IEventDispatcher $dispatcher,
	) {
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('db:add-missing-primary-keys')
			->setDescription('Add missing primary keys to the database tables')
			->addOption('dry-run', null, InputOption::VALUE_NONE, 'Output the SQL queries instead of running them.');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$dryRun = $input->getOption('dry-run');

		// Dispatch event so apps can also update indexes if needed
		$event = new AddMissingPrimaryKeyEvent();
		$this->dispatcher->dispatchTyped($event);
		$missingKeys = $event->getMissingPrimaryKeys();
		$updated = false;

		if (!empty($missingKeys)) {
			$schema = new SchemaWrapper($this->connection);

			foreach ($missingKeys as $missingKey) {
				if ($schema->hasTable($missingKey['tableName'])) {
					$table = $schema->getTable($missingKey['tableName']);
					if (!$table->hasPrimaryKey()) {
						$output->writeln('<info>Adding primary key to the ' . $missingKey['tableName'] . ' table, this can take some time...</info>');
						$table->setPrimaryKey($missingKey['columns'], $missingKey['primaryKeyName']);

						if ($missingKey['formerIndex'] && $table->hasIndex($missingKey['formerIndex'])) {
							$table->dropIndex($missingKey['formerIndex']);
						}

						$sqlQueries = $this->connection->migrateToSchema($schema->getWrappedSchema(), $dryRun);
						if ($dryRun && $sqlQueries !== null) {
							$output->writeln($sqlQueries);
						}

						$updated = true;
						$output->writeln('<info>' . $missingKey['tableName'] . ' table updated successfully.</info>');
					}
				}
			}
		}

		if (!$updated) {
			$output->writeln('<info>Done.</info>');
		}

		return 0;
	}
}
