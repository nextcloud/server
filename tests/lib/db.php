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

	/**
	 * @var string
	 */
	private $table1;

	/**
	 * @var string
	 */
	private $table2;

	/**
	 * @var string
	 */
	private $table3;

	public function setUp() {
		$dbfile = OC::$SERVERROOT.'/tests/data/db_structure.xml';

		$r = '_'.OC_Util::generateRandomBytes(4).'_';
		$content = file_get_contents( $dbfile );
		$content = str_replace( '*dbprefix*', '*dbprefix*'.$r, $content );
		file_put_contents( self::$schema_file, $content );
		OC_DB::createDbFromStructure(self::$schema_file);

		$this->test_prefix = $r;
		$this->table1 = $this->test_prefix.'cntcts_addrsbks';
		$this->table2 = $this->test_prefix.'cntcts_cards';
		$this->table3 = $this->test_prefix.'vcategory';
		$this->table4 = $this->test_prefix.'decimal';
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

	public function testInsertIfNotExistNull() {
		$categoryentries = array(
			array('addressbookid' => 123, 'fullname' => null, 'expectedResult' => 1),
			array('addressbookid' => 123, 'fullname' => null, 'expectedResult' => 0),
			array('addressbookid' => 123, 'fullname' => 'test', 'expectedResult' => 1),
		);

		foreach($categoryentries as $entry) {
			$result = OC_DB::insertIfNotExist('*PREFIX*'.$this->table2,
				array(
					'addressbookid' => $entry['addressbookid'],
					'fullname' => $entry['fullname'],
				));
			$this->assertEquals($entry['expectedResult'], $result);
		}

		$query = OC_DB::prepare('SELECT * FROM `*PREFIX*'.$this->table2.'`');
		$result = $query->execute();
		$this->assertTrue((bool)$result);
		$this->assertEquals(2, count($result->fetchAll()));
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
		$rowset = $result->fetchAll();
		$this->assertEquals(1, count($rowset));
		$this->assertArrayHasKey('carddata', $rowset[0]);
		$this->assertEquals($carddata, $rowset[0]['carddata']);

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
		// Test that previously inserted data isn't overwritten
		// And that a new row hasn't been inserted.
		$rowset = $result->fetchAll();
		$this->assertEquals(1, count($rowset));
		$this->assertArrayHasKey('carddata', $rowset[0]);
		$this->assertEquals($carddata, $rowset[0]['carddata']);
	}

	public function testUtf8Data() {
		$table = "*PREFIX*{$this->table2}";
		$expected = "Ћö雙喜\xE2\x80\xA2";

		$query = OC_DB::prepare("INSERT INTO `$table` (`fullname`, `uri`, `carddata`) VALUES (?, ?, ?)");
		$result = $query->execute(array($expected, 'uri_1', 'This is a vCard'));
		$this->assertEquals(1, $result);

		$actual = OC_DB::prepare("SELECT `fullname` FROM `$table`")->execute()->fetchOne();
		$this->assertSame($expected, $actual);
	}

	public function testDecimal() {
		$table = "*PREFIX*" . $this->table4;
		$rowname = 'decimaltest';

		// Insert, select and delete decimal(12,2) values
		$inserts = array('1337133713.37', '1234567890');
		$expects = array('1337133713.37', '1234567890.00');

		for ($i = 0; $i < count($inserts); $i++) {
			$insert = $inserts[$i];
			$expect = $expects[$i];

			$query = OC_DB::prepare('INSERT INTO `' . $table . '` (`' . $rowname . '`) VALUES (?)');
			$result = $query->execute(array($insert));
			$this->assertEquals(1, $result);
			$query = OC_DB::prepare('SELECT `' . $rowname . '` FROM `' . $table . '`');
			$result = $query->execute();
			$this->assertTrue((bool)$result);
			$row = $result->fetchRow();
			$this->assertArrayHasKey($rowname, $row);
			$this->assertEquals($expect, $row[$rowname]);
			$query = OC_DB::prepare('DELETE FROM `' . $table . '`');
			$result = $query->execute();
			$this->assertTrue((bool)$result);
		}
	}

	public function testUpdateAffectedRowsNoMatch() {
		$this->insertCardData('fullname1', 'uri1');
		// The WHERE clause does not match any rows
		$this->assertSame(0, $this->updateCardData('fullname3', 'uri2'));
	}

	public function testUpdateAffectedRowsDifferent() {
		$this->insertCardData('fullname1', 'uri1');
		// The WHERE clause matches a single row and the value we are updating
		// is different from the one already present.
		$this->assertSame(1, $this->updateCardData('fullname1', 'uri2'));
	}

	public function testUpdateAffectedRowsSame() {
		$this->insertCardData('fullname1', 'uri1');
		// The WHERE clause matches a single row and the value we are updating
		// to is the same as the one already present. MySQL reports 0 here when
		// the PDO::MYSQL_ATTR_FOUND_ROWS flag is not specified.
		$this->assertSame(1, $this->updateCardData('fullname1', 'uri1'));
	}

	public function testUpdateAffectedRowsMultiple() {
		$this->insertCardData('fullname1', 'uri1');
		$this->insertCardData('fullname2', 'uri2');
		// The WHERE clause matches two rows. One row contains a value that
		// needs to be updated, the other one already contains the value we are
		// updating to. MySQL reports 1 here when the PDO::MYSQL_ATTR_FOUND_ROWS
		// flag is not specified.
		$query = OC_DB::prepare("UPDATE `*PREFIX*{$this->table2}` SET `uri` = ?");
		$this->assertSame(2, $query->execute(array('uri1')));
	}

	protected function insertCardData($fullname, $uri) {
		$query = OC_DB::prepare("INSERT INTO `*PREFIX*{$this->table2}` (`fullname`, `uri`, `carddata`) VALUES (?, ?, ?)");
		$this->assertSame(1, $query->execute(array($fullname, $uri, uniqid())));
	}

	protected function updateCardData($fullname, $uri) {
		$query = OC_DB::prepare("UPDATE `*PREFIX*{$this->table2}` SET `uri` = ? WHERE `fullname` = ?");
		return $query->execute(array($uri, $fullname));
	}
}
