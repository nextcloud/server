<?php
/**
 * Copyright (c) 2012 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\DB;

class MDB2SchemaManager {
	/**
	 * @var \OC\DB\Connection $conn
	 */
	protected $conn;

	/**
	 * @param \OC\DB\Connection $conn
	 */
	public function __construct($conn) {
		$this->conn = $conn;
		$this->conn->close();
		$this->conn->connect();
	}

	/**
	 * @brief saves database scheme to xml file
	 * @param string $file name of file
	 * @param int|string $mode
	 * @return bool
	 *
	 * TODO: write more documentation
	 */
	public function getDbStructure( $file, $mode = MDB2_SCHEMA_DUMP_STRUCTURE) {
		$sm = $this->conn->getSchemaManager();

		return \OC_DB_MDB2SchemaWriter::saveSchemaToFile($file, $sm);
	}

	/**
	 * @brief Creates tables from XML file
	 * @param string $file file to read structure from
	 * @return bool
	 *
	 * TODO: write more documentation
	 */
	public function createDbFromStructure( $file ) {
		$schemaReader = new MDB2SchemaReader(\OC_Config::getObject(), $this->conn->getDatabasePlatform());
		$toSchema = $schemaReader->loadSchemaFromFile($file);
		return $this->executeSchemaChange($toSchema);
	}

	/**
	 * @brief update the database scheme
	 * @param string $file file to read structure from
	 * @return string|boolean
	 */
	public function updateDbFromStructure($file, $generateSql = false) {
		$sm = $this->conn->getSchemaManager();
		$fromSchema = $sm->createSchema();

		$schemaReader = new MDB2SchemaReader(\OC_Config::getObject(), $this->conn->getDatabasePlatform());
		$toSchema = $schemaReader->loadSchemaFromFile($file);

		// remove tables we don't know about
		/** @var $table \Doctrine\DBAL\Schema\Table */
		foreach($fromSchema->getTables() as $table) {
			if (!$toSchema->hasTable($table->getName())) {
				$fromSchema->dropTable($table->getName());
			}
		}
		// remove sequences we don't know about
		foreach($fromSchema->getSequences() as $table) {
			if (!$toSchema->hasSequence($table->getName())) {
				$fromSchema->dropSequence($table->getName());
			}
		}

		$comparator = new \Doctrine\DBAL\Schema\Comparator();
		$schemaDiff = $comparator->compare($fromSchema, $toSchema);

		$platform = $this->conn->getDatabasePlatform();
		foreach($schemaDiff->changedTables as $tableDiff) {
			$tableDiff->name = $platform->quoteIdentifier($tableDiff->name);
			foreach($tableDiff->changedColumns as $column) {
				$column->oldColumnName = $platform->quoteIdentifier($column->oldColumnName);
			}
		}
		
		if ($generateSql) {
			return $this->generateChangeScript($schemaDiff);
		}

		return $this->executeSchemaChange($schemaDiff);
	}

	/**
	 * @brief drop a table
	 * @param string $tableName the table to drop
	 */
	public function dropTable($tableName) {
		$sm = $this->conn->getSchemaManager();
		$fromSchema = $sm->createSchema();
		$toSchema = clone $fromSchema;
		$toSchema->dropTable($tableName);
		$sql = $fromSchema->getMigrateToSql($toSchema, $this->conn->getDatabasePlatform());
		$this->conn->executeQuery($sql);
	}

	/**
	 * remove all tables defined in a database structure xml file
	 * @param string $file the xml file describing the tables
	 */
	public function removeDBStructure($file) {
		$schemaReader = new MDB2SchemaReader(\OC_Config::getObject(), $this->conn->getDatabasePlatform());
		$fromSchema = $schemaReader->loadSchemaFromFile($file);
		$toSchema = clone $fromSchema;
		/** @var $table \Doctrine\DBAL\Schema\Table */
		foreach($toSchema->getTables() as $table) {
			$toSchema->dropTable($table->getName());
		}
		$comparator = new \Doctrine\DBAL\Schema\Comparator();
		$schemaDiff = $comparator->compare($fromSchema, $toSchema);
		$this->executeSchemaChange($schemaDiff);
	}

	/**
	 * @brief replaces the ownCloud tables with a new set
	 * @param $file string path to the MDB2 xml db export file
	 */
	public function replaceDB( $file ) {
		$apps = \OC_App::getAllApps();
		$this->conn->beginTransaction();
		// Delete the old tables
		$this->removeDBStructure( \OC::$SERVERROOT . '/db_structure.xml' );

		foreach($apps as $app) {
			$path = \OC_App::getAppPath($app).'/appinfo/database.xml';
			if(file_exists($path)) {
				$this->removeDBStructure( $path );
			}
		}

		// Create new tables
		$this->conn->commit();
	}

	/**
	 * @param \Doctrine\DBAL\Schema\Schema $schema
	 * @return bool
	 */
	private function executeSchemaChange($schema) {
		$this->conn->beginTransaction();
		foreach($schema->toSql($this->conn->getDatabasePlatform()) as $sql) {
			$this->conn->query($sql);
		}
		$this->conn->commit();
		return true;
	}

	/**
	 * @param \Doctrine\DBAL\Schema\Schema $schema
	 * @return string
	 */
	public function generateChangeScript($schema) {

		$script = '';
		$sqls = $schema->toSql($this->conn->getDatabasePlatform());
		foreach($sqls as $sql) {
			$script .= $sql . ';';
			$script .= PHP_EOL;
		}

		return $script;
	}
}
