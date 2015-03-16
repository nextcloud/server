<?php
/**
 * Copyright (c) 2013 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */


namespace OC\DB;

class AdapterSqlite extends Adapter {
	public function fixupStatement($statement) {
		$statement = preg_replace('/`(\w+)` ILIKE \?/', 'LOWER($1) LIKE LOWER(?)', $statement);
		$statement = str_replace( '`', '"', $statement );
		$statement = str_ireplace( 'NOW()', 'datetime(\'now\')', $statement );
		$statement = str_ireplace( 'UNIX_TIMESTAMP()', 'strftime(\'%s\',\'now\')', $statement );
		return $statement;
	}

	/**
	 * Insert a row if the matching row does not exists.
	 *
	 * @param string $table The table name (will replace *PREFIX* with the actual prefix)
	 * @param array $input data that should be inserted into the table  (column name => value)
	 * @param array|null $compare List of values that should be checked for "if not exists"
	 *				If this is null or an empty array, all keys of $input will be compared
	 * @return int number of inserted rows
	 * @throws \Doctrine\DBAL\DBALException
	 */
	public function insertIfNotExist($table, $input, array $compare = null) {
		if (empty($compare)) {
			$compare = array_keys($input);
		}
		$fieldList = '`' . implode('`,`', array_keys($input)) . '`';
		$query = "INSERT INTO `$table` ($fieldList) SELECT "
			. str_repeat('?,', count($input)-1).'? '
			. " WHERE NOT EXISTS (SELECT 1 FROM `$table` WHERE ";

		$inserts = array_values($input);
		foreach($compare as $key) {
			$query .= '`' . $key . '`';
			if (is_null($input[$key])) {
				$query .= ' IS NULL AND ';
			} else {
				$inserts[] = $input[$key];
				$query .= ' = ? AND ';
			}
		}
		$query = substr($query, 0, strlen($query) - 5);
		$query .= ')';

		return $this->conn->executeUpdate($query, $inserts);
	}
}
