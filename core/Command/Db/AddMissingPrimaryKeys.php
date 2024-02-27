<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2017 Bjoern Schiessle <bjoern@schiessle.org>
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
			->addOption('dry-run', null, InputOption::VALUE_NONE, "Output the SQL queries instead of running them.");
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
