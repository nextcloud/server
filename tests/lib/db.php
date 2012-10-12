<?php
/**
 * Copyright (c) 2012 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

class Test_DB extends UnitTestCase {
	protected static $schema_file = 'static://test_db_scheme';
	protected $test_prefix;

	public function setUp() {
		$dbfile = OC::$SERVERROOT.'/tests/data/db_structure.xml';

		$r = '_'.OC_Util::generate_random_bytes('4').'_';
		$content = file_get_contents( $dbfile );
		$content = str_replace( '*dbprefix*', '*dbprefix*'.$r, $content );
		file_put_contents( self::$schema_file, $content );

		$this->test_prefix = $r;
		$this->table1 = $this->test_prefix.'contacts_addressbooks';
		$this->table2 = $this->test_prefix.'contacts_cards';
	}

	public function tearDown() {
		unlink(self::$schema_file);
	}

	protected function setUpDB() {
		OC_DB::disconnect();
		OC_DB::createDbFromStructure(self::$schema_file);
	}
	protected function tearDownDB() {
		OC_DB::removeDBStructure(self::$schema_file);
	}

	// every thing in one test, phpunit messes with MDB2
	// also setUpDB and tearDownDB only once, otherwise sqlite doesn't finish
	public function testDBCompatibility() {
		$this->setUpDB();
		$this->doTestQuotes();
		$this->doTestNOW();
		$this->doTestUNIX_TIMESTAMP();
		$this->tearDownDB();
	}

	public function doTestQuotes() {
		//$this->setUpDB();
		$query = OC_DB::prepare('SELECT `fullname` FROM *PREFIX*'.$this->table2.' WHERE `uri` = ?');
		$result = $query->execute(array('uri_1'));
		$this->assertTrue($result);
		$row = $result->fetchRow();
		$this->assertFalse($row);
		$query = OC_DB::prepare('INSERT INTO *PREFIX*'.$this->table2.' (`fullname`,`uri`) VALUES (?,?)');
		$result = $query->execute(array('fullname test', 'uri_1'));
		$this->assertTrue($result);
		$query = OC_DB::prepare('SELECT `fullname`,`uri` FROM *PREFIX*'.$this->table2.' WHERE `uri` = ?');
		$result = $query->execute(array('uri_1'));
		$this->assertTrue($result);
		$row = $result->fetchRow();
		$this->assertArrayHasKey('fullname', $row);
		$this->assertEqual($row['fullname'], 'fullname test');
		$row = $result->fetchRow();
		$this->assertFalse($row);
		//$this->tearDownDB();
	}

	public function doTestNOW() {
		//$this->setUpDB();
		$query = OC_DB::prepare('INSERT INTO *PREFIX*'.$this->table2.' (`fullname`,`uri`) VALUES (NOW(),?)');
		$result = $query->execute(array('uri_2'));
		$this->assertTrue($result);
		$query = OC_DB::prepare('SELECT `fullname`,`uri` FROM *PREFIX*'.$this->table2.' WHERE `uri` = ?');
		$result = $query->execute(array('uri_2'));
		$this->assertTrue($result);
		//$this->tearDownDB();
	}

	public function doTestUNIX_TIMESTAMP() {
		//$this->setUpDB();
		$query = OC_DB::prepare('INSERT INTO *PREFIX*'.$this->table2.' (`fullname`,`uri`) VALUES (UNIX_TIMESTAMP(),?)');
		$result = $query->execute(array('uri_3'));
		$this->assertTrue($result);
		$query = OC_DB::prepare('SELECT `fullname`,`uri` FROM *PREFIX*'.$this->table2.' WHERE `uri` = ?');
		$result = $query->execute(array('uri_3'));
		$this->assertTrue($result);
		//$this->tearDownDB();
	}
}
