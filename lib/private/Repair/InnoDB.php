<?php
/**
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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

use Doctrine\DBAL\Platforms\MySqlPlatform;
use OC\Hooks\BasicEmitter;

class InnoDB extends BasicEmitter implements \OC\RepairStep {

	public function getName() {
		return 'Repair MySQL database engine';
	}

	/**
	 * Fix mime types
	 */
	public function run() {
		$connection = \OC::$server->getDatabaseConnection();
		if (!$connection->getDatabasePlatform() instanceof MySqlPlatform) {
			$this->emit('\OC\Repair', 'info', array('Not a mysql database -> nothing to do'));
			return;
		}

		$tables = $this->getAllMyIsamTables($connection);
		if (is_array($tables)) {
			foreach ($tables as $table) {
				$connection->exec("ALTER TABLE $table ENGINE=InnoDB;");
				$this->emit('\OC\Repair', 'info', array("Fixed $table"));
			}
		}
	}

	/**
	 * @param \Doctrine\DBAL\Connection $connection
	 * @return string[]
	 */
	private function getAllMyIsamTables($connection) {
		$dbName = \OC::$server->getConfig()->getSystemValue("dbname");
		$result = $connection->fetchArray(
			"SELECT table_name FROM information_schema.tables WHERE table_schema = ? AND engine = 'MyISAM' AND TABLE_NAME LIKE \"*PREFIX*%\"",
			array($dbName)
		);

		return $result;
	}
}

