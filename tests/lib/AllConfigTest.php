<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test;

use OC\AllConfig;
use OCP\IDBConnection;
use OCP\PreConditionNotMetException;
use OCP\Server;

/**
 * Class AllConfigTest
 *
 * @package Test
 */
#[\PHPUnit\Framework\Attributes\Group('DB')]
class AllConfigTest extends \Test\TestCase {
	/** @var IDBConnection */
	protected $connection;

	/** Insert one row into oc_preferences */
	private function insertPreferenceRow(string $userid, string $appid, string $configkey, string $configvalue): void {
		$qb = $this->connection->getQueryBuilder();
		$qb->insert('preferences')
			->values([
				'userid' => $qb->createNamedParameter($userid),
				'appid' => $qb->createNamedParameter($appid),
				'configkey' => $qb->createNamedParameter($configkey),
				'configvalue' => $qb->createNamedParameter($configvalue),
			])
			->executeStatement();
	}

	/** Return all oc_preferences rows for the given user */
	private function getPreferenceRows(string $userid): array {
		$qb = $this->connection->getQueryBuilder();
		return $qb->select('userid', 'appid', 'configkey', 'configvalue')
			->from('preferences')
			->where($qb->expr()->eq('userid', $qb->createNamedParameter($userid)))
			->executeQuery()
			->fetchAllAssociative();
	}

	/** Return the total number of rows in oc_preferences */
	private function countPreferenceRows(): int {
		$qb = $this->connection->getQueryBuilder();
		return (int)$qb->select($qb->func()->count('*'))
			->from('preferences')
			->executeQuery()
			->fetchOne();
	}

	/** Truncate oc_preferences */
	private function clearPreferences(): void {
		$this->connection->getQueryBuilder()->delete('preferences')->executeStatement();
	}

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
		$this->insertPreferenceRow('userDelete', 'appDelete', 'keyDelete', 'valueDelete');

		$config->deleteUserValue('userDelete', 'appDelete', 'keyDelete');

		$actualCount = count($this->getPreferenceRows('userDelete'));

