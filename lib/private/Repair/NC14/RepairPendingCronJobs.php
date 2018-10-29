<?php
/**
 * @copyright Copyright (c) 2018 Morris Jobke <hey@morrisjobke.de>
 *
 * @author Morris Jobke <hey@morrisjobke.de>
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

namespace OC\Repair\NC14;

use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class RepairPendingCronJobs implements IRepairStep {
	const MAX_ROWS = 1000;

	/** @var IDBConnection */
	private $connection;
	/** @var IConfig */
	private $config;

	public function __construct(IDBConnection $connection, IConfig $config) {
		$this->connection = $connection;
		$this->config = $config;
	}


	public function getName() {
		return 'Repair pending cron jobs';
	}

	private function shouldRun() {
		$versionFromBeforeUpdate = $this->config->getSystemValue('version', '0.0.0');

		return version_compare($versionFromBeforeUpdate, '14.0.0.9', '<');
	}

	/**
	 * @suppress SqlInjectionChecker
	 */
	private function repair() {
		$reset = $this->connection->getQueryBuilder();
		$reset->update('jobs')
			->set('reserved_at', $reset->expr()->literal(0, IQueryBuilder::PARAM_INT))
			->where($reset->expr()->neq('reserved_at', $reset->expr()->literal(0, IQueryBuilder::PARAM_INT)));

		return $reset->execute();
	}

	public function run(IOutput $output) {
		if ($this->shouldRun()) {
			$count = $this->repair();

			$output->info('Repaired ' . $count . ' pending cron job(s).');
		} else {
			$output->info('No need to repair pending cron jobs.');
		}
	}
}
