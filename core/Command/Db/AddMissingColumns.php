<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2020 Joas Schilling <coding@schilljs.com>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
			->addOption('dry-run', null, InputOption::VALUE_NONE, "Output the SQL queries instead of running them.");
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
