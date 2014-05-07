<?php
/**
 * Copyright (c) 2012 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

class Test_DBSchema extends PHPUnit_Framework_TestCase {
	protected $schema_file = 'static://test_db_scheme';
	protected $schema_file2 = 'static://test_db_scheme2';
	protected $table1;
	protected $table2;

	public function setUp() {
		$dbfile = OC::$SERVERROOT.'/tests/data/db_structure.xml';
		$dbfile2 = OC::$SERVERROOT.'/tests/data/db_structure2.xml';

		$r = '_'.OC_Util::generateRandomBytes(4).'_';
		$content = file_get_contents( $dbfile );
		$content = str_replace( '*dbprefix*', '*dbprefix*'.$r, $content );
		file_put_contents( $this->schema_file, $content );
		$content = file_get_contents( $dbfile2 );
		$content = str_replace( '*dbprefix*', '*dbprefix*'.$r, $content );
		file_put_contents( $this->schema_file2, $content );

		$prefix = OC_Config::getValue( "dbtableprefix", "oc_" );
		
		$this->table1 = $prefix.$r.'cntcts_addrsbks';
		$this->table2 = $prefix.$r.'cntcts_cards';
	}

	public function tearDown() {
		unlink($this->schema_file);
		unlink($this->schema_file2);
	}

	// everything in one test, they depend on each other
	/**
	 * @medium
	 */
	public function testSchema() {
		$this->doTestSchemaCreating();
		$this->doTestSchemaChanging();
		$this->doTestSchemaDumping();
		$this->doTestSchemaRemoving();
	}

	public function doTestSchemaCreating() {
		OC_DB::createDbFromStructure($this->schema_file);
		$this->assertTableExist($this->table1);
		$this->assertTableExist($this->table2);
	}

	public function doTestSchemaChanging() {
		OC_DB::updateDbFromStructure($this->schema_file2);
		$this->assertTableExist($this->table2);
	}

	public function doTestSchemaDumping() {
		$outfile = 'static://db_out.xml';
		OC_DB::getDbStructure($outfile);
		$content = file_get_contents($outfile);
		$this->assertContains($this->table1, $content);
		$this->assertContains($this->table2, $content);
	}

	public function doTestSchemaRemoving() {
		OC_DB::removeDBStructure($this->schema_file);
		$this->assertTableNotExist($this->table1);
		$this->assertTableNotExist($this->table2);
	}

	/**
	 * @param string $table
	 */
	public function tableExist($table) {

		switch (OC_Config::getValue( 'dbtype', 'sqlite' )) {
			case 'sqlite':
			case 'sqlite3':
				$sql = "SELECT name FROM sqlite_master "
					.  "WHERE type = 'table' AND name = ? "
					.  "UNION ALL SELECT name FROM sqlite_temp_master "
					.  "WHERE type = 'table' AND name = ?";
				$result = \OC_DB::executeAudited($sql, array($table, $table));
				break;
			case 'mysql':
				$sql = 'SHOW TABLES LIKE ?';
				$result = \OC_DB::executeAudited($sql, array($table));
				break;
			case 'pgsql':
				$sql = 'SELECT tablename AS table_name, schemaname AS schema_name '
					.  'FROM pg_tables WHERE schemaname NOT LIKE \'pg_%\' '
					.  'AND schemaname != \'information_schema\' '
					.  'AND tablename = ?';
				$result = \OC_DB::executeAudited($sql, array($table));
				break;
			case 'oci':
				$sql = 'SELECT TABLE_NAME FROM USER_TABLES WHERE TABLE_NAME = ?';
				$result = \OC_DB::executeAudited($sql, array($table));
				break;
			case 'mssql':
				$sql = 'SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = ?';
				$result = \OC_DB::executeAudited($sql, array($table));
				break;
		}
		
		$name = $result->fetchOne();
		if ($name === $table) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * @param string $table
	 */
	public function assertTableExist($table) {
		$this->assertTrue($this->tableExist($table), 'Table ' . $table . ' does not exist');
	}

	/**
	 * @param string $table
	 */
	public function assertTableNotExist($table) {
		$type=OC_Config::getValue( "dbtype", "sqlite" );
		if( $type == 'sqlite' || $type == 'sqlite3' ) {
			// sqlite removes the tables after closing the DB
		} else {
			$this->assertFalse($this->tableExist($table), 'Table ' . $table . ' exists.');
		}
	}
}
