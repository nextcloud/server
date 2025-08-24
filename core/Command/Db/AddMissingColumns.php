<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Command\Db;

use OC\DB\Connection;
use OC\DB\SchemaWrapper;
use OCP\DB\Events\AddMissingColumnsEvent;
use OCP\EventDispatcher\IEventDispatcher;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class AddMissingColumns
 *
 * if you added a new lazy column to the database, this is the right place to add
 * your update routine for existing instances
 *
 * @package OC\Core\Command\Db
 */
class AddMissingColumns extends Command {
	public function __construct(
		private Connection $connection,
		private IEventDispatcher $dispatcher,
	) {
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('db:add-missing-columns')
			->setDescription('Add missing optional columns to the database tables')
			->addOption('dry-run', null, InputOption::VALUE_NONE, 'Output the SQL queries instead of running them.');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$dryRun = $input->getOption('dry-run');

		// Dispatch event so apps can also update columns if needed
		$event = new AddMissingColumnsEvent();
		$this->dispatcher->dispatchTyped($event);
		$missingColumns = $event->getMissingColumns();
		$updated = false;

		if (!empty($missingColumns)) {
			$schema = new SchemaWrapper($this->connection);

			foreach ($missingColumns as $missingColumn) {
				if ($schema->hasTable($missingColumn['tableName'])) {
					$table = $schema->getTable($missingColumn['tableName']);
					if (!$table->hasColumn($missingColumn['columnName'])) {
						$output->writeln('<info>Adding additional ' . $missingColumn['columnName'] . ' column to the ' . $missingColumn['tableName'] . ' table, this can take some time...</info>');
						$table->addColumn($missingColumn['columnName'], $missingColumn['typeName'], $missingColumn['options']);
						$sqlQueries = $this->connection->migrateToSchema($schema->getWrappedSchema(), $dryRun);
						if ($dryRun && $sqlQueries !== null) {
							$output->writeln($sqlQueries);
						}
						$updated = true;
						$output->writeln('<info>' . $missingColumn['tableName'] . ' table updated successfully.</info>');
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
