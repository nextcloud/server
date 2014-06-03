<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\DB;

use Doctrine\DBAL\DBALException;

class SQLiteMigrator extends Migrator {
	/**
	 * @param \Doctrine\DBAL\Schema\Schema $targetSchema
	 * @throws \OC\DB\MigrationException
	 *
	 * For sqlite we simple make a copy of the entire database, and test the migration on that
	 */
	public function checkMigrate(\Doctrine\DBAL\Schema\Schema $targetSchema) {
		$dbFile = $this->connection->getDatabase();
		$tmpFile = \OC_Helper::tmpFile('.db');
		copy($dbFile, $tmpFile);

		$connectionParams = array(
			'path' => $tmpFile,
			'driver' => 'pdo_sqlite',
		);
		$conn = \Doctrine\DBAL\DriverManager::getConnection($connectionParams);
		try {
			$this->applySchema($targetSchema, $conn);
			$conn->close();
			unlink($tmpFile);
		} catch (DBALException $e) {
			$conn->close();
			unlink($tmpFile);
			throw new MigrationException('', $e->getMessage());
		}
	}
}
