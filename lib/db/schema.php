<?php
/**
 * Copyright (c) 2012 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

class OC_DB_Schema {
	/**
	 * @brief saves database scheme to xml file
	 * @param \Doctrine\DBAL\Connection $conn
	 * @param string $file name of file
	 * @param int|string $mode
	 * @return bool
	 *
	 * TODO: write more documentation
	 */
	public static function getDbStructure( $conn, $file, $mode=MDB2_SCHEMA_DUMP_STRUCTURE) {
		$sm = $conn->getSchemaManager();

		return OC_DB_MDB2SchemaWriter::saveSchemaToFile($file, $sm);
	}

	/**
	 * @brief Creates tables from XML file
	 * @param string $file file to read structure from
	 * @return bool
	 *
	 * TODO: write more documentation
	 */
	public static function createDbFromStructure( $conn, $file ) {
		$toSchema = OC_DB_MDB2SchemaReader::loadSchemaFromFile($file, $conn->getDatabasePlatform());
		return self::executeSchemaChange($conn, $toSchema);
	}

	/**
	 * @brief update the database scheme
	 * @param string $file file to read structure from
	 * @return bool
	 */
	public static function updateDbFromStructure($conn, $file) {
		$sm = $conn->getSchemaManager();
		$fromSchema = $sm->createSchema();

		$toSchema = OC_DB_MDB2SchemaReader::loadSchemaFromFile($file, $conn->getDatabasePlatform());

		// remove tables we don't know about
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

		$platform = $conn->getDatabasePlatform();
		$tables = $schemaDiff->newTables + $schemaDiff->changedTables + $schemaDiff->removedTables;
		foreach($tables as $tableDiff) {
			$tableDiff->name = $platform->quoteIdentifier($tableDiff->name);
		}


		//$from = $fromSchema->toSql($conn->getDatabasePlatform());
		//$to = $toSchema->toSql($conn->getDatabasePlatform());
		//echo($from[9]);
		//echo '<br>';
		//echo($to[9]);
		//var_dump($from, $to);
		return self::executeSchemaChange($conn, $schemaDiff);
	}

	/**
	 * @brief drop a table
	 * @param string $tableName the table to drop
	 */
	public static function dropTable($conn, $tableName) {
		$sm = $conn->getSchemaManager();
		$fromSchema = $sm->createSchema();
		$toSchema = clone $fromSchema;
		$toSchema->dropTable($tableName);
		$sql = $fromSchema->getMigrateToSql($toSchema, $conn->getDatabasePlatform());
		$conn->execute($sql);
	}

	/**
	 * remove all tables defined in a database structure xml file
	 * @param string $file the xml file describing the tables
	 */
	public static function removeDBStructure($conn, $file) {
		$fromSchema = OC_DB_MDB2SchemaReader::loadSchemaFromFile($file, $conn->getDatabasePlatform());
		$toSchema = clone $fromSchema;
		foreach($toSchema->getTables() as $table) {
			$toSchema->dropTable($table->getName());
		}
		$comparator = new \Doctrine\DBAL\Schema\Comparator();
		$schemaDiff = $comparator->compare($fromSchema, $toSchema);
		self::executeSchemaChange($conn, $schemaDiff);
	}

	/**
	 * @brief replaces the ownCloud tables with a new set
	 * @param $file string path to the MDB2 xml db export file
	 */
	public static function replaceDB( $conn, $file ) {
		$apps = OC_App::getAllApps();
		self::beginTransaction();
		// Delete the old tables
		self::removeDBStructure( $conn, OC::$SERVERROOT . '/db_structure.xml' );

		foreach($apps as $app) {
			$path = OC_App::getAppPath($app).'/appinfo/database.xml';
			if(file_exists($path)) {
				self::removeDBStructure( $conn, $path );
			}
		}

		// Create new tables
		self::commit();
	}

	private static function executeSchemaChange($conn, $schema) {
		$conn->beginTransaction();
		foreach($schema->toSql($conn->getDatabasePlatform()) as $sql) {
			$conn->query($sql);
		}
		$conn->commit();
	}
}
