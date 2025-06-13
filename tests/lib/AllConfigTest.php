<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test;

/**
 * Class AllConfigTest
 *
 * @group DB
 *
 * @package Test
 */
use OC\AllConfig;
use OC\SystemConfig;
use OCP\IDBConnection;
use OCP\PreConditionNotMetException;
use OCP\Server;

class AllConfigTest extends \Test\TestCase {
	/** @var \OCP\IDBConnection */
	protected $connection;

	protected function getConfig($systemConfig = null, $connection = null) {
		if ($this->connection === null) {
			$this->connection = Server::get(IDBConnection::class);
		}
		if ($connection === null) {
			$connection = $this->connection;
		}
		if ($systemConfig === null) {
			$systemConfig = $this->getMockBuilder('\OC\SystemConfig')
				->disableOriginalConstructor()
				->getMock();
		}
		return new AllConfig($systemConfig, $connection);
	}

	public function testDeleteUserValue(): void {
		$config = $this->getConfig();

		// preparation - add something to the database
		$this->connection->executeUpdate(
			'INSERT INTO `*PREFIX*preferences` (`userid`, `appid`, ' .
			'`configkey`, `configvalue`) VALUES (?, ?, ?, ?)',
			['userDelete', 'appDelete', 'keyDelete', 'valueDelete']
		);

		$config->deleteUserValue('userDelete', 'appDelete', 'keyDelete');

		$result = $this->connection->executeQuery(
			'SELECT COUNT(*) AS `count` FROM `*PREFIX*preferences` WHERE `userid` = ?',
			['userDelete']
		)->fetch();
		$actualCount = $result['count'];

		$this->assertEquals(0, $actualCount, 'There was one value in the database and after the tests there should be no entry left.');
	}

	public function testSetUserValue(): void {
		$selectAllSQL = 'SELECT `userid`, `appid`, `configkey`, `configvalue` FROM `*PREFIX*preferences` WHERE `userid` = ?';
		$config = $this->getConfig();

		$config->setUserValue('userSet', 'appSet', 'keySet', 'valueSet');

		$result = $this->connection->executeQuery($selectAllSQL, ['userSet'])->fetchAll();

		$this->assertEquals(1, count($result));
		$this->assertEquals([
			'userid' => 'userSet',
			'appid' => 'appSet',
			'configkey' => 'keySet',
			'configvalue' => 'valueSet'
		], $result[0]);

		// test if the method overwrites existing database entries
		$config->setUserValue('userSet', 'appSet', 'keySet', 'valueSet2');

		$result = $this->connection->executeQuery($selectAllSQL, ['userSet'])->fetchAll();

		$this->assertEquals(1, count($result));
		$this->assertEquals([
			'userid' => 'userSet',
			'appid' => 'appSet',
			'configkey' => 'keySet',
			'configvalue' => 'valueSet2'
		], $result[0]);

		// cleanup - it therefore relies on the successful execution of the previous test
		$config->deleteUserValue('userSet', 'appSet', 'keySet');
	}

	public function testSetUserValueWithPreCondition(): void {
		$config = $this->getConfig();

		$selectAllSQL = 'SELECT `userid`, `appid`, `configkey`, `configvalue` FROM `*PREFIX*preferences` WHERE `userid` = ?';

		$config->setUserValue('userPreCond', 'appPreCond', 'keyPreCond', 'valuePreCond');

		$result = $this->connection->executeQuery($selectAllSQL, ['userPreCond'])->fetchAll();

		$this->assertEquals(1, count($result));
		$this->assertEquals([
			'userid' => 'userPreCond',
			'appid' => 'appPreCond',
			'configkey' => 'keyPreCond',
			'configvalue' => 'valuePreCond'
		], $result[0]);

		// test if the method overwrites existing database entries with valid precond
		$config->setUserValue('userPreCond', 'appPreCond', 'keyPreCond', 'valuePreCond2', 'valuePreCond');

		$result = $this->connection->executeQuery($selectAllSQL, ['userPreCond'])->fetchAll();

		$this->assertEquals(1, count($result));
		$this->assertEquals([
			'userid' => 'userPreCond',
			'appid' => 'appPreCond',
			'configkey' => 'keyPreCond',
			'configvalue' => 'valuePreCond2'
		], $result[0]);

		// cleanup
		$config->deleteUserValue('userPreCond', 'appPreCond', 'keyPreCond');
	}

