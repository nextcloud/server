<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\DB;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Schema\Schema;

class SQLiteMigrator extends Migrator {

	/**
	 * @param \Doctrine\DBAL\Schema\Schema $targetSchema
	 * @throws \OC\DB\MigrationException
	 *
	 * For sqlite we simple make a copy of the entire database, and test the migration on that
	 */
	public function checkMigrate(\Doctrine\DBAL\Schema\Schema $targetSchema) {
		$dbFile = $this->connection->getDatabase();
		$tmpFile = $this->buildTempDatabase();
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

	/**
	 * @return string
	 */
	private function buildTempDatabase() {
		$dataDir = $this->config->getSystemValue("datadirectory", \OC::$SERVERROOT . '/data');
		$tmpFile = uniqid("oc_");
		return "$dataDir/$tmpFile.db";
	}

	/**
	 * @param Schema $targetSchema
	 * @param \Doctrine\DBAL\Connection $connection
	 * @return \Doctrine\DBAL\Schema\SchemaDiff
	 */
	protected function getDiff(Schema $targetSchema, \Doctrine\DBAL\Connection $connection) {
		$platform = $connection->getDatabasePlatform();
		$platform->registerDoctrineTypeMapping('tinyint unsigned', 'integer');
		$platform->registerDoctrineTypeMapping('smallint unsigned', 'integer');
		$platform->registerDoctrineTypeMapping('varchar ', 'string');

		return parent::getDiff($targetSchema, $connection);
	}
}
