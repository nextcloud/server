<?php
/**
 * Copyright (c) 2013 Bart Visscher <bartv@thisnet.nl>
 * Copyright (c) 2014 Andreas Fischer <bantu@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\DB;
use OCP\IConfig;

/**
* Various PostgreSQL specific helper functions.
*/
class PgSqlTools {

	/** @var \OCP\IConfig */
	private $config;

	/**
	 * @param \OCP\IConfig $config
	 */
	public function __construct(IConfig $config) {
		$this->config = $config;
	}

	/**
	* @brief Resynchronizes all sequences of a database after using INSERTs
	*        without leaving out the auto-incremented column.
	* @param \OC\DB\Connection $conn
	* @return null
	*/
	public function resynchronizeDatabaseSequences(Connection $conn) {
		$filterExpression = '/^' . preg_quote($this->config->getSystemValue('dbtableprefix', 'oc_')) . '/';
		$databaseName = $conn->getDatabase();
		$conn->getConfiguration()->setFilterSchemaAssetsExpression($filterExpression);

		foreach ($conn->getSchemaManager()->listSequences() as $sequence) {
			$sequenceName = $sequence->getName();
			$sqlInfo = 'SELECT table_schema, table_name, column_name
				FROM information_schema.columns
				WHERE column_default = ? AND table_catalog = ?';
			$sequenceInfo = $conn->fetchAssoc($sqlInfo, array(
				"nextval('$sequenceName'::regclass)",
				$databaseName
			));
			$tableName = $sequenceInfo['table_name'];
			$columnName = $sequenceInfo['column_name'];
			$sqlMaxId = "SELECT MAX($columnName) FROM $tableName";
			$sqlSetval = "SELECT setval('$sequenceName', ($sqlMaxId))";
			$conn->executeQuery($sqlSetval);
		}
	}
}
