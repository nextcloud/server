<?php
/**
 * @copyright Copyright (c) 2017, ownCloud GmbH
 *
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

use OCP\IDBConnection;

/**
* Various MySQL specific helper functions.
*/
class MySqlTools {

	/**
	 * @param Connection $connection
	 * @return bool
	 */
	public function supports4ByteCharset(IDBConnection $connection) {
		foreach (['innodb_file_format' => 'Barracuda', 'innodb_large_prefix' => 'ON', 'innodb_file_per_table' => 'ON'] as $var => $val) {
			$result = $connection->executeQuery("SHOW VARIABLES LIKE '$var'");
			$rows = $result->fetch();
			$result->closeCursor();
			if ($rows === false) {
				return false;
			}
			if (strcasecmp($rows['Value'], $val) !== 0) {
				return false;
			}
		}
		return true;
	}
}
