<?php
/**
 * @author Björn Schießle <schiessle@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */


namespace Test\Accounts;


use OC\Accounts\AccountManager;
use OC\Mail\Mailer;
use Test\TestCase;

/**
 * Class AccountsManagerTest
 *
 * @group DB
 * @package Test\Accounts
 */
class AccountsManagerTest extends TestCase {

	/** @var  \OCP\IDBConnection */
	private $connection;

	/** @var string accounts table name */
	private $table = 'accounts';
	
	public function setUp() {
		parent::setUp();
		$this->connection = \OC::$server->getDatabaseConnection();
	}

	public function tearDown() {
		parent::tearDown();
		$query = $this->connection->getQueryBuilder();
		$query->delete($this->table)->execute();
	}

	/**
	 * get a instance of the accountManager
	 *
	 * @param array $mockedMethods list of methods which should be mocked
	 * @return \PHPUnit_Framework_MockObject_MockObject | AccountManager
	 */
	public function getInstance($mockedMethods = null) {
		return $this->getMockBuilder('OC\Accounts\AccountManager')
			->setConstructorArgs([$this->connection])
			->setMethods($mockedMethods)
			->getMock();
		
	}

	/**
	 * @dataProvider dataTrueFalse
	 *
	 * @param bool $userAlreadyExists
	 */
	public function testUpdateUser($userAlreadyExists) {
		$accountManager = $this->getInstance(['getUser', 'insertNewUser', 'updateExistingUser']);

		$accountManager->expects($this->once())->method('getUser')->willReturn($userAlreadyExists);

		if ($userAlreadyExists) {
			$accountManager->expects($this->once())->method('updateExistingUser')
				->with('uid', 'data');
			$accountManager->expects($this->never())->method('insertNewUser');
		} else {
			$accountManager->expects($this->once())->method('insertNewUser')
				->with('uid', 'data');
			$accountManager->expects($this->never())->method('updateExistingUser');
		}

		$accountManager->updateUser('uid', 'data');
	}

	public function dataTrueFalse() {
		return [
			[true],
			[false]
		];
	}


	/**
	 * @dataProvider dataTestGetUser
	 *
	 * @param string $setUser
	 * @param array $setData
	 * @param string $askUser
	 * @param array $expectedData
	 */
	public function testGetUser($setUser, $setData, $askUser, $expectedData) {
		$accountManager = $this->getInstance();
		$this->addDummyValuesToTable($setUser, $setData);
		$this->assertEquals($expectedData,
			$accountManager->getUser($askUser)
		);
	}

	public function dataTestGetUser() {
		return [
			['user1', ['key' => 'value'], 'user1', ['key' => 'value']],
			['user1', ['key' => 'value'], 'user2', []],
		];
	}

	public function testUpdateExistingUser() {
		$user = 'user1';
		$oldData = ['key' => 'value'];
		$newData = ['newKey' => 'newValue'];

		$accountManager = $this->getInstance();
		$this->addDummyValuesToTable($user, $oldData);
		$this->invokePrivate($accountManager, 'updateExistingUser', [$user, $newData]);
		$newDataFromTable = $this->getDataFromTable($user);
		$this->assertEquals($newData, $newDataFromTable);
	}

	public function testInsertNewUser() {
		$user = 'user1';
		$data = ['key' => 'value'];

		$accountManager = $this->getInstance();
		$this->assertNull($this->getDataFromTable($user));
		$this->invokePrivate($accountManager, 'insertNewUser', [$user, $data]);

		$dataFromDb = $this->getDataFromTable($user);
		$this->assertEquals($data, $dataFromDb);
	}

	private function addDummyValuesToTable($uid, $data) {

		$query = $this->connection->getQueryBuilder();
		$query->insert($this->table)
			->values(
				[
					'uid' => $query->createNamedParameter($uid),
					'data' => $query->createNamedParameter(json_encode($data)),
				]
			)
			->execute();
	}

	private function getDataFromTable($uid) {
		$query = $this->connection->getQueryBuilder();
		$query->select('data')->from($this->table)
			->where($query->expr()->eq('uid', $query->createParameter('uid')))
			->setParameter('uid', $uid);
		$query->execute();
		$result = $query->execute()->fetchAll();

		if (!empty($result)) {
			return json_decode($result[0]['data'], true);
		}
	}

}
