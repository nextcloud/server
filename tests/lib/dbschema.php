<?php
/**
 * Copyright (c) 2012 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

class Test_DBSchema extends PHPUnit_Framework_TestCase {
	protected static $schema_file = 'static://test_db_scheme';
	protected static $schema_file2 = 'static://test_db_scheme2';
	protected $test_prefix;
	protected $table1;
	protected $table2;

	public function setUp() {
		$dbfile = OC::$SERVERROOT.'/tests/data/db_structure.xml';
		$dbfile2 = OC::$SERVERROOT.'/tests/data/db_structure2.xml';

		$r = '_'.OC_Util::generate_random_bytes('4').'_';
		$content = file_get_contents( $dbfile );
		$content = str_replace( '*dbprefix*', '*dbprefix*'.$r, $content );
		file_put_contents( self::$schema_file, $content );
		$content = file_get_contents( $dbfile2 );
		$content = str_replace( '*dbprefix*', '*dbprefix*'.$r, $content );
		file_put_contents( self::$schema_file2, $content );

		$this->test_prefix = $r;
		$this->table1 = $this->test_prefix.'contacts_addressbooks';
		$this->table2 = $this->test_prefix.'contacts_cards';
	}

	public function tearDown() {
		unlink(self::$schema_file);
		unlink(self::$schema_file2);
	}

	// everything in one test, they depend on each other
	public function testSchema() {
		$this->doTestSchemaCreating();
		$this->doTestSchemaChanging();
		$this->doTestSchemaDumping();
		$this->doTestSchemaRemoving();
	}

	public function doTestSchemaCreating() {
		OC_DB::createDbFromStructure(self::$schema_file);
		$this->assertTableExist($this->table1);
		$this->assertTableExist($this->table2);
	}

	public function doTestSchemaChanging() {
		OC_DB::updateDbFromStructure(self::$schema_file2);
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
		OC_DB::removeDBStructure(self::$schema_file);
		$this->assertTableNotExist($this->table1);
		$this->assertTableNotExist($this->table2);
	}

	public function tableExist($table) {
		$table = '*PREFIX*' . $table;

		switch (OC_Config::getValue( 'dbtype', 'sqlite' )) {
			case 'sqlite':
			case 'sqlite3':
				$sql = "SELECT name FROM sqlite_master "
				. "WHERE type = 'table' AND name != 'sqlite_sequence' "
				.  "AND name != 'geometry_columns' AND name != 'spatial_ref_sys' "
				. "UNION ALL SELECT name FROM sqlite_temp_master "
				. "WHERE type = 'table' AND name = '".$table."'";
				$query = OC_DB::prepare($sql);
				$result = $query->execute(array());
				$exists = $result && $result->fetchOne();
				break;
			case 'mysql':
				$sql = 'SHOW TABLES LIKE "'.$table.'"';
				$query = OC_DB::prepare($sql);
				$result = $query->execute(array());
				$exists = $result && $result->fetchOne();
				break;
			case 'pgsql':
				$sql = "SELECT tablename AS table_name, schemaname AS schema_name "
				. "FROM pg_tables WHERE schemaname NOT LIKE 'pg_%' "
				.  "AND schemaname != 'information_schema' "
				.  "AND tablename = '".$table."'";
				$query = OC_DB::prepare($sql);
				$result = $query->execute(array());
				$exists = $result && $result->fetchOne();
				break;
		}
		return $exists;
	}

	public function assertTableExist($table) {
		$this->assertTrue($this->tableExist($table));
	}

	public function assertTableNotExist($table) {
		$type=OC_Config::getValue( "dbtype", "sqlite" );
		if( $type == 'sqlite' || $type == 'sqlite3' ) {
			// sqlite removes the tables after closing the DB
		}
		else {
			$this->assertFalse($this->tableExist($table));
		}
	}
}
