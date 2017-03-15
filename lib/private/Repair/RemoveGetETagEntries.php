<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Morris Jobke <hey@morrisjobke.de>
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

namespace OC\Repair;

use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class RemoveGetETagEntries implements IRepairStep {

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
	public function run(IOutput $out) {
		$sql = 'DELETE FROM `*PREFIX*properties`'
			. ' WHERE `propertyname` = ?';
		$deletedRows = $this->connection->executeUpdate($sql, ['{DAV:}getetag']);

		$out->info('Removed ' . $deletedRows . ' unneeded "{DAV:}getetag" entries from properties table.');
	}
}
