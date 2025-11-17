<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Repair;

use Doctrine\DBAL\Exception\DriverException;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use Psr\Log\LoggerInterface;

class Collation implements IRepairStep {
	/**
	 * @param bool $ignoreFailures
	 */
	public function __construct(
		protected IConfig $config,
		protected LoggerInterface $logger,
		protected IDBConnection $connection,
		protected $ignoreFailures,
	) {
	}

	public function getName() {
		return 'Repair MySQL collation';
	}

	/**
	 * Fix mime types
	 */
	public function run(IOutput $output) {
		if ($this->connection->getDatabaseProvider() !== IDBConnection::PLATFORM_MYSQL) {
			$output->info('Not a mysql database -> nothing to do');
			return;
		}

		$characterSet = $this->config->getSystemValueBool('mysql.utf8mb4', false) ? 'utf8mb4' : 'utf8';

		$tables = $this->getAllNonUTF8BinTables($this->connection);
		foreach ($tables as $table) {
			$output->info("Change row format for $table ...");
			$query = $this->connection->prepare('ALTER TABLE `' . $table . '` ROW_FORMAT = DYNAMIC;');
			try {
				$query->execute();
			} catch (DriverException $e) {
				// Just log this
				$this->logger->error($e->getMessage(), ['exception' => $e]);
				if (!$this->ignoreFailures) {
					throw $e;
				}
			}

			$output->info("Change collation for $table ...");
			$query = $this->connection->prepare('ALTER TABLE `' . $table . '` CONVERT TO CHARACTER SET ' . $characterSet . ' COLLATE ' . $characterSet . '_bin;');
			try {
				$query->execute();
			} catch (DriverException $e) {
				// Just log this
				$this->logger->error($e->getMessage(), ['exception' => $e]);
				if (!$this->ignoreFailures) {
					throw $e;
				}
			}
		}
		if (empty($tables)) {
			$output->info('All tables already have the correct collation -> nothing to do');
		}
	}

	/**
	 * @param IDBConnection $connection
	 * @return string[]
	 */
	protected function getAllNonUTF8BinTables(IDBConnection $connection) {
		$dbName = $this->config->getSystemValueString('dbname');
		$characterSet = $this->config->getSystemValueBool('mysql.utf8mb4', false) ? 'utf8mb4' : 'utf8';

		// fetch tables by columns
		$statement = $connection->executeQuery(
			'SELECT DISTINCT(TABLE_NAME) AS `table`'
			. '	FROM INFORMATION_SCHEMA . COLUMNS'
			. '	WHERE TABLE_SCHEMA = ?'
			. "	AND (COLLATION_NAME <> '" . $characterSet . "_bin' OR CHARACTER_SET_NAME <> '" . $characterSet . "')"
			. "	AND TABLE_NAME LIKE '*PREFIX*%'",
			[$dbName]
		);
		$rows = $statement->fetchAll();
		$result = [];
		foreach ($rows as $row) {
			$result[$row['table']] = true;
		}

		// fetch tables by collation
		$statement = $connection->executeQuery(
			'SELECT DISTINCT(TABLE_NAME) AS `table`'
			. '	FROM INFORMATION_SCHEMA . TABLES'
			. '	WHERE TABLE_SCHEMA = ?'
			. "	AND TABLE_COLLATION <> '" . $characterSet . "_bin'"
			. "	AND TABLE_NAME LIKE '*PREFIX*%'",
			[$dbName]
		);
		$rows = $statement->fetchAll();
		foreach ($rows as $row) {
			$result[$row['table']] = true;
		}

		return array_keys($result);
	}
}