	public static function dataSetUserValueUnexpectedValue(): array {
		return [
			[true],
			[false],
			[null],
			[new \stdClass()],
		];
	}

	/**
	 * @dataProvider dataSetUserValueUnexpectedValue
	 * @param mixed $value
	 */
	public function testSetUserValueUnexpectedValue($value): void {
		$this->expectException(\UnexpectedValueException::class);

		$config = $this->getConfig();
		$config->setUserValue('userSetBool', 'appSetBool', 'keySetBool', $value);
	}


	public function testSetUserValueWithPreConditionFailure(): void {
		$this->expectException(PreConditionNotMetException::class);

		$config = $this->getConfig();

		$selectAllSQL = 'SELECT `userid`, `appid`, `configkey`, `configvalue` FROM `*PREFIX*preferences` WHERE `userid` = ?';

		$config->setUserValue('userPreCond1', 'appPreCond', 'keyPreCond', 'valuePreCond');

		$result = $this->connection->executeQuery($selectAllSQL, ['userPreCond1'])->fetchAll();

		$this->assertEquals(1, count($result));
		$this->assertEquals([
			'userid' => 'userPreCond1',
			'appid' => 'appPreCond',
			'configkey' => 'keyPreCond',
			'configvalue' => 'valuePreCond'
		], $result[0]);

		// test if the method overwrites existing database entries with valid precond
		$config->setUserValue('userPreCond1', 'appPreCond', 'keyPreCond', 'valuePreCond2', 'valuePreCond3');

		$result = $this->connection->executeQuery($selectAllSQL, ['userPreCond1'])->fetchAll();

		$this->assertEquals(1, count($result));
		$this->assertEquals([
			'userid' => 'userPreCond1',
			'appid' => 'appPreCond',
			'configkey' => 'keyPreCond',
			'configvalue' => 'valuePreCond'
		], $result[0]);

		// cleanup
		$config->deleteUserValue('userPreCond1', 'appPreCond', 'keyPreCond');
	}

	public function testSetUserValueWithPreConditionFailureWhenResultStillMatches(): void {
		$this->expectException(PreConditionNotMetException::class);

		$config = $this->getConfig();

		$selectAllSQL = 'SELECT `userid`, `appid`, `configkey`, `configvalue` FROM `*PREFIX*preferences` WHERE `userid` = ?';

		$config->setUserValue('userPreCond1', 'appPreCond', 'keyPreCond', 'valuePreCond');

		$result = $this->connection->executeQuery($selectAllSQL, ['userPreCond1'])->fetchAll();

		$this->assertCount(1, $result);
		$this->assertEquals([
			'userid' => 'userPreCond1',
			'appid' => 'appPreCond',
			'configkey' => 'keyPreCond',
			'configvalue' => 'valuePreCond'
		], $result[0]);

		// test if the method throws with invalid precondition when the value is the same
		$config->setUserValue('userPreCond1', 'appPreCond', 'keyPreCond', 'valuePreCond', 'valuePreCond3');

		$result = $this->connection->executeQuery($selectAllSQL, ['userPreCond1'])->fetchAll();

		$this->assertCount(1, $result);
		$this->assertEquals([
			'userid' => 'userPreCond1',
			'appid' => 'appPreCond',
			'configkey' => 'keyPreCond',
			'configvalue' => 'valuePreCond'
		], $result[0]);

		// cleanup
		$config->deleteUserValue('userPreCond1', 'appPreCond', 'keyPreCond');
	}

