<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\DB;

use Doctrine\DBAL\Schema\AbstractAsset;
use OCP\IConfig;
use function preg_match;
use function preg_quote;

/**
 * Various PostgreSQL specific helper functions.
 */
class PgSqlTools {
	public function __construct(
		private IConfig $config,
	) {
	}

	/**
	 * @brief Resynchronizes all sequences of a database after using INSERTs
	 *        without leaving out the auto-incremented column.
	 */
	public function resynchronizeDatabaseSequences(Connection $conn): void {
		$databaseName = $conn->getDatabase();
		$conn->getConfiguration()->setSchemaAssetsFilter(function ($asset) {
			/** @var string|AbstractAsset $asset */
			$filterExpression = '/^' . preg_quote($this->config->getSystemValueString('dbtableprefix', 'oc_')) . '/';
			if ($asset instanceof AbstractAsset) {
				return preg_match($filterExpression, $asset->getName()) !== false;
			}
			return preg_match($filterExpression, $asset) !== false;
		});

		foreach ($conn->createSchemaManager()->listSequences() as $sequence) {
			$longSequenceName = $sequence->getName();
			// We need to strip away the preceding database prefix.
			// Example: oc_circles_circle_id_seq, not nextcloud.oc_circles_circle_id_seq
			$sequenceName = preg_replace('/^.*\./', '', $longSequenceName);

			$sqlInfo = 'SELECT table_schema, table_name, column_name
				FROM information_schema.columns
				WHERE column_default = ? AND table_catalog = ?';
			$result = $conn->executeQuery($sqlInfo, [
				"nextval('$sequenceName'::regclass)",
				$databaseName
			]);
			$sequenceInfo = $result->fetchAssociative();
			$result->free();
			/** @var string $tableName */
			$tableName = $sequenceInfo['table_name'];
			/** @var string $columnName */
			$columnName = $sequenceInfo['column_name'];
			$sqlMaxId = "SELECT MAX($columnName) FROM $tableName";
			$sqlSetval = "SELECT setval('$sequenceName', ($sqlMaxId))";
			$conn->executeQuery($sqlSetval);
		}
	}
}
