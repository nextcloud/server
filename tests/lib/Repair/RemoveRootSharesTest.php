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

use OC\Repair\RemoveRootShares;
use OCP\Files\IRootFolder;
use OCP\IDBConnection;
use OCP\IUserManager;
use OCP\Migration\IOutput;
use Test\Traits\UserTrait;

/**
 * Class RemoveOldSharesTest
 *
 * @package Test\Repair
 * @group DB
 */
class RemoveRootSharesTest extends \Test\TestCase {
	use UserTrait;

	/** @var RemoveRootShares */
	protected $repair;

	/** @var IDBConnection */
	protected $connection;

	/** @var IOutput */
	private $outputMock;

	/** @var IUserManager */
	private $userManager;

	/** @var IRootFolder */
	private $rootFolder;

	protected function setUp() {
		parent::setUp();

		$this->outputMock = $this->getMockBuilder('\OCP\Migration\IOutput')
			->disableOriginalConstructor()
			->getMock();

		$this->userManager = \OC::$server->getUserManager();
		$this->rootFolder = \OC::$server->getRootFolder();

		$this->connection = \OC::$server->getDatabaseConnection();
		$this->repair = new RemoveRootShares($this->connection, $this->userManager, $this->rootFolder);
	}

	protected function tearDown() {
		$qb = $this->connection->getQueryBuilder();
		$qb->delete('share');
		$qb->execute();

		return parent::tearDown();
	}

	public function testRootSharesExist() {
		//Add test user
		$user = $this->userManager->createUser('test', 'test');
		$userFolder = $this->rootFolder->getUserFolder('test');
		$fileId = $userFolder->getId();

		//Now insert cyclic share
		$qb = $this->connection->getQueryBuilder();
		$qb->insert('share')
			->values([
				'share_type'  => $qb->createNamedParameter(0),
				'share_with'  => $qb->createNamedParameter('foo'),
				'uid_owner'   => $qb->createNamedParameter('owner'),
				'item_type'   => $qb->createNamedParameter('file'),
				'item_source' => $qb->createNamedParameter($fileId),
				'item_target' => $qb->createNamedParameter('/target'),
				'file_source' => $qb->createNamedParameter($fileId),
				'file_target' => $qb->createNamedParameter('/target'),
				'permissions' => $qb->createNamedParameter(1),
			]);
		$qb->execute();

		$res = $this->invokePrivate($this->repair, 'rootSharesExist', []);
		$this->assertTrue($res);

		$user->delete();
	}

	public function testRootSharesDontExist() {
		//Add test user
		$user = $this->userManager->createUser('test', 'test');
		$userFolder = $this->rootFolder->getUserFolder('test');
		$fileId = $userFolder->getId();

		//Now insert cyclic share
		$qb = $this->connection->getQueryBuilder();
		$qb->insert('share')
			->values([
				'share_type'  => $qb->createNamedParameter(0),
				'share_with'  => $qb->createNamedParameter('foo'),
				'uid_owner'   => $qb->createNamedParameter('owner'),
				'item_type'   => $qb->createNamedParameter('file'),
				'item_source' => $qb->createNamedParameter($fileId+1),
				'item_target' => $qb->createNamedParameter('/target'),
				'file_source' => $qb->createNamedParameter($fileId+1),
				'file_target' => $qb->createNamedParameter('/target'),
				'permissions' => $qb->createNamedParameter(1),
			]);
		$qb->execute();

		$res = $this->invokePrivate($this->repair, 'rootSharesExist', []);
		$this->assertFalse($res);

		$user->delete();
	}

	public function testRun() {
		//Add test user
		$user1 = $this->userManager->createUser('test1', 'test1');
		$userFolder = $this->rootFolder->getUserFolder('test1');
		$fileId = $userFolder->getId();

		//Now insert cyclic share
		$qb = $this->connection->getQueryBuilder();
		$qb->insert('share')
			->values([
				'share_type'  => $qb->createNamedParameter(0),
				'share_with'  => $qb->createNamedParameter('foo'),
				'uid_owner'   => $qb->createNamedParameter('owner'),
				'item_type'   => $qb->createNamedParameter('file'),
				'item_source' => $qb->createNamedParameter($fileId),
				'item_target' => $qb->createNamedParameter('/target'),
				'file_source' => $qb->createNamedParameter($fileId),
				'file_target' => $qb->createNamedParameter('/target'),
				'permissions' => $qb->createNamedParameter(1),
			]);
		$qb->execute();

		//Add test user
		$user2 = $this->userManager->createUser('test2', 'test2');
		$userFolder = $this->rootFolder->getUserFolder('test2');
		$folder = $userFolder->newFolder('foo');
		$fileId = $folder->getId();

		//Now insert cyclic share
		$qb = $this->connection->getQueryBuilder();
		$qb->insert('share')
			->values([
				'share_type'  => $qb->createNamedParameter(0),
				'share_with'  => $qb->createNamedParameter('foo'),
				'uid_owner'   => $qb->createNamedParameter('owner'),
				'item_type'   => $qb->createNamedParameter('file'),
				'item_source' => $qb->createNamedParameter($fileId),
				'item_target' => $qb->createNamedParameter('/target'),
				'file_source' => $qb->createNamedParameter($fileId),
				'file_target' => $qb->createNamedParameter('/target'),
				'permissions' => $qb->createNamedParameter(1),
			]);
		$qb->execute();

		$this->repair->run($this->outputMock);

		//Verify
		$qb = $this->connection->getQueryBuilder();
		$qb->selectAlias($qb->createFunction('COUNT(*)'), 'count')
			->from('share');

		$cursor = $qb->execute();
		$data = $cursor->fetch();
		$cursor->closeCursor();

		$count = (int)$data['count'];

		$this->assertEquals(1, $count);

		$user1->delete();
		$user2->delete();
	}
}
