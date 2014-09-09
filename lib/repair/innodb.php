<?php
/**
 * Copyright (c) 2014 Thomas Müller <deepdiver@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
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
		$connection = \OC_DB::getConnection();
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

