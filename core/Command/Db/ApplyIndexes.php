<?php
/**
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OC\Core\Command\Db;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\SchemaDiff;
use Doctrine\DBAL\Schema\TableDiff;
use OC\DB\MDB2SchemaManager;
use OCP\IDBConnection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ApplyIndexes extends Command {
	protected function configure() {
		$this
			->setName('db:apply-indexes')
			->setDescription('Applies indexes which are marked as <only-on-create/>. This allows to run upgrades fast by applying the indexes later during live operations.');
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output) {

		/** @var Connection | IDBConnection $connection */
		$connection = \OC::$server->getDatabaseConnection();
		$schemaManager = new MDB2SchemaManager($connection);

		try {
			$files = $this->getFiles();
			$progress = new ProgressBar($output);
			$progress->start(count($files));

			foreach ($files as $file) {
				$progress->advance();
				$toSchema = $schemaManager->readSchemaFromFile($file, true);
				$migrator = $schemaManager->getMigrator();
				$diff = $migrator->getDiff($toSchema, $connection);
				$diff = $this->filterDiff($diff);
				$migrator->applyDiff($diff, $connection);
			}
			$progress->finish();
		} catch (\Exception $e) {
			$output->writeln('Failed to update database structure ('.$e.')');
		}

	}

	/**
	 * @return array
	 */
	protected function getFiles() {
		$files = [__DIR__ . '/../../../db_structure.xml'];

		$appManager = \OC::$server->getAppManager();
		$apps = $appManager->getInstalledApps();
		foreach ($apps as $app) {
			$path = \OC_App::getAppPath($app);
			if ($path === false) {
				continue;
			}
			$schema = $path . '/appinfo/database.xml';
			if (file_exists($schema)) {
				$files[]= $schema;
			}
		}

		return $files;
	}

	/**
	 * @param SchemaDiff $diff
	 * @return SchemaDiff
	 */
	private function filterDiff($diff) {
		$newDiff = new SchemaDiff();
		foreach ($diff->changedTables as $table) {
			if (count($table->addedIndexes) > 0) {
				$newTable = new TableDiff($table->name);
				$newTable->addedIndexes = $table->addedIndexes;

				$newDiff->changedTables[] = $newTable;
			}
		}
		return $newDiff;
	}
}