	public function testSetUserValueUnchanged(): void {
		// TODO - FIXME until the dependency injection is handled properly (in AllConfig)
		$this->markTestSkipped('Skipped because this is just testable if database connection can be injected');

		$resultMock = $this->getMockBuilder('\Doctrine\DBAL\Driver\Statement')
			->disableOriginalConstructor()->getMock();
		$resultMock->expects($this->once())
			->method('fetchColumn')
			->willReturn('valueSetUnchanged');

		$connectionMock = $this->createMock(IDBConnection::class);
		$connectionMock->expects($this->once())
			->method('executeQuery')
			->with($this->equalTo('SELECT `configvalue` FROM `*PREFIX*preferences` ' .
					'WHERE `userid` = ? AND `appid` = ? AND `configkey` = ?'),
				$this->equalTo(['userSetUnchanged', 'appSetUnchanged', 'keySetUnchanged']))
			->willReturn($resultMock);
		$connectionMock->expects($this->never())
			->method('executeUpdate');

		$config = $this->getConfig(null, $connectionMock);

		$config->setUserValue('userSetUnchanged', 'appSetUnchanged', 'keySetUnchanged', 'valueSetUnchanged');
	}

	public function testGetUserValue(): void {
		$config = $this->getConfig();

		// setup - it therefore relies on the successful execution of the previous test
		$config->setUserValue('userGet', 'appGet', 'keyGet', 'valueGet');
		$value = $config->getUserValue('userGet', 'appGet', 'keyGet');

		$this->assertEquals('valueGet', $value);

		$result = $this->connection->executeQuery(
			'SELECT `userid`, `appid`, `configkey`, `configvalue` FROM `*PREFIX*preferences` WHERE `userid` = ?',
			['userGet']
		)->fetchAll();

		$this->assertEquals(1, count($result));
		$this->assertEquals([
			'userid' => 'userGet',
			'appid' => 'appGet',
			'configkey' => 'keyGet',
			'configvalue' => 'valueGet'
		], $result[0]);

		// drop data from database - but the config option should be cached in the config object
		$this->connection->executeUpdate('DELETE FROM `*PREFIX*preferences` WHERE `userid` = ?', ['userGet']);

		// testing the caching mechanism
		$value = $config->getUserValue('userGet', 'appGet', 'keyGet');

		$this->assertEquals('valueGet', $value);

		$result = $this->connection->executeQuery(
			'SELECT `userid`, `appid`, `configkey`, `configvalue` FROM `*PREFIX*preferences` WHERE `userid` = ?',
			['userGet']
		)->fetchAll();

		$this->assertEquals(0, count($result));
	}

	public function testGetUserKeys(): void {
		$config = $this->getConfig();

		// preparation - add something to the database
		$data = [
			['userFetch', 'appFetch1', 'keyFetch1', 'value1'],
			['userFetch', 'appFetch1', 'keyFetch2', 'value2'],
			['userFetch', 'appFetch2', 'keyFetch3', 'value3'],
			['userFetch', 'appFetch1', 'keyFetch4', 'value4'],
			['userFetch', 'appFetch4', 'keyFetch1', 'value5'],
			['userFetch', 'appFetch5', 'keyFetch1', 'value6'],
			['userFetch2', 'appFetch', 'keyFetch1', 'value7']
		];
		foreach ($data as $entry) {
			$this->connection->executeUpdate(
				'INSERT INTO `*PREFIX*preferences` (`userid`, `appid`, ' .
				'`configkey`, `configvalue`) VALUES (?, ?, ?, ?)',
				$entry
			);
		}

		$value = $config->getUserKeys('userFetch', 'appFetch1');
		$this->assertEquals(['keyFetch1', 'keyFetch2', 'keyFetch4'], $value);

		$value = $config->getUserKeys('userFetch2', 'appFetch');
		$this->assertEquals(['keyFetch1'], $value);

		// cleanup
		$this->connection->executeUpdate('DELETE FROM `*PREFIX*preferences`');
	}

