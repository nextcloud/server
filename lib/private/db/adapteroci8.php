<?php
/**
 * Copyright (c) 2013 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */


namespace OC\DB;

class AdapterOCI8 extends Adapter {
	public function lastInsertId($table) {
		if ($table !== null) {
			$suffix = '_SEQ';
			$table = '"' . $table . $suffix . '"';
		}
		return $this->conn->realLastInsertId($table);
	}

	const UNIX_TIMESTAMP_REPLACEMENT = "(cast(sys_extract_utc(systimestamp) as date) - date'1970-01-01') * 86400";

	public function fixupStatement($statement) {
		$statement = preg_replace('/`(\w+)` ILIKE \?/', 'REGEXP_LIKE(`$1`, \'^\' || REPLACE(?, \'%\', \'.*\') || \'$\', \'i\')', $statement);
		$statement = str_replace('`', '"', $statement);
		$statement = str_ireplace('NOW()', 'CURRENT_TIMESTAMP', $statement);
		$statement = str_ireplace('UNIX_TIMESTAMP()', self::UNIX_TIMESTAMP_REPLACEMENT, $statement);
		return $statement;
	}
}
