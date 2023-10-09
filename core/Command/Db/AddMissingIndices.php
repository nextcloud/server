<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2017 Bjoern Schiessle <bjoern@schiessle.org>
 *
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Mario Danic <mario@lovelyhq.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Citharel <nextcloud@tcit.fr>
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
			->addOption('dry-run', null, InputOption::VALUE_NONE, "Output the SQL queries instead of running them.");
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$dryRun = $input->getOption('dry-run');

		// Dispatch event so apps can also update indexes if needed
		$event = new AddMissingIndicesEvent();
		$this->dispatcher->dispatchTyped($event);

		$missingIndices = $event->getMissingIndices();
		if ($missingIndices !== []) {
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


						$sqlQueries = $this->connection->migrateToSchema($schema->getWrappedSchema(), $dryRun);
						if ($dryRun && $sqlQueries !== null) {
							$output->writeln($sqlQueries);
						}
						$output->writeln('<info>' . $table->getName() . ' table updated successfully.</info>');
					}
				}
			}
		}

		return 0;
	}
}
