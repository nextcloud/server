<?php
/**
 * Copyright (c) 2012 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

class Test_DB extends PHPUnit_Framework_TestCase {
	protected $backupGlobals = FALSE;

	protected static $schema_file = 'static://test_db_scheme';
	protected $test_prefix;

	public function setUp() {
		$dbfile = OC::$SERVERROOT.'/tests/data/db_structure.xml';

		$r = '_'.OC_Util::generateRandomBytes('4').'_';
		$content = file_get_contents( $dbfile );
		$content = str_replace( '*dbprefix*', '*dbprefix*'.$r, $content );
		file_put_contents( self::$schema_file, $content );
		OC_DB::createDbFromStructure(self::$schema_file);

		$this->test_prefix = $r;
		$this->table1 = $this->test_prefix.'cntcts_addrsbks';
		$this->table2 = $this->test_prefix.'cntcts_cards';
		$this->table3 = $this->test_prefix.'vcategory';
	}

	public function tearDown() {
		OC_DB::removeDBStructure(self::$schema_file);
		unlink(self::$schema_file);
	}

	public function testQuotes() {
		$query = OC_DB::prepare('SELECT `fullname` FROM `*PREFIX*'.$this->table2.'` WHERE `uri` = ?');
		$result = $query->execute(array('uri_1'));
		$this->assertTrue((bool)$result);
		$row = $result->fetchRow();
		$this->assertFalse($row);
		$query = OC_DB::prepare('INSERT INTO `*PREFIX*'.$this->table2.'` (`fullname`,`uri`) VALUES (?,?)');
		$result = $query->execute(array('fullname test', 'uri_1'));
		$this->assertEquals(1, $result);
		$query = OC_DB::prepare('SELECT `fullname`,`uri` FROM `*PREFIX*'.$this->table2.'` WHERE `uri` = ?');
		$result = $query->execute(array('uri_1'));
		$this->assertTrue((bool)$result);
		$row = $result->fetchRow();
		$this->assertArrayHasKey('fullname', $row);
		$this->assertEquals($row['fullname'], 'fullname test');
		$row = $result->fetchRow();
		$this->assertFalse((bool)$row); //PDO returns false, MDB2 returns null
	}

	/**
	 * @medium
	 */
	public function testNOW() {
		$query = OC_DB::prepare('INSERT INTO `*PREFIX*'.$this->table2.'` (`fullname`,`uri`) VALUES (NOW(),?)');
		$result = $query->execute(array('uri_2'));
		$this->assertEquals(1, $result);
		$query = OC_DB::prepare('SELECT `fullname`,`uri` FROM `*PREFIX*'.$this->table2.'` WHERE `uri` = ?');
		$result = $query->execute(array('uri_2'));
		$this->assertTrue((bool)$result);
	}

	public function testUNIX_TIMESTAMP() {
		$query = OC_DB::prepare('INSERT INTO `*PREFIX*'.$this->table2.'` (`fullname`,`uri`) VALUES (UNIX_TIMESTAMP(),?)');
		$result = $query->execute(array('uri_3'));
		$this->assertEquals(1, $result);
		$query = OC_DB::prepare('SELECT `fullname`,`uri` FROM `*PREFIX*'.$this->table2.'` WHERE `uri` = ?');
		$result = $query->execute(array('uri_3'));
		$this->assertTrue((bool)$result);
	}
	
	public function testLastInsertId() {
		$query = OC_DB::prepare('INSERT INTO `*PREFIX*'.$this->table2.'` (`fullname`,`uri`) VALUES (?,?)');
		$result1 = OC_DB::executeAudited($query, array('insertid 1','uri_1'));
		$id1 = OC_DB::insertid('*PREFIX*'.$this->table2);
		
		// we don't know the id we should expect, so insert another row
		$result2 = OC_DB::executeAudited($query, array('insertid 2','uri_2'));
		$id2 = OC_DB::insertid('*PREFIX*'.$this->table2);
		// now we can check if the two ids are in correct order
		$this->assertGreaterThan($id1, $id2);
	}
	
	public function testinsertIfNotExist() {
		$categoryentries = array(
				array('user' => 'test', 'type' => 'contact', 'category' => 'Family',    'expectedResult' => 1),
				array('user' => 'test', 'type' => 'contact', 'category' => 'Friends',   'expectedResult' => 1),
				array('user' => 'test', 'type' => 'contact', 'category' => 'Coworkers', 'expectedResult' => 1),
				array('user' => 'test', 'type' => 'contact', 'category' => 'Coworkers', 'expectedResult' => 0),
				array('user' => 'test', 'type' => 'contact', 'category' => 'School',    'expectedResult' => 1),
			);

		foreach($categoryentries as $entry) {
			$result = OC_DB::insertIfNotExist('*PREFIX*'.$this->table3,
				array(
					'uid' => $entry['user'],
					'type' => $entry['type'],
					'category' => $entry['category'],
				));
			$this->assertEquals($entry['expectedResult'], $result);
		}

		$query = OC_DB::prepare('SELECT * FROM `*PREFIX*'.$this->table3.'`');
		$result = $query->execute();
		$this->assertTrue((bool)$result);
		$this->assertEquals(4, count($result->fetchAll()));
	}

	public function testinsertIfNotExistDontOverwrite() {
		$fullname = 'fullname test';
		$uri = 'uri_1';
		$carddata = 'This is a vCard';

		// Normal test to have same known data inserted.
		$query = OC_DB::prepare('INSERT INTO `*PREFIX*'.$this->table2.'` (`fullname`, `uri`, `carddata`) VALUES (?, ?, ?)');
		$result = $query->execute(array($fullname, $uri, $carddata));
		$this->assertEquals(1, $result);
		$query = OC_DB::prepare('SELECT `fullname`, `uri`, `carddata` FROM `*PREFIX*'.$this->table2.'` WHERE `uri` = ?');
		$result = $query->execute(array($uri));
		$this->assertTrue((bool)$result);
		$row = $result->fetchRow();
		$this->assertArrayHasKey('carddata', $row);
		$this->assertEquals($carddata, $row['carddata']);
		$this->assertEquals(1, $result->numRows());

		// Try to insert a new row
		$result = OC_DB::insertIfNotExist('*PREFIX*'.$this->table2,
			array(
				'fullname' => $fullname,
				'uri' => $uri,
			));
		$this->assertEquals(0, $result);

		$query = OC_DB::prepare('SELECT `fullname`, `uri`, `carddata` FROM `*PREFIX*'.$this->table2.'` WHERE `uri` = ?');
		$result = $query->execute(array($uri));
		$this->assertTrue((bool)$result);
		$row = $result->fetchRow();
		$this->assertArrayHasKey('carddata', $row);
		// Test that previously inserted data isn't overwritten
		$this->assertEquals($carddata, $row['carddata']);
		// And that a new row hasn't been inserted.
		$this->assertEquals(1, $result->numRows());

	}

	/**
	* Tests whether the database is configured so it accepts and returns dates
	* in the expected format.
	*/
	public function testTimestampDateFormat() {
		$table = '*PREFIX*'.$this->test_prefix.'timestamp';
		$column = 'timestamptest';

		$expectedFormat = 'Y-m-d H:i:s';
		$expected = new \DateTime;

		$query = OC_DB::prepare("INSERT INTO `$table` (`$column`) VALUES (?)");
		$result = $query->execute(array($expected->format($expectedFormat)));
		$this->assertEquals(
			1,
			$result,
			"Database failed to accept dates in the format '$expectedFormat'."
		);

		$id = OC_DB::insertid($table);
		$query = OC_DB::prepare("SELECT * FROM `$table` WHERE `id` = ?");
		$result = $query->execute(array($id));
		$row = $result->fetchRow();

		$actual = \DateTime::createFromFormat($expectedFormat, $row[$column]);
		$this->assertInstanceOf(
			'\DateTime',
			$actual,
			"Database failed to return dates in the format '$expectedFormat'."
		);

		$this->assertEquals(
			$expected,
			$actual,
			'Failed asserting that the returned date is the same as the inserted.'
		);
	}
}
