<?php
/**
 * @author Roeland Jago Douma <rullzer@owncloud.com>
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
namespace Test\Repair;

use OC\Repair\RemoveOldShares;
use OCP\IDBConnection;
use OCP\Migration\IOutput;

/**
 * Class RemoveOldSharesTest
 *
 * @package Test\Repair
 * @group DB
 */
class RemoveOldSharesTest extends \Test\TestCase {

	/** @var RemoveOldShares */
	protected $repair;

	/** @var IDBConnection */
	protected $connection;

	/** @var IOutput */
	private $outputMock;

	protected function setUp() {
		parent::setUp();

		$this->outputMock = $this->getMockBuilder('\OCP\Migration\IOutput')
			->disableOriginalConstructor()
			->getMock();

		$this->connection = \OC::$server->getDatabaseConnection();
		$this->repair = new RemoveOldShares($this->connection);
	}

	protected function tearDown() {
		$qb = $this->connection->getQueryBuilder();
		$qb->delete('share');
		$qb->execute();

		return parent::tearDown();
	}

	public function testRun() {
		$qb = $this->connection->getQueryBuilder();
		$qb->insert('share')
			->values([
				'share_type'  => $qb->createNamedParameter(0),
				'share_with'  => $qb->createNamedParameter('foo'),
				'uid_owner'   => $qb->createNamedParameter('owner'),
				'item_type'   => $qb->createNamedParameter('file'),
				'item_source' => $qb->createNamedParameter(42),
				'item_target' => $qb->createNamedParameter('/target'),
				'file_source' => $qb->createNamedParameter(42),
				'file_target' => $qb->createNamedParameter('/target'),
				'permissions' => $qb->createNamedParameter(1),
			]);
		$qb->execute();

		$qb = $this->connection->getQueryBuilder();
		$qb->insert('share')
			->values([
				'share_type'  => $qb->createNamedParameter(0),
				'share_with'  => $qb->createNamedParameter('foo'),
				'uid_owner'   => $qb->createNamedParameter('owner'),
				'item_type'   => $qb->createNamedParameter('calendar'),
				'item_source' => $qb->createNamedParameter(42),
				'item_target' => $qb->createNamedParameter('/target'),
				'file_source' => $qb->createNamedParameter(42),
				'file_target' => $qb->createNamedParameter('/target'),
				'permissions' => $qb->createNamedParameter(1),
			]);
		$qb->execute();

		$qb = $this->connection->getQueryBuilder();
		$qb->insert('share')
			->values([
				'share_type'  => $qb->createNamedParameter(0),
				'share_with'  => $qb->createNamedParameter('foo'),
				'uid_owner'   => $qb->createNamedParameter('owner'),
				'item_type'   => $qb->createNamedParameter('event'),
				'item_source' => $qb->createNamedParameter(42),
				'item_target' => $qb->createNamedParameter('/target'),
				'file_source' => $qb->createNamedParameter(42),
				'file_target' => $qb->createNamedParameter('/target'),
				'permissions' => $qb->createNamedParameter(1),
			]);
		$qb->execute();

		$qb = $this->connection->getQueryBuilder();
		$qb->insert('share')
			->values([
				'share_type'  => $qb->createNamedParameter(0),
				'share_with'  => $qb->createNamedParameter('foo'),
				'uid_owner'   => $qb->createNamedParameter('owner'),
				'item_type'   => $qb->createNamedParameter('contact'),
				'item_source' => $qb->createNamedParameter(42),
				'item_target' => $qb->createNamedParameter('/target'),
				'file_source' => $qb->createNamedParameter(42),
				'file_target' => $qb->createNamedParameter('/target'),
				'permissions' => $qb->createNamedParameter(1),
			]);
		$qb->execute();

		$qb = $this->connection->getQueryBuilder();
		$qb->insert('share')
			->values([
				'share_type'  => $qb->createNamedParameter(0),
				'share_with'  => $qb->createNamedParameter('foo'),
				'uid_owner'   => $qb->createNamedParameter('owner'),
				'item_type'   => $qb->createNamedParameter('addressbook'),
				'item_source' => $qb->createNamedParameter(42),
				'item_target' => $qb->createNamedParameter('/target'),
				'file_source' => $qb->createNamedParameter(42),
				'file_target' => $qb->createNamedParameter('/target'),
				'permissions' => $qb->createNamedParameter(1),
			]);
		$qb->execute();

		$qb = $this->connection->getQueryBuilder();
		$qb->selectAlias($qb->createFunction('COUNT(*)'), 'count')
			->from('share');

		$cursor = $qb->execute();
		$data = $cursor->fetchAll();
		$cursor->closeCursor();
		$this->assertEquals(5, $data[0]['count']);

		$this->repair->run($this->outputMock);

		$qb = $this->connection->getQueryBuilder();
		$qb->select('*')
			->from('share');

		$cursor = $qb->execute();
		$data = $cursor->fetchAll();
		$cursor->closeCursor();
		$this->assertCount(1, $data);
		$this->assertEquals('file', $data[0]['item_type']);
	}
}
