<?php
/**
 * @author Björn Schießle <schiessle@owncloud.com>
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


namespace OCA\Federation\Tests\lib;


use OCA\Federation\DbHandler;
use OCP\IDBConnection;
use Test\TestCase;

class DbHandlerTest extends TestCase {

	/** @var  DbHandler */
	private $dbHandler;

	/** @var  \PHPUnit_Framework_MockObject_MockObject */
	private $il10n;

	/** @var  IDBConnection */
	private $connection;

	/** @var string  */
	private $dbTable = 'trusted_servers';

	public function setUp() {
		parent::setUp();

		$this->connection = \OC::$server->getDatabaseConnection();
		$this->il10n = $this->getMock('OCP\IL10N');

		$this->dbHandler = new DbHandler(
			$this->connection,
			$this->il10n
		);

		$query = $this->connection->getQueryBuilder()->select('*')->from($this->dbTable);
		$result = $query->execute()->fetchAll();
		$this->assertEmpty($result, 'we need to start with a empty trusted_servers table');
	}

	public function tearDown() {
		parent::tearDown();
		$query = $this->connection->getQueryBuilder()->delete($this->dbTable);
		$query->execute();
	}

	public function testAdd() {
		$id = $this->dbHandler->add('server1');

		$query = $this->connection->getQueryBuilder()->select('*')->from($this->dbTable);
		$result = $query->execute()->fetchAll();
		$this->assertSame(1, count($result));
		$this->assertSame('server1', $result[0]['url']);
		$this->assertSame($id, $result[0]['id']);
	}

	public function testRemove() {
		$id1 = $this->dbHandler->add('server1');
		$id2 = $this->dbHandler->add('server2');

		$query = $this->connection->getQueryBuilder()->select('*')->from($this->dbTable);
		$result = $query->execute()->fetchAll();
		$this->assertSame(2, count($result));
		$this->assertSame('server1', $result[0]['url']);
		$this->assertSame('server2', $result[1]['url']);
		$this->assertSame($id1, $result[0]['id']);
		$this->assertSame($id2, $result[1]['id']);

		$this->dbHandler->remove($id2);
		$query = $this->connection->getQueryBuilder()->select('*')->from($this->dbTable);
		$result = $query->execute()->fetchAll();
		$this->assertSame(1, count($result));
		$this->assertSame('server1', $result[0]['url']);
		$this->assertSame($id1, $result[0]['id']);
	}

	public function testGetAll() {
		$id1 = $this->dbHandler->add('server1');
		$id2 = $this->dbHandler->add('server2');

		$result = $this->dbHandler->getAll();
		$this->assertSame(2, count($result));
		$this->assertSame('server1', $result[0]['url']);
		$this->assertSame('server2', $result[1]['url']);
		$this->assertSame($id1, $result[0]['id']);
		$this->assertSame($id2, $result[1]['id']);
	}

	/**
	 * @dataProvider dataTestExists
	 *
	 * @param string $serverInTable
	 * @param string $checkForServer
	 * @param bool $expected
	 */
	public function testExists($serverInTable, $checkForServer, $expected) {
		$this->dbHandler->add($serverInTable);
		$this->assertSame($expected,
			$this->dbHandler->exists($checkForServer)
		);
	}

	public function dataTestExists() {
		return [
			['server1', 'server1', true],
			['server1', 'server1', true],
			['server1', 'server2', false]
		];
	}

}
