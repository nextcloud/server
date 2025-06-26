<?php

/**
 * SPDX-FileCopyrightText: 2017 ownCloud GmbH
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\DB;

use OCP\IDBConnection;

/**
 * Various MySQL specific helper functions.
 */
class MySqlTools {
	/**
	 * @param IDBConnection $connection
	 * @return bool
	 */
	public function supports4ByteCharset(IDBConnection $connection) {
		$variables = ['innodb_file_per_table' => 'ON'];
		if (!$this->isMariaDBWithLargePrefix($connection)) {
			$variables['innodb_file_format'] = 'Barracuda';
			$variables['innodb_large_prefix'] = 'ON';
		}

		foreach ($variables as $var => $val) {
			$result = $connection->executeQuery("SHOW VARIABLES LIKE '$var'");
			$row = $result->fetch();
			$result->closeCursor();
			if ($row === false) {
				return false;
			}
			if (strcasecmp($row['Value'], $val) !== 0) {
				return false;
			}
		}
		return true;
	}

	protected function isMariaDBWithLargePrefix(IDBConnection $connection) {
		$result = $connection->executeQuery('SELECT VERSION()');
		$row = strtolower($result->fetchColumn());
		$result->closeCursor();

		if ($row === false) {
			return false;
		}

		return str_contains($row, 'maria') && version_compare($row, '10.3', '>=')
			|| !str_contains($row, 'maria') && version_compare($row, '8.0', '>=');
	}
}
