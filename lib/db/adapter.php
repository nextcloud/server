<?php
/**
 * Copyright (c) 2013 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\DB;

class Adapter {
	protected $conn;

	public function __construct($conn) {
		$this->conn = $conn;
	}

	public function lastInsertId($table) {
		return $this->conn->realLastInsertId($table);
	}

	public function fixupStatement($statement) {
		return $statement;
	}

	public function insertIfNotExist($table, $input) {
		$query = 'INSERT INTO `' .$table . '` (`'
			. implode('`,`', array_keys($input)) . '`) SELECT '
			. str_repeat('?,', count($input)-1).'? ' // Is there a prettier alternative?
			. 'FROM `' . $table . '` WHERE ';

		foreach($input as $key => $value) {
			$query .= '`' . $key . '` = ? AND ';
		}
		$query = substr($query, 0, strlen($query) - 5);
		$query .= ' HAVING COUNT(*) = 0';
		$inserts = array_values($input);
		$inserts = array_merge($inserts, $inserts);

		try {
			$statement = $this->conn->prepare($query);
			$result = $statement->execute($inserts);
		} catch(\Doctrine\DBAL\DBALException $e) {
			$entry = 'DB Error: "'.$e->getMessage() . '"<br />';
			$entry .= 'Offending command was: ' . $query.'<br />';
			OC_Log::write('core', $entry, OC_Log::FATAL);
			error_log('DB error: ' . $entry);
			OC_Template::printErrorPage( $entry );
		}

		return $result;
	}
}
