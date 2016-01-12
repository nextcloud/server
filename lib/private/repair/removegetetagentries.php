<?php
/**
 * @author Morris Jobke <hey@morrisjobke.de>
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

namespace OC\Repair;

use OC\Hooks\BasicEmitter;
use OCP\IDBConnection;

class RemoveGetETagEntries extends BasicEmitter {

	/**
	 * @var IDBConnection
	 */
	protected $connection;

	/**
	 * @param IDBConnection $connection
	 */
	public function __construct(IDBConnection $connection) {
		$this->connection = $connection;
	}

	public function getName() {
		return 'Remove getetag entries in properties table';
	}

	/**
	 * Removes all entries with the key "{DAV:}getetag" from the table properties
	 */
	public function run() {
		$sql = 'DELETE FROM `*PREFIX*properties`'
			. ' WHERE `propertyname` = ?';
		$deletedRows = $this->connection->executeUpdate($sql, ['{DAV:}getetag']);

		$this->emit(
			'\OC\Repair',
			'info',
			['Removed ' . $deletedRows . ' unneeded "{DAV:}getetag" entries from properties table.']
		);
	}
}
