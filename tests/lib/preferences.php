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

	public function testGetApps()
	{
		$statementMock = $this->getMock('\Doctrine\DBAL\Statement', array(), array(), '', false);
		$statementMock->expects($this->exactly(2))
			->method('fetchColumn')
			->will($this->onConsecutiveCalls('foo', false));
		$connectionMock = $this->getMock('\OC\DB\Connection', array(), array(), '', false);
		$connectionMock->expects($this->once())
			->method('executeQuery')
			->with($this->equalTo('SELECT DISTINCT `appid` FROM `*PREFIX*preferences` WHERE `userid` = ?'),
				$this->equalTo(array('bar')))
			->will($this->returnValue($statementMock));

		$preferences = new OC\Preferences($connectionMock);
		$apps = $preferences->getApps('bar');
		$this->assertEquals(array('foo'), $apps);
	}

	public function testGetKeys()
	{
		$statementMock = $this->getMock('\Doctrine\DBAL\Statement', array(), array(), '', false);
		$statementMock->expects($this->exactly(2))
			->method('fetchColumn')
			->will($this->onConsecutiveCalls('foo', false));
		$connectionMock = $this->getMock('\OC\DB\Connection', array(), array(), '', false);
		$connectionMock->expects($this->once())
			->method('executeQuery')
			->with($this->equalTo('SELECT `configkey` FROM `*PREFIX*preferences` WHERE `userid` = ? AND `appid` = ?'),
				$this->equalTo(array('bar', 'moo')))
			->will($this->returnValue($statementMock));

		$preferences = new OC\Preferences($connectionMock);
		$keys = $preferences->getKeys('bar', 'moo');
		$this->assertEquals(array('foo'), $keys);
	}

	public function testGetValue()
	{
		$connectionMock = $this->getMock('\OC\DB\Connection', array(), array(), '', false);
		$connectionMock->expects($this->exactly(2))
			->method('fetchAssoc')
			->with($this->equalTo('SELECT `configvalue` FROM `*PREFIX*preferences` WHERE `userid` = ? AND `appid` = ? AND `configkey` = ?'),
				$this->equalTo(array('grg', 'bar', 'red')))
			->will($this->onConsecutiveCalls(array('configvalue'=>'foo'), null));

		$preferences = new OC\Preferences($connectionMock);
		$value = $preferences->getValue('grg', 'bar', 'red');
		$this->assertEquals('foo', $value);
		$value = $preferences->getValue('grg', 'bar', 'red', 'def');
		$this->assertEquals('def', $value);
	}

	public function testSetValue()
	{
		$connectionMock = $this->getMock('\OC\DB\Connection', array(), array(), '', false);
		$connectionMock->expects($this->exactly(2))
			->method('fetchColumn')
			->with($this->equalTo('SELECT `configvalue` FROM `*PREFIX*preferences`'
				.' WHERE `userid` = ? AND `appid` = ? AND `configkey` = ?'),
				$this->equalTo(array('grg', 'bar', 'foo')))
			->will($this->onConsecutiveCalls(false, 'v1'));
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

	public function testSetValueUnchanged() {
		$connectionMock = $this->getMock('\OC\DB\Connection', array(), array(), '', false);
		$connectionMock->expects($this->exactly(3))
			->method('fetchColumn')
			->with($this->equalTo('SELECT `configvalue` FROM `*PREFIX*preferences`'
				.' WHERE `userid` = ? AND `appid` = ? AND `configkey` = ?'),
				$this->equalTo(array('grg', 'bar', 'foo')))
			->will($this->onConsecutiveCalls(false, 'v1', 'v1'));
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
		$connectionMock->expects($this->never())
			->method('update');

		$preferences = new OC\Preferences($connectionMock);
		$preferences->setValue('grg', 'bar', 'foo', 'v1');
		$preferences->setValue('grg', 'bar', 'foo', 'v1');
		$preferences->setValue('grg', 'bar', 'foo', 'v1');
	}

	public function testSetValueUnchanged2() {
		$connectionMock = $this->getMock('\OC\DB\Connection', array(), array(), '', false);
		$connectionMock->expects($this->exactly(3))
			->method('fetchColumn')
			->with($this->equalTo('SELECT `configvalue` FROM `*PREFIX*preferences`'
				.' WHERE `userid` = ? AND `appid` = ? AND `configkey` = ?'),
				$this->equalTo(array('grg', 'bar', 'foo')))
			->will($this->onConsecutiveCalls(false, 'v1', 'v2'));
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
		$preferences->setValue('grg', 'bar', 'foo', 'v2');
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
