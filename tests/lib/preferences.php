<?php
/**
 * Copyright (c) 2013 Christopher Schäpers <christopher@schaepers.it>
 * Copyright (c) 2013 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

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
			->method('executeUpdate')
			->with($this->equalTo("UPDATE `*PREFIX*preferences` SET `configvalue` = ?"
						. " WHERE `userid` = ? AND `appid` = ? AND `configkey` = ?"),
				$this->equalTo(array('v2', 'grg', 'bar', 'foo'))
				);

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
			->method('executeUpdate');

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
			->method('executeUpdate')
			->with($this->equalTo("UPDATE `*PREFIX*preferences` SET `configvalue` = ?"
						. " WHERE `userid` = ? AND `appid` = ? AND `configkey` = ?"),
				$this->equalTo(array('v2', 'grg', 'bar', 'foo'))
				);

		$preferences = new OC\Preferences($connectionMock);
		$preferences->setValue('grg', 'bar', 'foo', 'v1');
		$preferences->setValue('grg', 'bar', 'foo', 'v2');
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
