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

class AddIndexToShareTable extends Command {

	/** @var IDBConnection */
	private $connection;

	/**
	 * @param IDBConnection $connection
	 */
	public function __construct(IDBConnection $connection) {
		$this->connection = $connection;
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('db:add-index-to-share-table')
			->setDescription('Add a index to share_with at the share table to increase performance');
	}

	protected function execute(InputInterface $input, OutputInterface $output) {

		$schema = new SchemaWrapper($this->connection);
		$updated = false;

		if ($schema->hasTable("share")) {
			$table = $schema->getTable("share");
			if (!$table->hasIndex('share_with_index')) {
				$output->writeln('<info>Adding additional index to the share table, this can take some time...</info>');
				$table->addIndex(['share_with'], 'share_with_index');
				$this->connection->migrateToSchema($schema->getWrappedSchema());
				$updated = true;
				$output->writeln('<info>Share table updated successfully.</info>');
			}
		}

		if (!$updated) {
			$output->writeln('<info>All index already existed, nothing to do.</info>');
		}

		return 0;

	}
}
