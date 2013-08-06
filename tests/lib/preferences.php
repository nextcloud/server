<?php
/**
 * Copyright (c) 2013 Christopher Schäpers <christopher@schaepers.it>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

class Test_Preferences extends PHPUnit_Framework_TestCase {
	public static function setUpBeforeClass() {
		$query = \OC_DB::prepare('INSERT INTO `*PREFIX*preferences` VALUES(?, ?, ?, ?)');
		$query->execute(array("Someuser", "someapp", "somekey", "somevalue"));

		$query->execute(array("Someuser", "getusersapp", "somekey", "somevalue"));
		$query->execute(array("Anotheruser", "getusersapp", "somekey", "someothervalue"));
		$query->execute(array("Anuser", "getusersapp", "somekey", "somevalue"));

		$query->execute(array("Someuser", "getappsapp", "somekey", "somevalue"));

		$query->execute(array("Someuser", "getkeysapp", "firstkey", "somevalue"));
		$query->execute(array("Someuser", "getkeysapp", "anotherkey", "somevalue"));
		$query->execute(array("Someuser", "getkeysapp", "key-tastic", "somevalue"));

		$query->execute(array("Someuser", "getvalueapp", "key", "a value for a key"));

		$query->execute(array("Deleteuser", "deleteapp", "deletekey", "somevalue"));
		$query->execute(array("Deleteuser", "deleteapp", "somekey", "somevalue"));
		$query->execute(array("Deleteuser", "someapp", "somekey", "somevalue"));
	}

	public static function tearDownAfterClass() {
		$query = \OC_DB::prepare('DELETE FROM `*PREFIX*preferences` WHERE `userid` = ?');
		$query->execute(array('Someuser'));
		$query->execute(array('Anotheruser'));
		$query->execute(array('Anuser'));
	}

	public function testGetUsers() {
		$query = \OC_DB::prepare('SELECT DISTINCT `userid` FROM `*PREFIX*preferences`');
		$result = $query->execute();
		$expected = array();
		while ($row = $result->fetchRow()) {
			$expected[] = $row['userid'];
		}

		$this->assertEquals($expected, \OC_Preferences::getUsers());
	}

	public function testGetApps() {
		$query = \OC_DB::prepare('SELECT DISTINCT `appid` FROM `*PREFIX*preferences` WHERE `userid` = ?');
		$result = $query->execute(array('Someuser'));
		$expected = array();
		while ($row = $result->fetchRow()) {
			$expected[] = $row['appid'];
		}

		$this->assertEquals($expected, \OC_Preferences::getApps('Someuser'));
	}

	public function testGetKeys() {
		$query = \OC_DB::prepare('SELECT DISTINCT `configkey` FROM `*PREFIX*preferences` WHERE `userid` = ? AND `appid` = ?');
		$result = $query->execute(array('Someuser', 'getkeysapp'));
		$expected = array();
		while ($row = $result->fetchRow()) {
			$expected[] = $row['configkey'];
		}

		$this->assertEquals($expected, \OC_Preferences::getKeys('Someuser', 'getkeysapp'));
	}

	public function testGetValue() {
		$this->assertNull(\OC_Preferences::getValue('nonexistant', 'nonexistant', 'nonexistant'));

		$this->assertEquals('default', \OC_Preferences::getValue('nonexistant', 'nonexistant', 'nonexistant', 'default'));

		$query = \OC_DB::prepare('SELECT `configvalue` FROM `*PREFIX*preferences` WHERE `userid` = ? AND `appid` = ? AND `configkey` = ?');
		$result = $query->execute(array('Someuser', 'getvalueapp', 'key'));
		$row = $result->fetchRow();
		$expected = $row['configvalue'];
		$this->assertEquals($expected, \OC_Preferences::getValue('Someuser', 'getvalueapp', 'key'));
	}

	public function testSetValue() {
		$this->assertTrue(\OC_Preferences::setValue('Someuser', 'setvalueapp', 'newkey', 'newvalue'));
		$query = \OC_DB::prepare('SELECT `configvalue` FROM `*PREFIX*preferences` WHERE `userid` = ? AND `appid` = ? AND `configkey` = ?');
		$result = $query->execute(array('Someuser', 'setvalueapp', 'newkey'));
		$row = $result->fetchRow();
		$value = $row['configvalue'];
		$this->assertEquals('newvalue', $value);

		$this->assertTrue(\OC_Preferences::setValue('Someuser', 'setvalueapp', 'newkey', 'othervalue'));
		$query = \OC_DB::prepare('SELECT `configvalue` FROM `*PREFIX*preferences` WHERE `userid` = ? AND `appid` = ? AND `configkey` = ?');
		$result = $query->execute(array('Someuser', 'setvalueapp', 'newkey'));
		$row = $result->fetchRow();
		$value = $row['configvalue'];
		$this->assertEquals('othervalue', $value);
	}

	public function testDeleteKey() {
		$this->assertTrue(\OC_Preferences::deleteKey('Deleteuser', 'deleteapp', 'deletekey'));
		$query = \OC_DB::prepare('SELECT `configvalue` FROM `*PREFIX*preferences` WHERE `userid` = ? AND `appid` = ? AND `configkey` = ?');
		$result = $query->execute(array('Deleteuser', 'deleteapp', 'deletekey'));
		$this->assertEquals(0, $result->numRows());
	}

	public function testDeleteApp() {
		$this->assertTrue(\OC_Preferences::deleteApp('Deleteuser', 'deleteapp'));
		$query = \OC_DB::prepare('SELECT `configvalue` FROM `*PREFIX*preferences` WHERE `userid` = ? AND `appid` = ?');
		$result = $query->execute(array('Deleteuser', 'deleteapp'));
		$this->assertEquals(0, $result->numRows());
	}

	public function testDeleteUser() {
		$this->assertTrue(\OC_Preferences::deleteUser('Deleteuser'));
		$query = \OC_DB::prepare('SELECT `configvalue` FROM `*PREFIX*preferences` WHERE `userid` = ?');
		$result = $query->execute(array('Deleteuser'));
		$this->assertEquals(0, $result->numRows());
	}

	public function testDeleteAppFromAllUsers() {
		$this->assertTrue(\OC_Preferences::deleteAppFromAllUsers('someapp'));
		$query = \OC_DB::prepare('SELECT `configvalue` FROM `*PREFIX*preferences` WHERE `appid` = ?');
		$result = $query->execute(array('someapp'));
		$this->assertEquals(0, $result->numRows());
	}
}
