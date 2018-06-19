<?php
/**
 * @copyright Copyright (c) 2017 Bjoern Schiessle <bjoern@schiessle.org>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */


namespace OC\Core\Command\Db;

use OC\DB\SchemaWrapper;
use OCP\IDBConnection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Class AddMissingIndices
 *
 * if you added any new indices to the database, this is the right place to add
 * it your update routine for existing instances
 *
 * @package OC\Core\Command\Db
 */
class AddMissingIndices extends Command {

	/** @var IDBConnection */
	private $connection;

	/** @var EventDispatcherInterface */
	private $dispatcher;

	public function __construct(IDBConnection $connection, EventDispatcherInterface $dispatcher) {
		parent::__construct();

		$this->connection = $connection;
		$this->dispatcher = $dispatcher;
	}

	protected function configure() {
		$this
			->setName('db:add-missing-indices')
			->setDescription('Add missing indices to the database tables');
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$this->addShareTableIndicies($output);

		// Dispatch event so apps can also update indexes if needed
		$event = new GenericEvent($output);
		$this->dispatcher->dispatch(IDBConnection::ADD_MISSING_INDEXES_EVENT, $event);
	}

	/**
	 * add missing indices to the share table
	 *
	 * @param OutputInterface $output
	 * @throws \Doctrine\DBAL\Schema\SchemaException
	 */
	private function addShareTableIndicies(OutputInterface $output) {

		$output->writeln('<info>Check indices of the share table.</info>');

		$schema = new SchemaWrapper($this->connection);
		$updated = false;

		if ($schema->hasTable('share')) {
			$table = $schema->getTable('share');
			if (!$table->hasIndex('share_with_index')) {
				$output->writeln('<info>Adding additional share_with index to the share table, this can take some time...</info>');
				$table->addIndex(['share_with'], 'share_with_index');
				$this->connection->migrateToSchema($schema->getWrappedSchema());
				$updated = true;
				$output->writeln('<info>Share table updated successfully.</info>');
			}

			if (!$table->hasIndex('parent_index')) {
				$output->writeln('<info>Adding additional parent index to the share table, this can take some time...</info>');
				$table->addIndex(['parent'], 'parent_index');
				$this->connection->migrateToSchema($schema->getWrappedSchema());
				$updated = true;
				$output->writeln('<info>Share table updated successfully.</info>');
			}
		}

		if (!$updated) {
			$output->writeln('<info>Done.</info>');
		}
	}
}
