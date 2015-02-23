<?php
/**
 * @author Bernhard Posselt <dev@bernhard-posselt.com>
 * @author Robin Appelman <icewind@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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
namespace OC\AppFramework\Db;

use \OCP\IDb;


/**
 * Small Facade for being able to inject the database connection for tests
 */
class Db implements IDb {
	/**
	 * @var \OCP\IDBConnection
	 */
	protected $connection;

	/**
	 * @param \OCP\IDBConnection $connection
	 */
	public function __construct($connection) {
		$this->connection = $connection;
	}

	/**
	 * Used to abstract the owncloud database access away
	 *
	 * @param string $sql the sql query with ? placeholder for params
	 * @param int $limit the maximum number of rows
	 * @param int $offset from which row we want to start
	 * @return \OC_DB_StatementWrapper prepared SQL query
	 */
	public function prepareQuery($sql, $limit = null, $offset = null) {
		$isManipulation = \OC_DB::isManipulation($sql);
		$statement = $this->connection->prepare($sql, $limit, $offset);
		return new \OC_DB_StatementWrapper($statement, $isManipulation);
	}


	/**
	 * Used to get the id of the just inserted element
	 *
	 * @param string $tableName the name of the table where we inserted the item
	 * @return int the id of the inserted element
	 */
	public function getInsertId($tableName) {
		return $this->connection->lastInsertId($tableName);
	}


}
