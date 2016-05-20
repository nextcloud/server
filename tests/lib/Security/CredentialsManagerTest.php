<?php
/**
 * @author Robin McCorkell <rmccorkell@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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

namespace Test\Security;

use \OCP\Security\ICrypto;
use \OCP\IDBConnection;
use \OC\Security\CredentialsManager;

class CredentialsManagerTest extends \Test\TestCase {

	/** @var ICrypto */
	protected $crypto;

	/** @var IDBConnection */
	protected $dbConnection;

	/** @var CredentialsManager */
	protected $manager;

	protected function setUp() {
		parent::setUp();
		$this->crypto = $this->getMock('\OCP\Security\ICrypto');
		$this->dbConnection = $this->getMockBuilder('\OC\DB\Connection')
			->disableOriginalConstructor()
			->getMock();
		$this->manager = new CredentialsManager($this->crypto, $this->dbConnection);
	}

	private function getQeuryResult($row) {
		$result = $this->getMockBuilder('\Doctrine\DBAL\Driver\Statement')
			->disableOriginalConstructor()
			->getMock();

		$result->expects($this->any())
			->method('fetch')
			->will($this->returnValue($row));

		return $result;
	}

	public function testStore() {
		$userId = 'abc';
		$identifier = 'foo';
		$credentials = 'bar';

		$this->crypto->expects($this->once())
			->method('encrypt')
			->with(json_encode($credentials))
			->willReturn('baz');

		$this->dbConnection->expects($this->once())
			->method('setValues')
			->with(CredentialsManager::DB_TABLE,
				['user' => $userId, 'identifier' => $identifier],
				['credentials' => 'baz']
			);

		$this->manager->store($userId, $identifier, $credentials);
	}

	public function testRetrieve() {
		$userId = 'abc';
		$identifier = 'foo';

		$this->crypto->expects($this->once())
			->method('decrypt')
			->with('baz')
			->willReturn(json_encode('bar'));

		$qb = $this->getMockBuilder('\OC\DB\QueryBuilder\QueryBuilder')
			->setConstructorArgs([$this->dbConnection])
			->setMethods(['execute'])
			->getMock();
		$qb->expects($this->once())
			->method('execute')
			->willReturn($this->getQeuryResult(['credentials' => 'baz']));

		$this->dbConnection->expects($this->once())
			->method('getQueryBuilder')
			->willReturn($qb);

		$this->manager->retrieve($userId, $identifier);
	}

}
