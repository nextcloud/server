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
use OCP\IDBConnection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Class AddMissingColumns
 *
 * if you added a new lazy column to the database, this is the right place to add
 * your update routine for existing instances
 *
 * @package OC\Core\Command\Db
 */
class AddMissingColumns extends Command {
	private Connection $connection;
	private EventDispatcherInterface $dispatcher;

	public function __construct(Connection $connection, EventDispatcherInterface $dispatcher) {
		parent::__construct();

		$this->connection = $connection;
		$this->dispatcher = $dispatcher;
	}

	protected function configure() {
		$this
			->setName('db:add-missing-columns')
			->setDescription('Add missing optional columns to the database tables')
			->addOption('dry-run', null, InputOption::VALUE_NONE, "Output the SQL queries instead of running them.");
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$this->addCoreColumns($output, $input->getOption('dry-run'));

		// Dispatch event so apps can also update columns if needed
		$event = new GenericEvent($output);
		$this->dispatcher->dispatch(IDBConnection::ADD_MISSING_COLUMNS_EVENT, $event);
		return 0;
	}

	/**
	 * add missing indices to the share table
	 *
	 * @param OutputInterface $output
	 * @param bool $dryRun If true, will return the sql queries instead of running them.
	 * @throws \Doctrine\DBAL\Schema\SchemaException
	 */
	private function addCoreColumns(OutputInterface $output, bool $dryRun): void {
		$output->writeln('<info>Check columns of the comments table.</info>');

		$schema = new SchemaWrapper($this->connection);
		$updated = false;

		if ($schema->hasTable('comments')) {
			$table = $schema->getTable('comments');
			if (!$table->hasColumn('reference_id')) {
				$output->writeln('<info>Adding additional reference_id column to the comments table, this can take some time...</info>');
				$table->addColumn('reference_id', 'string', [
					'notnull' => false,
					'length' => 64,
				]);
				$sqlQueries = $this->connection->migrateToSchema($schema->getWrappedSchema(), $dryRun);
				if ($dryRun && $sqlQueries !== null) {
					$output->writeln($sqlQueries);
				}
				$updated = true;
				$output->writeln('<info>Comments table updated successfully.</info>');
			}
		}

		if (!$updated) {
			$output->writeln('<info>Done.</info>');
		}
	}
}
