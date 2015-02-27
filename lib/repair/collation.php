<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Repair;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use OC\Hooks\BasicEmitter;

class Collation extends BasicEmitter implements \OC\RepairStep {
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
	public function run() {
		if (!$this->connection->getDatabasePlatform() instanceof MySqlPlatform) {
			$this->emit('\OC\Repair', 'info', array('Not a mysql database -> nothing to no'));
			return;
		}

		$tables = $this->getAllNonUTF8BinTables($this->connection);
		foreach ($tables as $table) {
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