		$this->assertEquals(0, $actualCount, 'There was one value in the database and after the tests there should be no entry left.');
	}

	public function testSetUserValue(): void {
		$config = $this->getConfig();

		$config->setUserValue('userSet', 'appSet', 'keySet', 'valueSet');

		$result = $this->getPreferenceRows('userSet');

		$this->assertEquals(1, count($result));
		$this->assertEquals([
			'userid' => 'userSet',
			'appid' => 'appSet',
			'configkey' => 'keySet',
			'configvalue' => 'valueSet'
		], $result[0]);

		// test if the method overwrites existing database entries
		$config->setUserValue('userSet', 'appSet', 'keySet', 'valueSet2');

		$result = $this->getPreferenceRows('userSet');

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

	/**
	 * This test needs to stay! Emails are expected to be lowercase due to performance reasons.
	 * This way we can skip the expensive casing change on the database.
	 */
	public function testSetUserValueSettingsEmail(): void {
		$config = $this->getConfig();

		$config->setUserValue('userSet', 'settings', 'email', 'mixed.CASE@domain.COM');

		$result = $this->getPreferenceRows('userSet');

		$this->assertEquals(1, count($result));
		$this->assertEquals([
			'userid' => 'userSet',
			'appid' => 'settings',
			'configkey' => 'email',
			'configvalue' => 'mixed.case@domain.com'
		], $result[0]);
	}

	public function testSetUserValueWithPreCondition(): void {
		$config = $this->getConfig();

		$config->setUserValue('userPreCond', 'appPreCond', 'keyPreCond', 'valuePreCond');

		$result = $this->getPreferenceRows('userPreCond');

		$this->assertEquals(1, count($result));
		$this->assertEquals([
			'userid' => 'userPreCond',
			'appid' => 'appPreCond',
			'configkey' => 'keyPreCond',
			'configvalue' => 'valuePreCond'
		], $result[0]);

		// test if the method overwrites existing database entries with valid precond
		$config->setUserValue('userPreCond', 'appPreCond', 'keyPreCond', 'valuePreCond2', 'valuePreCond');

		$result = $this->getPreferenceRows('userPreCond');

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
	 * @param mixed $value
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dataSetUserValueUnexpectedValue')]
	public function testSetUserValueUnexpectedValue($value): void {
		$this->expectException(\UnexpectedValueException::class);

		$config = $this->getConfig();
		$config->setUserValue('userSetBool', 'appSetBool', 'keySetBool', $value);
	}


	public function testSetUserValueWithPreConditionFailure(): void {
		$this->expectException(PreConditionNotMetException::class);

		$config = $this->getConfig();

		$config->setUserValue('userPreCond1', 'appPreCond', 'keyPreCond', 'valuePreCond');

		$result = $this->getPreferenceRows('userPreCond1');

		$this->assertEquals(1, count($result));
		$this->assertEquals([
			'userid' => 'userPreCond1',
			'appid' => 'appPreCond',
			'configkey' => 'keyPreCond',
			'configvalue' => 'valuePreCond'
		], $result[0]);

		// test if the method overwrites existing database entries with valid precond
		$config->setUserValue('userPreCond1', 'appPreCond', 'keyPreCond', 'valuePreCond2', 'valuePreCond3');

		$result = $this->getPreferenceRows('userPreCond1');

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

		$config->setUserValue('userPreCond1', 'appPreCond', 'keyPreCond', 'valuePreCond');

		$result = $this->getPreferenceRows('userPreCond1');

		$this->assertCount(1, $result);
		$this->assertEquals([
			'userid' => 'userPreCond1',
			'appid' => 'appPreCond',
			'configkey' => 'keyPreCond',
			'configvalue' => 'valuePreCond'
		], $result[0]);

		// test if the method throws with invalid precondition when the value is the same
		$config->setUserValue('userPreCond1', 'appPreCond', 'keyPreCond', 'valuePreCond', 'valuePreCond3');

		$result = $this->getPreferenceRows('userPreCond1');

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
			->method('fetchOne')
			->willReturn('valueSetUnchanged');

		$connectionMock = $this->createMock(IDBConnection::class);
		$connectionMock->expects($this->once())
			->method('executeQuery')
			->with($this->equalTo('SELECT `configvalue` FROM `*PREFIX*preferences` '
					. 'WHERE `userid` = ? AND `appid` = ? AND `configkey` = ?'),
				$this->equalTo(['userSetUnchanged', 'appSetUnchanged', 'keySetUnchanged']))
			->willReturn($resultMock);
		$connectionMock->expects($this->never())
			->method('executeStatement');

		$config = $this->getConfig(null, $connectionMock);

		$config->setUserValue('userSetUnchanged', 'appSetUnchanged', 'keySetUnchanged', 'valueSetUnchanged');
	}

	public function testGetUserValue(): void {
		$config = $this->getConfig();

		// setup - it therefore relies on the successful execution of the previous test
		$config->setUserValue('userGet', 'appGet', 'keyGet', 'valueGet');
		$value = $config->getUserValue('userGet', 'appGet', 'keyGet');

		$this->assertEquals('valueGet', $value);

		$result = $this->getPreferenceRows('userGet');

		$this->assertEquals(1, count($result));
		$this->assertEquals([
			'userid' => 'userGet',
			'appid' => 'appGet',
			'configkey' => 'keyGet',
			'configvalue' => 'valueGet'
		], $result[0]);

		// drop data from database - but the config option should be cached in the config object
		$qb = $this->connection->getQueryBuilder();
		$qb->delete('preferences')
			->where($qb->expr()->eq('userid', $qb->createNamedParameter('userGet')))
			->executeStatement();

		// testing the caching mechanism
		$value = $config->getUserValue('userGet', 'appGet', 'keyGet');

		$this->assertEquals('valueGet', $value);

		$this->assertEquals(0, count($this->getPreferenceRows('userGet')));
	}

	public function testGetUserKeys(): void {
		$config = $this->getConfig();

		// preparation - add something to the database
		$this->insertPreferenceRow('userFetch', 'appFetch1', 'keyFetch1', 'value1');
		$this->insertPreferenceRow('userFetch', 'appFetch1', 'keyFetch2', 'value2');
		$this->insertPreferenceRow('userFetch', 'appFetch2', 'keyFetch3', 'value3');
		$this->insertPreferenceRow('userFetch', 'appFetch1', 'keyFetch4', 'value4');
		$this->insertPreferenceRow('userFetch', 'appFetch4', 'keyFetch1', 'value5');
		$this->insertPreferenceRow('userFetch', 'appFetch5', 'keyFetch1', 'value6');
		$this->insertPreferenceRow('userFetch2', 'appFetch', 'keyFetch1', 'value7');

		$value = $config->getUserKeys('userFetch', 'appFetch1');
		$this->assertEquals(['keyFetch1', 'keyFetch2', 'keyFetch4'], $value);

		$value = $config->getUserKeys('userFetch2', 'appFetch');
		$this->assertEquals(['keyFetch1'], $value);

		// cleanup
		$this->clearPreferences();
	}

	public function testGetUserKeysAllInts(): void {
		$config = $this->getConfig();

		// preparation - add something to the database
		$this->insertPreferenceRow('userFetch8', 'appFetch1', '123', 'value');
		$this->insertPreferenceRow('userFetch8', 'appFetch1', '456', 'value');

		$value = $config->getUserKeys('userFetch8', 'appFetch1');
		$this->assertEquals(['123', '456'], $value);
		$this->assertIsString($value[0]);
		$this->assertIsString($value[1]);

		// cleanup
		$this->clearPreferences();
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
		$this->insertPreferenceRow('userFetch1', 'appFetch2', 'keyFetch1', 'value1');
		$this->insertPreferenceRow('userFetch2', 'appFetch2', 'keyFetch1', 'value2');
		$this->insertPreferenceRow('userFetch3', 'appFetch2', 'keyFetch1', '3');
		$this->insertPreferenceRow('userFetch4', 'appFetch2', 'keyFetch1', 'value4');
		$this->insertPreferenceRow('userFetch5', 'appFetch2', 'keyFetch1', 'value5');
		$this->insertPreferenceRow('userFetch6', 'appFetch2', 'keyFetch1', 'value6');
		$this->insertPreferenceRow('userFetch7', 'appFetch2', 'keyFetch1', 'value7');

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
		$this->clearPreferences();
	}

	public function testDeleteAllUserValues(): void {
		$config = $this->getConfig();

		// preparation - add something to the database
		$this->insertPreferenceRow('userFetch3', 'appFetch1', 'keyFetch1', 'value1');
		$this->insertPreferenceRow('userFetch3', 'appFetch1', 'keyFetch2', 'value2');
		$this->insertPreferenceRow('userFetch3', 'appFetch2', 'keyFetch3', 'value3');
		$this->insertPreferenceRow('userFetch3', 'appFetch1', 'keyFetch4', 'value4');
		$this->insertPreferenceRow('userFetch3', 'appFetch4', 'keyFetch1', 'value5');
		$this->insertPreferenceRow('userFetch3', 'appFetch5', 'keyFetch1', 'value6');
		$this->insertPreferenceRow('userFetch4', 'appFetch2', 'keyFetch1', 'value7');

		$config->deleteAllUserValues('userFetch3');

		$this->assertEquals(1, $this->countPreferenceRows(), 'After removing `userFetch3` there should be exactly 1 entry left.');

		// cleanup
		$this->clearPreferences();
	}

	public function testDeleteAppFromAllUsers(): void {
		$config = $this->getConfig();

		// preparation - add something to the database
		$this->insertPreferenceRow('userFetch5', 'appFetch1', 'keyFetch1', 'value1');
		$this->insertPreferenceRow('userFetch5', 'appFetch1', 'keyFetch2', 'value2');
		$this->insertPreferenceRow('userFetch5', 'appFetch2', 'keyFetch3', 'value3');
		$this->insertPreferenceRow('userFetch5', 'appFetch1', 'keyFetch4', 'value4');
		$this->insertPreferenceRow('userFetch5', 'appFetch4', 'keyFetch1', 'value5');
		$this->insertPreferenceRow('userFetch5', 'appFetch5', 'keyFetch1', 'value6');
		$this->insertPreferenceRow('userFetch6', 'appFetch2', 'keyFetch1', 'value7');

		$config->deleteAppFromAllUsers('appFetch1');

		$this->assertEquals(4, $this->countPreferenceRows(), 'After removing `appFetch1` there should be exactly 4 entries left.');

		$config->deleteAppFromAllUsers('appFetch2');

		$this->assertEquals(2, $this->countPreferenceRows(), 'After removing `appFetch2` there should be exactly 2 entries left.');

		// cleanup
		$this->clearPreferences();
	}

	public function testGetUsersForUserValue(): void {
		// mock the check for the database to run the correct SQL statements for each database type
		$systemConfig = $this->getMockBuilder('\OC\SystemConfig')
			->disableOriginalConstructor()
			->getMock();
		$config = $this->getConfig($systemConfig);

		// preparation - add something to the database
		$this->insertPreferenceRow('user1', 'appFetch9', 'keyFetch9', 'value9');
		$this->insertPreferenceRow('user2', 'appFetch9', 'keyFetch9', 'value9');
		$this->insertPreferenceRow('user3', 'appFetch9', 'keyFetch9', 'value8');
		$this->insertPreferenceRow('user4', 'appFetch9', 'keyFetch8', 'value9');
		$this->insertPreferenceRow('user5', 'appFetch8', 'keyFetch9', 'value9');
		$this->insertPreferenceRow('user6', 'appFetch9', 'keyFetch9', 'value9');

		$value = $config->getUsersForUserValue('appFetch9', 'keyFetch9', 'value9');
		$this->assertEquals(['user1', 'user2', 'user6'], $value);

		// cleanup
		$this->clearPreferences();
	}
}
