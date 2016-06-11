<?php
/**
 * @author Roeland Jago Douma <rullzer@owncloud.com>
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
namespace OC\Repair;

use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

/**
 * Class RemoveOldShares
 *
 * @package OC\Repair
 */
class RemoveOldShares implements IRepairStep {

	/** @var IDBConnection */
	protected $connection;

	/**
	 * RemoveOldCalendarShares constructor.
	 *
	 * @param IDBConnection $db
	 */
	public function __construct(IDBConnection $connection) {
		$this->connection = $connection;
	}

	/**
	 * @return string
	 */
	public function getName() {
		return 'Remove old (< 9.0) calendar/contact shares';
	}

	/**
	 * @param IOutput $output
	 */
	public function run(IOutput $output) {
		$output->startProgress(4);

		$this->removeCalendarShares($output);
		$this->removeContactShares($output);

		$output->finishProgress();
	}

	/**
	 * @param IOutput $output
	 */
	private function removeCalendarShares(IOutput $output) {
		$qb = $this->connection->getQueryBuilder();
		$qb->delete('share')
			->where($qb->expr()->eq('item_type', $qb->createNamedParameter('calendar')));
		$qb->execute();

		$output->advance();

		$qb = $this->connection->getQueryBuilder();
		$qb->delete('share')
			->where($qb->expr()->eq('item_type', $qb->createNamedParameter('event')));
		$qb->execute();

		$output->advance();
	}

	/**
	 * @param IOutput $output
	 */
	private function removeContactShares(IOutput $output) {
		$qb = $this->connection->getQueryBuilder();
		$qb->delete('share')
			->where($qb->expr()->eq('item_type', $qb->createNamedParameter('contact')));
		$qb->execute();

		$output->advance();

		$qb = $this->connection->getQueryBuilder();
		$qb->delete('share')
			->where($qb->expr()->eq('item_type', $qb->createNamedParameter('addressbook')));
		$qb->execute();

		$output->advance();
	}
}

