<?php
/**
 * @author Lukas Reschke <lukas@owncloud.com>
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
namespace OCA\Dav\Migration;

use OCP\IDBConnection;

class CalendarAdapter {

	/** @var \OCP\IDBConnection */
	protected $dbConnection;

	/** @var string */
	private $sourceCalendarTable;

	/** @var string */
	private $sourceCalObjTable;

	/**
	 * @param IDBConnection $dbConnection
	 * @param string $sourceCalendarTable
	 * @param string $sourceCalObjTable
	 */
	function __construct(IDBConnection $dbConnection,
						 $sourceCalendarTable = 'clndr_calendars',
						 $sourceCalObjTable = 'clndr_objects') {
		$this->dbConnection = $dbConnection;
		$this->sourceCalendarTable = $sourceCalendarTable;
		$this->sourceCalObjTable = $sourceCalObjTable;
	}

	/**
	 * @param string $user
	 * @param \Closure $callBack
	 */
	public function foreachCalendar($user, \Closure $callBack) {
		// get all calendars of that user
		$query = $this->dbConnection->getQueryBuilder();
		$stmt = $query->select('*')->from($this->sourceCalendarTable)
			->where($query->expr()->eq('userid', $query->createNamedParameter($user)))
			->execute();

		while($row = $stmt->fetch()) {
			$callBack($row);
		}
	}

	public function setup() {
		if (!$this->dbConnection->tableExists($this->sourceCalendarTable)) {
			throw new NothingToDoException('Calendar tables are missing');
		}
	}

	/**
	 * @param int $calendarId
	 * @param \Closure $callBack
	 */
	public function foreachCalendarObject($calendarId, \Closure $callBack) {
		$query = $this->dbConnection->getQueryBuilder();
		$stmt = $query->select('*')->from($this->sourceCalObjTable)
			->where($query->expr()->eq('calendarid', $query->createNamedParameter($calendarId)))
			->execute();

		while($row = $stmt->fetch()) {
			$callBack($row);
		}
	}

	/**
	 * @param int $addressBookId
	 * @return array
	 */
	public function getShares($addressBookId) {
		$query = $this->dbConnection->getQueryBuilder();
		$shares = $query->select('*')->from('share')
			->where($query->expr()->eq('item_source', $query->createNamedParameter($addressBookId)))
			->andWhere($query->expr()->eq('item_type', $query->expr()->literal('calendar')))
			->andWhere($query->expr()->in('share_type', [ $query->expr()->literal(0), $query->expr()->literal(1)]))
			->execute()
			->fetchAll();

		return $shares;
	}
}
