<?php

declare(strict_types=1);

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
	public function __construct(
		protected IConfig $config,
		protected LoggerInterface $logger,
		protected IDBConnection $connection,
		protected bool $ignoreFailures,
	) {
	}

	#[\Override]
	public function getName(): string {
		return 'Repair MySQL collation';
	}

	/**
	 * Fix mime types
	 */
	#[\Override]
	public function run(IOutput $output): void {
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
				$output->warning($e->getMessage());
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
				$output->warning($e->getMessage());
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
	 * @return string[]
	 */
	protected function getAllNonUTF8BinTables(IDBConnection $connection): array {
		$dbName = $this->config->getSystemValueString('dbname');
		$characterSet = $this->config->getSystemValueBool('mysql.utf8mb4', false) ? 'utf8mb4' : 'utf8';

		// Since MySQL 8.0.30, INFORMATION_SCHEMA reports the legacy 3-byte utf8
		// charset/collation as "utf8mb3" instead of the "utf8" alias used to
		// create it, so both names have to be accepted or already-correct
		// tables get flagged as needing repair on every run.
		// See https://dev.mysql.com/doc/refman/8.0/en/charset-unicode-utf8mb3.html
		$acceptedCharsets = $characterSet === 'utf8' ? ['utf8', 'utf8mb3'] : [$characterSet];
		$acceptedCollations = array_map(static fn (string $charset): string => $charset . '_bin', $acceptedCharsets);
		$charsetList = "'" . implode("', '", $acceptedCharsets) . "'";
		$collationList = "'" . implode("', '", $acceptedCollations) . "'";

		// fetch tables by columns
		$statement = $connection->executeQuery(
			'SELECT DISTINCT(TABLE_NAME) AS `table`'
			. '	FROM INFORMATION_SCHEMA . COLUMNS'
			. '	WHERE TABLE_SCHEMA = ?'
			. "	AND (COLLATION_NAME NOT IN ($collationList) OR CHARACTER_SET_NAME NOT IN ($charsetList))"
			. "	AND TABLE_NAME LIKE '*PREFIX*%'",
			[$dbName]
		);
		$rows = $statement->fetchAllAssociative();
		$result = [];
		foreach ($rows as $row) {
			$result[$row['table']] = true;
		}

		// fetch tables by collation
		$statement = $connection->executeQuery(
			'SELECT DISTINCT(TABLE_NAME) AS `table`'
			. '	FROM INFORMATION_SCHEMA . TABLES'
			. '	WHERE TABLE_SCHEMA = ?'
			. "	AND TABLE_COLLATION NOT IN ($collationList)"
			. "	AND TABLE_NAME LIKE '*PREFIX*%'",
			[$dbName]
		);
		$rows = $statement->fetchAllAssociative();
		foreach ($rows as $row) {
			$result[$row['table']] = true;
		}

		return array_keys($result);
	}
}