	public function testGetUserKeysAllInts(): void {
		$config = $this->getConfig();

		// preparation - add something to the database
		$data = [
			['userFetch8', 'appFetch1', '123', 'value'],
			['userFetch8', 'appFetch1', '456', 'value'],
		];
		foreach ($data as $entry) {
			$this->connection->executeUpdate(
				'INSERT INTO `*PREFIX*preferences` (`userid`, `appid`, ' .
				'`configkey`, `configvalue`) VALUES (?, ?, ?, ?)',
				$entry
			);
		}

		$value = $config->getUserKeys('userFetch8', 'appFetch1');
		$this->assertEquals(['123', '456'], $value);
		$this->assertIsString($value[0]);
		$this->assertIsString($value[1]);

		// cleanup
		$this->connection->executeUpdate('DELETE FROM `*PREFIX*preferences`');
	}

	public function testGetUserValueDefault(): void {
		$config = $this->getConfig();

		$this->assertEquals('', $config->getUserValue('userGetUnset', 'appGetUnset', 'keyGetUnset'));
		$this->assertEquals(null, $config->getUserValue('userGetUnset', 'appGetUnset', 'keyGetUnset', null));
		$this->assertEquals('foobar', $config->getUserValue('userGetUnset', 'appGetUnset', 'keyGetUnset', 'foobar'));
	}

	public function testGetUserValueForUsers(): void {
		$config = $this->getConfig();

		// preparation - add something to the database
		$data = [
			['userFetch1', 'appFetch2', 'keyFetch1', 'value1'],
			['userFetch2', 'appFetch2', 'keyFetch1', 'value2'],
			['userFetch3', 'appFetch2', 'keyFetch1', 3],
			['userFetch4', 'appFetch2', 'keyFetch1', 'value4'],
			['userFetch5', 'appFetch2', 'keyFetch1', 'value5'],
			['userFetch6', 'appFetch2', 'keyFetch1', 'value6'],
			['userFetch7', 'appFetch2', 'keyFetch1', 'value7']
		];
		foreach ($data as $entry) {
			$this->connection->executeUpdate(
				'INSERT INTO `*PREFIX*preferences` (`userid`, `appid`, ' .
				'`configkey`, `configvalue`) VALUES (?, ?, ?, ?)',
				$entry
			);
		}

		$value = $config->getUserValueForUsers('appFetch2', 'keyFetch1',
			['userFetch1', 'userFetch2', 'userFetch3', 'userFetch5']);
		$this->assertEquals([
			'userFetch1' => 'value1',
			'userFetch2' => 'value2',
			'userFetch3' => 3,
			'userFetch5' => 'value5'
		], $value);

		$value = $config->getUserValueForUsers('appFetch2', 'keyFetch1',
			['userFetch1', 'userFetch4', 'userFetch9']);
		$this->assertEquals([
			'userFetch1' => 'value1',
			'userFetch4' => 'value4'
		], $value, 'userFetch9 is an non-existent user and should not be shown.');

		// cleanup
		$this->connection->executeUpdate('DELETE FROM `*PREFIX*preferences`');
	}

	public function testDeleteAllUserValues(): void {
		$config = $this->getConfig();

		// preparation - add something to the database
		$data = [
			['userFetch3', 'appFetch1', 'keyFetch1', 'value1'],
			['userFetch3', 'appFetch1', 'keyFetch2', 'value2'],
			['userFetch3', 'appFetch2', 'keyFetch3', 'value3'],
			['userFetch3', 'appFetch1', 'keyFetch4', 'value4'],
			['userFetch3', 'appFetch4', 'keyFetch1', 'value5'],
			['userFetch3', 'appFetch5', 'keyFetch1', 'value6'],
			['userFetch4', 'appFetch2', 'keyFetch1', 'value7']
		];
		foreach ($data as $entry) {
			$this->connection->executeUpdate(
				'INSERT INTO `*PREFIX*preferences` (`userid`, `appid`, ' .
				'`configkey`, `configvalue`) VALUES (?, ?, ?, ?)',
				$entry
			);
		}

		$config->deleteAllUserValues('userFetch3');

		$result = $this->connection->executeQuery(
			'SELECT COUNT(*) AS `count` FROM `*PREFIX*preferences`'
		)->fetch();
		$actualCount = $result['count'];

		$this->assertEquals(1, $actualCount, 'After removing `userFetch3` there should be exactly 1 entry left.');

		// cleanup
		$this->connection->executeUpdate('DELETE FROM `*PREFIX*preferences`');
	}

