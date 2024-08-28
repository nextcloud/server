<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\DB;

class AdapterOCI8 extends Adapter {
	public function lastInsertId($table) {
		if (is_null($table)) {
			throw new \InvalidArgumentException('Oracle requires a table name to be passed into lastInsertId()');
		}
		if ($table !== null) {
			$suffix = '_SEQ';
			$table = '"' . $table . $suffix . '"';
		}
		return $this->conn->realLastInsertId($table);
	}

	public const UNIX_TIMESTAMP_REPLACEMENT = "(cast(sys_extract_utc(systimestamp) as date) - date'1970-01-01') * 86400";

	public function fixupStatement($statement) {
		$statement = preg_replace('/`(\w+)` ILIKE \?/', 'REGEXP_LIKE(`$1`, \'^\' || REPLACE(?, \'%\', \'.*\') || \'$\', \'i\')', $statement);
		$statement = str_replace('`', '"', $statement);
		$statement = str_ireplace('NOW()', 'CURRENT_TIMESTAMP', $statement);
		$statement = str_ireplace('UNIX_TIMESTAMP()', self::UNIX_TIMESTAMP_REPLACEMENT, $statement);
		return $statement;
	}
}
