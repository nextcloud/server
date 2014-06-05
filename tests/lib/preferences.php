<?php
/**
 * Copyright (c) 2013 Christopher Schäpers <christopher@schaepers.it>
 * Copyright (c) 2013 Bart Visscher <bartv@thisnet.nl>
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
		$this->assertEquals(0, count($result->fetchAll()));
	}

	public function testDeleteApp() {
		$this->assertTrue(\OC_Preferences::deleteApp('Deleteuser', 'deleteapp'));
		$query = \OC_DB::prepare('SELECT `configvalue` FROM `*PREFIX*preferences` WHERE `userid` = ? AND `appid` = ?');
		$result = $query->execute(array('Deleteuser', 'deleteapp'));
		$this->assertEquals(0, count($result->fetchAll()));
	}

	public function testDeleteUser() {
		$this->assertTrue(\OC_Preferences::deleteUser('Deleteuser'));
		$query = \OC_DB::prepare('SELECT `configvalue` FROM `*PREFIX*preferences` WHERE `userid` = ?');
		$result = $query->execute(array('Deleteuser'));
		$this->assertEquals(0, count($result->fetchAll()));
	}

	public function testDeleteAppFromAllUsers() {
		$this->assertTrue(\OC_Preferences::deleteAppFromAllUsers('someapp'));
		$query = \OC_DB::prepare('SELECT `configvalue` FROM `*PREFIX*preferences` WHERE `appid` = ?');
		$result = $query->execute(array('someapp'));
		$this->assertEquals(0, count($result->fetchAll()));
	}
}

class Test_Preferences_Object extends PHPUnit_Framework_TestCase {
	public function testGetUsers()
	{
		$statementMock = $this->getMock('\Doctrine\DBAL\Statement', array(), array(), '', false);
		$statementMock->expects($this->exactly(2))
			->method('fetchColumn')
			->will($this->onConsecutiveCalls('foo', false));
		$connectionMock = $this->getMock('\OC\DB\Connection', array(), array(), '', false);
		$connectionMock->expects($this->once())
			->method('executeQuery')
			->with($this->equalTo('SELECT DISTINCT `userid` FROM `*PREFIX*preferences`'))
			->will($this->returnValue($statementMock));

		$preferences = new OC\Preferences($connectionMock);
		$apps = $preferences->getUsers();
		$this->assertEquals(array('foo'), $apps);
	}

	public function testSetValue()
	{
		$connectionMock = $this->getMock('\OC\DB\Connection', array(), array(), '', false);
		$connectionMock->expects($this->exactly(2))
			->method('fetchColumn')
			->with($this->equalTo('SELECT COUNT(*) FROM `*PREFIX*preferences`'
				.' WHERE `userid` = ? AND `appid` = ? AND `configkey` = ?'),
				$this->equalTo(array('grg', 'bar', 'foo')))
			->will($this->onConsecutiveCalls(0, 1));
		$connectionMock->expects($this->once())
			->method('insert')
			->with($this->equalTo('*PREFIX*preferences'),
				$this->equalTo(
					array(
						'userid' => 'grg',
						'appid' => 'bar',
						'configkey' => 'foo',
						'configvalue' => 'v1',
					)
				));
		$connectionMock->expects($this->once())
			->method('update')
			->with($this->equalTo('*PREFIX*preferences'),
				$this->equalTo(
					array(
						'configvalue' => 'v2',
					)),
				$this->equalTo(
					array(
						'userid' => 'grg',
						'appid' => 'bar',
						'configkey' => 'foo',
					)
				));

		$preferences = new OC\Preferences($connectionMock);
		$preferences->setValue('grg', 'bar', 'foo', 'v1');
		$preferences->setValue('grg', 'bar', 'foo', 'v2');
	}

	public function testGetUserValues()
	{
		$query = \OC_DB::prepare('INSERT INTO `*PREFIX*preferences` VALUES(?, ?, ?, ?)');
		$query->execute(array('SomeUser', 'testGetUserValues', 'somekey', 'somevalue'));
		$query->execute(array('AnotherUser', 'testGetUserValues', 'somekey', 'someothervalue'));
		$query->execute(array('AUser', 'testGetUserValues', 'somekey', 'somevalue'));

		$preferences = new OC\Preferences(\OC_DB::getConnection());
		$users = array('SomeUser', 'AnotherUser', 'NoValueSet');

		$values = $preferences->getValueForUsers('testGetUserValues', 'somekey', $users);
		$this->assertUserValues($values);

		// Add a lot of users so the array is chunked
		for ($i = 1; $i <= 75; $i++) {
			array_unshift($users, 'NoValueBefore#' . $i);
			array_push($users, 'NoValueAfter#' . $i);
		}

		$values = $preferences->getValueForUsers('testGetUserValues', 'somekey', $users);
		$this->assertUserValues($values);

		// Clean DB after the test
		$query = \OC_DB::prepare('DELETE FROM `*PREFIX*preferences` WHERE `appid` = ?');
		$query->execute(array('testGetUserValues'));
	}

	protected function assertUserValues($values) {
		$this->assertEquals(2, sizeof($values));

		$this->assertArrayHasKey('SomeUser', $values);
		$this->assertEquals('somevalue', $values['SomeUser']);

		$this->assertArrayHasKey('AnotherUser', $values);
		$this->assertEquals('someothervalue', $values['AnotherUser']);
	}

	public function testGetValueUsers()
	{
		// Prepare data
		$query = \OC_DB::prepare('INSERT INTO `*PREFIX*preferences` VALUES(?, ?, ?, ?)');
		$query->execute(array('SomeUser', 'testGetUsersForValue', 'somekey', 'somevalue'));
		$query->execute(array('AnotherUser', 'testGetUsersForValue', 'somekey', 'someothervalue'));
		$query->execute(array('AUser', 'testGetUsersForValue', 'somekey', 'somevalue'));

		$preferences = new OC\Preferences(\OC_DB::getConnection());
		$result = $preferences->getUsersForValue('testGetUsersForValue', 'somekey', 'somevalue');
		sort($result);
		$this->assertEquals(array('AUser', 'SomeUser'), $result);

		// Clean DB after the test
		$query = \OC_DB::prepare('DELETE FROM `*PREFIX*preferences` WHERE `appid` = ?');
		$query->execute(array('testGetUsersForValue'));
	}

	public function testDeleteKey()
	{
		$connectionMock = $this->getMock('\OC\DB\Connection', array(), array(), '', false);
		$connectionMock->expects($this->once())
			->method('delete')
			->with($this->equalTo('*PREFIX*preferences'),
				$this->equalTo(
					array(
						'userid' => 'grg',
						'appid' => 'bar',
						'configkey' => 'foo',
					)
				));

		$preferences = new OC\Preferences($connectionMock);
		$preferences->deleteKey('grg', 'bar', 'foo');
	}

	public function testDeleteApp()
	{
		$connectionMock = $this->getMock('\OC\DB\Connection', array(), array(), '', false);
		$connectionMock->expects($this->once())
			->method('delete')
			->with($this->equalTo('*PREFIX*preferences'),
				$this->equalTo(
					array(
						'userid' => 'grg',
						'appid' => 'bar',
					)
				));

		$preferences = new OC\Preferences($connectionMock);
		$preferences->deleteApp('grg', 'bar');
	}

	public function testDeleteUser()
	{
		$connectionMock = $this->getMock('\OC\DB\Connection', array(), array(), '', false);
		$connectionMock->expects($this->once())
			->method('delete')
			->with($this->equalTo('*PREFIX*preferences'),
				$this->equalTo(
					array(
						'userid' => 'grg',
					)
				));

		$preferences = new OC\Preferences($connectionMock);
		$preferences->deleteUser('grg');
	}

	public function testDeleteAppFromAllUsers()
	{
		$connectionMock = $this->getMock('\OC\DB\Connection', array(), array(), '', false);
		$connectionMock->expects($this->once())
			->method('delete')
			->with($this->equalTo('*PREFIX*preferences'),
				$this->equalTo(
					array(
						'appid' => 'bar',
					)
				));

		$preferences = new OC\Preferences($connectionMock);
		$preferences->deleteAppFromAllUsers('bar');
	}
}
