<?php
/**
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
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class Collation implements IRepairStep {
	/**
	 * @var \OCP\IConfig
	 */
	protected $config;

	/**
	 * @var \OC\DB\Connection
	 */
	protected $connection;

	/**
	 * @param \OCP\IConfig $config
	 * @param \OC\DB\Connection $connection
	 */
	public function __construct($config, $connection) {
		$this->connection = $connection;
		$this->config = $config;
	}

	public function getName() {
		return 'Repair MySQL collation';
	}

	/**
	 * Fix mime types
	 */
	public function run(IOutput $output) {
		if (!$this->connection->getDatabasePlatform() instanceof MySqlPlatform) {
			$output->info('Not a mysql database -> nothing to no');
			return;
		}

		$tables = $this->getAllNonUTF8BinTables($this->connection);
		foreach ($tables as $table) {
			$output->info("Change collation for $table ...");
			$query = $this->connection->prepare('ALTER TABLE `' . $table . '` CONVERT TO CHARACTER SET utf8 COLLATE utf8_bin;');
			$query->execute();
		}
	}

	/**
	 * @param \Doctrine\DBAL\Connection $connection
	 * @return string[]
	 */
	protected function getAllNonUTF8BinTables($connection) {
		$dbName = $this->config->getSystemValue("dbname");
		$rows = $connection->fetchAll(
			"SELECT DISTINCT(TABLE_NAME) AS `table`" .
			"	FROM INFORMATION_SCHEMA . COLUMNS" .
			"	WHERE TABLE_SCHEMA = ?" .
			"	AND (COLLATION_NAME <> 'utf8_bin' OR CHARACTER_SET_NAME <> 'utf8')" .
			"	AND TABLE_NAME LIKE \"*PREFIX*%\"",
			array($dbName)
		);
		$result = array();
		foreach ($rows as $row) {
			$result[] = $row['table'];
		}
		return $result;
	}
}

