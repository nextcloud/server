<?php
/**
 * @copyright Copyright (c) 2017 Joas Schilling <coding@schilljs.com>
 *
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\TwoFactorBackupCodes\Migration;

use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class CopyEntriesFromOldTable implements IRepairStep {

	/** @var IDBConnection */
	protected $connection;

	/** @var IConfig */
	protected $config;

	/**
	 * @param IDBConnection $connection
	 * @param IConfig $config
	 */
	public function __construct(IDBConnection $connection, IConfig $config) {
		$this->connection = $connection;
		$this->config = $config;
	}

	/**
	 * Returns the step's name
	 *
	 * @return string
	 * @since 9.1.0
	 */
	public function getName() {
		return 'Copy twofactor backup codes from legacy table';
	}

	/**
	 * Run repair step.
	 * Must throw exception on error.
	 *
	 * @since 9.1.0
	 * @param IOutput $output
	 * @throws \Exception in case of failure
	 */
	public function run(IOutput $output) {
		$version = $this->config->getAppValue('twofactor_backupcodes', 'installed_version', '0.0.0');
		if (version_compare($version, '1.1.1', '>=')) {
			return;
		}

		if (!$this->connection->tableExists('twofactor_backup_codes')) {
			// Legacy table does not exist
			return;
		}

		$insert = $this->connection->getQueryBuilder();
		$insert->insert('twofactor_backupcodes')
			->values([
				// Inserting with id might fail: 'id' => $insert->createParameter('id'),
				'user_id' => $insert->createParameter('user_id'),
				'code' => $insert->createParameter('code'),
				'used' => $insert->createParameter('used'),
			]);

		$query = $this->connection->getQueryBuilder();
		$query->select('*')
			->from('twofactor_backup_codes')
			->orderBy('id', 'ASC');
		$result = $query->execute();

		$output->startProgress();
		while ($row = $result->fetch()) {
			$output->advance();

			$insert
				// Inserting with id might fail: ->setParameter('id', $row['id'], IQueryBuilder::PARAM_INT)
				->setParameter('user_id', $row['user_id'], IQueryBuilder::PARAM_STR)
				->setParameter('code', $row['code'], IQueryBuilder::PARAM_STR)
				->setParameter('used', $row['used'], IQueryBuilder::PARAM_INT)
				->execute();
		}
		$output->finishProgress();

		$this->connection->dropTable('twofactor_backup_codes');
	}
}
