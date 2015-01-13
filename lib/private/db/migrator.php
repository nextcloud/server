<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\DB;

use \Doctrine\DBAL\DBALException;
use \Doctrine\DBAL\Schema\Index;
use \Doctrine\DBAL\Schema\Table;
use \Doctrine\DBAL\Schema\Schema;
use \Doctrine\DBAL\Schema\SchemaConfig;
use \Doctrine\DBAL\Schema\Comparator;
use OCP\IConfig;
use OCP\Security\ISecureRandom;

class Migrator {

	/**
	 * @var \Doctrine\DBAL\Connection $connection
	 */
	protected $connection;

	/**
	 * @var ISecureRandom
	 */
	private $random;

	/** @var IConfig */
	protected $config;

	/**
	 * @param \Doctrine\DBAL\Connection $connection
	 * @param ISecureRandom $random
	 * @param IConfig $config
	 */
	public function __construct(\Doctrine\DBAL\Connection $connection, ISecureRandom $random, IConfig $config) {
		$this->connection = $connection;
		$this->random = $random;
		$this->config = $config;
	}

	/**
	 * @param \Doctrine\DBAL\Schema\Schema $targetSchema
	 */
	public function migrate(Schema $targetSchema) {
		$this->applySchema($targetSchema);
	}

	/**
	 * @param \Doctrine\DBAL\Schema\Schema $targetSchema
	 * @return string
	 */
	public function generateChangeScript(Schema $targetSchema) {
		$schemaDiff = $this->getDiff($targetSchema, $this->connection);

		$script = '';
		$sqls = $schemaDiff->toSql($this->connection->getDatabasePlatform());
		foreach ($sqls as $sql) {
			$script .= $this->convertStatementToScript($sql);
		}

		return $script;
	}

	/**
	 * @param Schema $targetSchema
	 * @throws \OC\DB\MigrationException
	 */
	public function checkMigrate(Schema $targetSchema) {
		/**
		 * @var \Doctrine\DBAL\Schema\Table[] $tables
		 */
		$tables = $targetSchema->getTables();
		$filterExpression = $this->getFilterExpression();
		$this->connection->getConfiguration()->
			setFilterSchemaAssetsExpression($filterExpression);
		$existingTables = $this->connection->getSchemaManager()->listTableNames();

		foreach ($tables as $table) {
			if (strpos($table->getName(), '.')) {
				list(, $tableName) = explode('.', $table->getName());
			} else {
				$tableName = $table->getName();
			}
			// don't need to check for new tables
			if (array_search($tableName, $existingTables) !== false) {
				$this->checkTableMigrate($table);
			}
		}
	}

	/**
	 * Create a unique name for the temporary table
	 *
	 * @param string $name
	 * @return string
	 */
	protected function generateTemporaryTableName($name) {
		return 'oc_' . $name . '_' . $this->random->generate(13, ISecureRandom::CHAR_LOWER . ISecureRandom::CHAR_DIGITS);
	}

	/**
	 * Check the migration of a table on a copy so we can detect errors before messing with the real table
	 *
	 * @param \Doctrine\DBAL\Schema\Table $table
	 * @throws \OC\DB\MigrationException
	 */
	protected function checkTableMigrate(Table $table) {
		$name = $table->getName();
		$tmpName = $this->generateTemporaryTableName($name);

		$this->copyTable($name, $tmpName);

		//create the migration schema for the temporary table
		$tmpTable = $this->renameTableSchema($table, $tmpName);
		$schemaConfig = new SchemaConfig();
		$schemaConfig->setName($this->connection->getDatabase());
		$schema = new Schema(array($tmpTable), array(), $schemaConfig);

		try {
			$this->applySchema($schema);
			$this->dropTable($tmpName);
		} catch (DBALException $e) {
			// pgsql needs to commit it's failed transaction before doing anything else
			if ($this->connection->isTransactionActive()) {
				$this->connection->commit();
			}
			$this->dropTable($tmpName);
			throw new MigrationException($table->getName(), $e->getMessage());
		}
	}

	/**
	 * @param \Doctrine\DBAL\Schema\Table $table
	 * @param string $newName
	 * @return \Doctrine\DBAL\Schema\Table
	 */
	protected function renameTableSchema(Table $table, $newName) {
		/**
		 * @var \Doctrine\DBAL\Schema\Index[] $indexes
		 */
		$indexes = $table->getIndexes();
		$newIndexes = array();
		foreach ($indexes as $index) {
			if ($index->isPrimary()) {
				// do not rename primary key
				$indexName = $index->getName();
			} else {
				// avoid conflicts in index names
				$indexName = 'oc_' . $this->random->generate(13, ISecureRandom::CHAR_LOWER . ISecureRandom::CHAR_DIGITS);
			}
			$newIndexes[] = new Index($indexName, $index->getColumns(), $index->isUnique(), $index->isPrimary());
		}

		// foreign keys are not supported so we just set it to an empty array
		return new Table($newName, $table->getColumns(), $newIndexes, array(), 0, $table->getOptions());
	}

	protected function getDiff(Schema $targetSchema, \Doctrine\DBAL\Connection $connection) {
		$filterExpression = $this->getFilterExpression();
		$this->connection->getConfiguration()->
		setFilterSchemaAssetsExpression($filterExpression);
		$sourceSchema = $connection->getSchemaManager()->createSchema();

		// remove tables we don't know about
		/** @var $table \Doctrine\DBAL\Schema\Table */
		foreach ($sourceSchema->getTables() as $table) {
			if (!$targetSchema->hasTable($table->getName())) {
				$sourceSchema->dropTable($table->getName());
			}
		}
		// remove sequences we don't know about
		foreach ($sourceSchema->getSequences() as $table) {
			if (!$targetSchema->hasSequence($table->getName())) {
				$sourceSchema->dropSequence($table->getName());
			}
		}

		$comparator = new Comparator();
		return $comparator->compare($sourceSchema, $targetSchema);
	}

	/**
	 * @param \Doctrine\DBAL\Schema\Schema $targetSchema
	 * @param \Doctrine\DBAL\Connection $connection
	 */
	protected function applySchema(Schema $targetSchema, \Doctrine\DBAL\Connection $connection = null) {
		if (is_null($connection)) {
			$connection = $this->connection;
		}

		$schemaDiff = $this->getDiff($targetSchema, $connection);

		$connection->beginTransaction();
		foreach ($schemaDiff->toSql($connection->getDatabasePlatform()) as $sql) {
			$connection->query($sql);
		}
		$connection->commit();
	}

	/**
	 * @param string $sourceName
	 * @param string $targetName
	 */
	protected function copyTable($sourceName, $targetName) {
		$quotedSource = $this->connection->quoteIdentifier($sourceName);
		$quotedTarget = $this->connection->quoteIdentifier($targetName);

		$this->connection->exec('CREATE TABLE ' . $quotedTarget . ' (LIKE ' . $quotedSource . ')');
		$this->connection->exec('INSERT INTO ' . $quotedTarget . ' SELECT * FROM ' . $quotedSource);
	}

	/**
	 * @param string $name
	 */
	protected function dropTable($name) {
		$this->connection->exec('DROP TABLE ' . $this->connection->quoteIdentifier($name));
	}

	/**
	 * @param $statement
	 * @return string
	 */
	protected function convertStatementToScript($statement) {
		$script = $statement . ';';
		$script .= PHP_EOL;
		$script .= PHP_EOL;
		return $script;
	}

	protected function getFilterExpression() {
		return '/^' . preg_quote($this->config->getSystemValue('dbtableprefix', 'oc_')) . '/';
	}
}
