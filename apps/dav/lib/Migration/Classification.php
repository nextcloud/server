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


namespace OCA\DAV\Migration;

use OCA\DAV\CalDAV\CalDavBackend;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class Classification implements IRepairStep {

	/** @var CalDavBackend */
	private $calDavBackend;

	/** @var IDBConnection */
	private $connection;

	/**
	 * Classification constructor.
	 *
	 * @param CalDavBackend $calDavBackend
	 * @param IDBConnection $connection
	 */
	public function __construct(CalDavBackend $calDavBackend, IDBConnection $connection) {
		$this->calDavBackend = $calDavBackend;
		$this->connection = $connection;

	}

	/**
	 * @param $calendarData
	 * @return integer
	 * @throws \Sabre\DAV\Exception\BadRequest
	 */
	protected function extractClassification($calendarData) {
		return $this->calDavBackend->getDenormalizedData($calendarData)['classification'];
	}

	/**
	 * @inheritdoc
	 */
	public function getName() {
		return 'Fix classification for calendar objects';
	}

	/**
	 * @inheritdoc
	 */
	public function run(IOutput $output) {
		$qb = $this->connection->getQueryBuilder();
		$result = $qb->select($qb->createFunction('COUNT(*)'))
			->from('calendarobjects')
			->execute();

		$max = $result->fetchColumn();
		$output->startProgress($max);

		$query = $this->connection->getQueryBuilder();
		$query->select(['id', 'calendardata', 'classification'])
			->from('calendarobjects');

		$stmt = $query->execute();

		while($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {

			$output->advance(1);

			$classification = $this->extractClassification($this->readBlob($row['calendardata']));
			$this->calDavBackend->setClassification($row['id'], $classification);
		}

		$output->finishProgress();
	}

	private function readBlob($cardData) {
		if (is_resource($cardData)) {
			return stream_get_contents($cardData);
		}

		return $cardData;
	}

}
