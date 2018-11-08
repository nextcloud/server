<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Jonny007-MKD <1-23-4-5@web.de>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
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

namespace OC\DB;

/**
 * This handles the way we use to write queries, into something that can be
 * handled by the database abstraction layer.
 */
class Adapter {

	/**
	 * @var \OC\DB\Connection $conn
	 */
	protected $conn;

	public function __construct($conn) {
		$this->conn = $conn;
	}

	/**
	 * @param string $table name
	 * @return int id of last insert statement
	 */
	public function lastInsertId($table) {
		return $this->conn->realLastInsertId($table);
	}

	/**
	 * @param string $statement that needs to be changed so the db can handle it
	 * @return string changed statement
	 */
	public function fixupStatement($statement) {
		return $statement;
	}

	/**
	 * Create an exclusive read+write lock on a table
	 *
	 * @param string $tableName
	 * @since 9.1.0
	 */
	public function lockTable($tableName) {
		$this->conn->beginTransaction();
		$this->conn->executeUpdate('LOCK TABLE `' .$tableName . '` IN EXCLUSIVE MODE');
	}

	/**
	 * Release a previous acquired lock again
	 *
	 * @since 9.1.0
	 */
	public function unlockTable() {
		$this->conn->commit();
	}

	/**
	 * Insert a row if the matching row does not exists.
	 *
	 * @param string $table The table name (will replace *PREFIX* with the actual prefix)
	 * @param array $input data that should be inserted into the table  (column name => value)
	 * @param array|null $compare List of values that should be checked for "if not exists"
	 *				If this is null or an empty array, all keys of $input will be compared
	 *				Please note: text fields (clob) must not be used in the compare array
	 * @return int number of inserted rows
	 * @throws \Doctrine\DBAL\DBALException
	 */
	public function insertIfNotExist($table, $input, array $compare = null) {
		if (empty($compare)) {
			$compare = array_keys($input);
		}

		//Search for given compare-fields
		$query_select = 'SELECT * FROM `' . $table . '` WHERE 1=1';
		foreach($compare as $key) {
			$query_select .= ' AND `' . $key . '`';
			if (is_null($input[$key])) {
				$query_select .= ' IS NULL';
			} else {
				$query_select .= ' = "' . $input[$key] .'"';
			};
		};
		$result = $this->conn->executeQuery($query_select);
		$data = $result->fetch();
		//took this from cache.php:
		//FIXME hide this HACK in the next database layer, or just use doctrine and get rid of MDB2 and PDO
		//PDO returns false, MDB2 returns null, oracle always uses MDB2, so convert null to false
		if ($data === null) {
			$data = false;
		}
		if ($data) {
			return 0; //Data already there, only empty result returned
		};

		//Do update
		$inserts = array_values($input);
		$query = 'INSERT INTO `' .$table . '` (`'
			. implode('`,`', array_keys($input))
			. '`) VALUES ('
			. str_repeat('?,', count($input)-1).'? ' // Is there a prettier alternative?
			. ')';
		return $this->conn->executeUpdate($query, $inserts);
	}
}