	public function testDeleteAppFromAllUsers(): void {
		$config = $this->getConfig();

		// preparation - add something to the database
		$data = [
			['userFetch5', 'appFetch1', 'keyFetch1', 'value1'],
			['userFetch5', 'appFetch1', 'keyFetch2', 'value2'],
			['userFetch5', 'appFetch2', 'keyFetch3', 'value3'],
			['userFetch5', 'appFetch1', 'keyFetch4', 'value4'],
			['userFetch5', 'appFetch4', 'keyFetch1', 'value5'],
			['userFetch5', 'appFetch5', 'keyFetch1', 'value6'],
			['userFetch6', 'appFetch2', 'keyFetch1', 'value7']
		];
		foreach ($data as $entry) {
			$this->connection->executeUpdate(
				'INSERT INTO `*PREFIX*preferences` (`userid`, `appid`, ' .
				'`configkey`, `configvalue`) VALUES (?, ?, ?, ?)',
				$entry
			);
		}

		$config->deleteAppFromAllUsers('appFetch1');

		$result = $this->connection->executeQuery(
			'SELECT COUNT(*) AS `count` FROM `*PREFIX*preferences`'
		)->fetch();
		$actualCount = $result['count'];

		$this->assertEquals(4, $actualCount, 'After removing `appFetch1` there should be exactly 4 entries left.');

		$config->deleteAppFromAllUsers('appFetch2');

		$result = $this->connection->executeQuery(
			'SELECT COUNT(*) AS `count` FROM `*PREFIX*preferences`'
		)->fetch();
		$actualCount = $result['count'];

		$this->assertEquals(2, $actualCount, 'After removing `appFetch2` there should be exactly 2 entries left.');

		// cleanup
		$this->connection->executeUpdate('DELETE FROM `*PREFIX*preferences`');
	}

	public function testGetUsersForUserValue(): void {
		// mock the check for the database to run the correct SQL statements for each database type
		$systemConfig = $this->getMockBuilder('\OC\SystemConfig')
			->disableOriginalConstructor()
			->getMock();
		$config = $this->getConfig($systemConfig);

		// preparation - add something to the database
		$data = [
			['user1', 'appFetch9', 'keyFetch9', 'value9'],
			['user2', 'appFetch9', 'keyFetch9', 'value9'],
			['user3', 'appFetch9', 'keyFetch9', 'value8'],
			['user4', 'appFetch9', 'keyFetch8', 'value9'],
			['user5', 'appFetch8', 'keyFetch9', 'value9'],
			['user6', 'appFetch9', 'keyFetch9', 'value9'],
		];
		foreach ($data as $entry) {
			$this->connection->executeUpdate(
				'INSERT INTO `*PREFIX*preferences` (`userid`, `appid`, ' .
				'`configkey`, `configvalue`) VALUES (?, ?, ?, ?)',
				$entry
			);
		}

		$value = $config->getUsersForUserValue('appFetch9', 'keyFetch9', 'value9');
		$this->assertEquals(['user1', 'user2', 'user6'], $value);

		// cleanup
		$this->connection->executeUpdate('DELETE FROM `*PREFIX*preferences`');
	}

	public function testGetUsersForUserValueCaseInsensitive(): void {
		// mock the check for the database to run the correct SQL statements for each database type
		$systemConfig = $this->createMock(SystemConfig::class);
		$config = $this->getConfig($systemConfig);

		$config->setUserValue('user1', 'myApp', 'myKey', 'test123');
		$config->setUserValue('user2', 'myApp', 'myKey', 'TEST123');
		$config->setUserValue('user3', 'myApp', 'myKey', 'test12345');

		$users = $config->getUsersForUserValueCaseInsensitive('myApp', 'myKey', 'test123');
		$this->assertSame(2, count($users));
		$this->assertSame(['user1', 'user2'], $users);
	}
}
