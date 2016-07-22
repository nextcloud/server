<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
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

namespace OC\Repair;

use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class FillETags implements IRepairStep {

	/** @var \OCP\IDBConnection */
	protected $connection;

	/**
	 * @param \OCP\IDBConnection $connection
	 */
	public function __construct($connection) {
		$this->connection = $connection;
	}

	public function getName() {
		return 'Generate ETags for file where no ETag is present.';
	}

	public function run(IOutput $output) {
		$qb = $this->connection->getQueryBuilder();
		$qb->update('filecache')
			->set('etag', $qb->expr()->literal('xxx'))
			->where($qb->expr()->eq('etag', $qb->expr()->literal('')))
			->orWhere($qb->expr()->isNull('etag'));

		$result = $qb->execute();
		$output->info("ETags have been fixed for $result files/folders.");
	}
}

