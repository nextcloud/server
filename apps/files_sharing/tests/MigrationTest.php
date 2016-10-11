<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
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

namespace OCA\Files_Sharing\Tests;


use OCA\Files_Sharing\Migration;

/**
 * Class MigrationTest
 *
 * @group DB
 */
class MigrationTest extends TestCase {

	/** @var \OCP\IDBConnection */
	private $connection;

	/** @var Migration */
	private $migration;

	private $table = 'share';

	public function setUp() {
		parent::setUp();

		$this->connection = \OC::$server->getDatabaseConnection();
		$this->migration = new Migration($this->connection);

		$this->cleanDB();
	}

	public function tearDown() {
		parent::tearDown();
		$this->cleanDB();
	}

	private function cleanDB() {
		$query = $this->connection->getQueryBuilder();
		$query->delete($this->table)->execute();
	}

	public function addDummyValues() {
		$query = $this->connection->getQueryBuilder();
		$query->insert($this->table)
			->values(
				array(
					'share_type' => $query->createParameter('share_type'),
					'share_with' => $query->createParameter('share_with'),
					'uid_owner' => $query->createParameter('uid_owner'),
					'uid_initiator' => $query->createParameter('uid_initiator'),
					'parent' => $query->createParameter('parent'),
					'item_type' => $query->createParameter('item_type'),
					'item_source' => $query->createParameter('item_source'),
					'item_target' => $query->createParameter('item_target'),
					'file_source' => $query->createParameter('file_source'),
					'file_target' => $query->createParameter('file_target'),
					'permissions' => $query->createParameter('permissions'),
					'stime' => $query->createParameter('stime'),
				)
			);
		// shared contact, shouldn't be modified
		$query->setParameter('share_type', \OCP\Share::SHARE_TYPE_CONTACT)
			->setParameter('share_with', 'user1')
			->setParameter('uid_owner', 'owner1')
			->setParameter('uid_initiator', '')
			->setParameter('parent', null)
			->setParameter('item_type', 'contact')
			->setParameter('item_source', '2')
			->setParameter('item_target', '/2')
			->setParameter('file_source', null)
			->setParameter('file_target', null)
			->setParameter('permissions', 31)
			->setParameter('stime', time());
		$this->assertSame(1,
			$query->execute()
		);
		// shared calendar, shouldn't be modified
		$query->setParameter('share_type', \OCP\Share::SHARE_TYPE_USER)
			->setParameter('share_with', 'user1')
			->setParameter('uid_owner', 'owner1')
			->setParameter('uid_initiator', '')
			->setParameter('parent', null)
			->setParameter('item_type', 'calendar')
			->setParameter('item_source', '2')
			->setParameter('item_target', '/2')
			->setParameter('file_source', null)
			->setParameter('file_target', null)
			->setParameter('permissions', 31)
			->setParameter('stime', time());
		$this->assertSame(1,
			$query->execute()
		);
		// single user share, shouldn't be modified
		$query->setParameter('share_type', \OCP\Share::SHARE_TYPE_USER)
			->setParameter('share_with', 'user1')
			->setParameter('uid_owner', 'owner1')
			->setParameter('uid_initiator', '')
			->setParameter('parent', null)
			->setParameter('item_type', 'file')
			->setParameter('item_source', '2')
			->setParameter('item_target', '/2')
			->setParameter('file_source', 2)
			->setParameter('file_target', '/foo')
			->setParameter('permissions', 31)
			->setParameter('stime', time());
		$this->assertSame(1,
			$query->execute()
		);
		// single group share, shouldn't be modified
		$query->setParameter('share_type', \OCP\Share::SHARE_TYPE_GROUP)
			->setParameter('share_with', 'group1')
			->setParameter('uid_owner', 'owner1')
			->setParameter('uid_initiator', '')
			->setParameter('parent', null)
			->setParameter('item_type', 'file')
			->setParameter('item_source', '2')
			->setParameter('item_target', '/2')
			->setParameter('file_source', 2)
			->setParameter('file_target', '/foo')
			->setParameter('permissions', 31)
			->setParameter('stime', time());
		$this->assertSame(1,
			$query->execute()
		);
		$parent = $query->getLastInsertId();
		// unique target for group share, shouldn't be modified
		$query->setParameter('share_type', 2)
			->setParameter('share_with', 'group1')
			->setParameter('uid_owner', 'owner1')
			->setParameter('uid_initiator', '')
			->setParameter('parent', $parent)
			->setParameter('item_type', 'file')
			->setParameter('item_source', '2')
			->setParameter('item_target', '/2')
			->setParameter('file_source', 2)
			->setParameter('file_target', '/foo renamed')
			->setParameter('permissions', 31)
			->setParameter('stime', time());
		$this->assertSame(1,
			$query->execute()
		);
		// first user share, shouldn't be modified
		$query->setParameter('share_type', \OCP\Share::SHARE_TYPE_USER)
			->setParameter('share_with', 'user1')
			->setParameter('uid_owner', 'owner2')
			->setParameter('uid_initiator', '')
			->setParameter('parent', null)
			->setParameter('item_type', 'file')
			->setParameter('item_source', '2')
			->setParameter('item_target', '/2')
			->setParameter('file_source', 2)
			->setParameter('file_target', '/foobar')
			->setParameter('permissions', 31)
			->setParameter('stime', time());
		$this->assertSame(1,
			$query->execute()
		);
		$parent = $query->getLastInsertId();
		// first re-share, should be attached to the first user share after migration
		$query->setParameter('share_type', \OCP\Share::SHARE_TYPE_USER)
			->setParameter('share_with', 'user2')
			->setParameter('uid_owner', 'user1')
			->setParameter('uid_initiator', '')
			->setParameter('parent', $parent)
			->setParameter('item_type', 'file')
			->setParameter('item_source', '2')
			->setParameter('item_target', '/2')
			->setParameter('file_source', 2)
			->setParameter('file_target', '/foobar')
			->setParameter('permissions', 31)
			->setParameter('stime', time());
		$this->assertSame(1,
			$query->execute()
		);
		$parent = $query->getLastInsertId();
		// second re-share, should be attached to the first user share after migration
		$query->setParameter('share_type', \OCP\Share::SHARE_TYPE_USER)
			->setParameter('share_with', 'user3')
			->setParameter('uid_owner', 'user2')
			->setParameter('uid_initiator', '')
			->setParameter('parent', $parent)
			->setParameter('item_type', 'file')
			->setParameter('item_source', '2')
			->setParameter('item_target', '/2')
			->setParameter('file_source', 2)
			->setParameter('file_target', '/foobar')
			->setParameter('permissions', 31)
			->setParameter('stime', time());
		$this->assertSame(1,
			$query->execute()
		);
		$parent = $query->getLastInsertId();
		// third re-share, should be attached to the first user share after migration
		$query->setParameter('share_type', \OCP\Share::SHARE_TYPE_REMOTE)
			->setParameter('share_with', 'user@server.com')
			->setParameter('uid_owner', 'user3')
			->setParameter('uid_initiator', '')
			->setParameter('parent', $parent)
			->setParameter('item_type', 'file')
			->setParameter('item_source', '2')
			->setParameter('item_target', '/2')
			->setParameter('file_source', 2)
			->setParameter('file_target', '/foobar')
			->setParameter('permissions', 31)
			->setParameter('stime', time());
		$this->assertSame(1,
			$query->execute()
		);

		// Link reshare should keep its parent
		$query->setParameter('share_type', \OCP\Share::SHARE_TYPE_LINK)
			->setParameter('share_with', null)
			->setParameter('uid_owner', 'user3')
			->setParameter('uid_initiator', '')
			->setParameter('parent', $parent)
			->setParameter('item_type', 'file')
			->setParameter('item_source', '2')
			->setParameter('item_target', '/2')
			->setParameter('file_source', 2)
			->setParameter('file_target', '/foobar')
			->setParameter('permissions', 31)
			->setParameter('stime', time());
		$this->assertSame(1,
			$query->execute()
		);
	}

	public function testRemoveReShares() {
		$this->addDummyValues();
		$this->migration->removeReShares();
		$this->verifyResult();
	}

	public function verifyResult() {
		$query = $this->connection->getQueryBuilder();
		$query->select('*')->from($this->table)->orderBy('id');
		$result = $query->execute()->fetchAll();
		$this->assertSame(10, count($result));

		// shares which shouldn't be modified
		for ($i = 0; $i < 4; $i++) {
			$this->assertSame('owner1', $result[$i]['uid_owner']);
			$this->assertEmpty($result[$i]['uid_initiator']);
			$this->assertNull($result[$i]['parent']);
		}
		// group share with unique target
		$this->assertSame('owner1', $result[4]['uid_owner']);
		$this->assertEmpty($result[4]['uid_initiator']);
		$this->assertNotEmpty($result[4]['parent']);
		// initial user share which was re-shared
		$this->assertSame('owner2', $result[5]['uid_owner']);
		$this->assertEmpty($result[5]['uid_initiator']);
		$this->assertNull($result[5]['parent']);
		// flatted re-shares
		for($i = 6; $i < 9; $i++) {
			$this->assertSame('owner2', $result[$i]['uid_owner']);
			$user = 'user' . ($i - 5);
			$this->assertSame($user, $result[$i]['uid_initiator']);
			$this->assertNull($result[$i]['parent']);
		}

		/*
		 * The link share is flattend but has an owner to avoid invisible shares
		 * see: https://github.com/owncloud/core/pull/22317
		 */
		$this->assertSame('owner2', $result[9]['uid_owner']);
		$this->assertSame('user3', $result[9]['uid_initiator']);
		$this->assertSame($result[7]['id'], $result[9]['parent']);
	}

	public function test1001DeepReshares() {
		$parent = null;
		for ($i = 0; $i < 1001; $i++) {
			$query = $this->connection->getQueryBuilder();
			$query->insert($this->table)
				->values(
					[
						'share_type' => $query->createParameter('share_type'),
						'share_with' => $query->createParameter('share_with'),
						'uid_owner' => $query->createParameter('uid_owner'),
						'uid_initiator' => $query->createParameter('uid_initiator'),
						'parent' => $query->createParameter('parent'),
						'item_type' => $query->createParameter('item_type'),
						'item_source' => $query->createParameter('item_source'),
						'item_target' => $query->createParameter('item_target'),
						'file_source' => $query->createParameter('file_source'),
						'file_target' => $query->createParameter('file_target'),
						'permissions' => $query->createParameter('permissions'),
						'stime' => $query->createParameter('stime'),
					]
				)
				->setParameter('share_type', \OCP\Share::SHARE_TYPE_USER)
				->setParameter('share_with', 'user'.($i+1))
				->setParameter('uid_owner', 'user'.($i))
				->setParameter('uid_initiator', null)
				->setParameter('parent', $parent)
				->setParameter('item_type', 'file')
				->setParameter('item_source', '2')
				->setParameter('item_target', '/2')
				->setParameter('file_source', 2)
				->setParameter('file_target', '/foobar')
				->setParameter('permissions', 31)
				->setParameter('stime', time());

			$this->assertSame(1, $query->execute());
			$parent = $query->getLastInsertId();
		}

		$this->migration->removeReShares();
		$this->migration->updateInitiatorInfo();

		$qb = $this->connection->getQueryBuilder();

		$stmt = $qb->select('id', 'share_with', 'uid_owner', 'uid_initiator', 'parent')
			->from('share')
			->orderBy('id', 'asc')
			->execute();

		$i = 0;
		while($share = $stmt->fetch()) {
			$this->assertEquals('user'.($i+1), $share['share_with']);
			$this->assertEquals('user' . ($i), $share['uid_initiator']);
			$this->assertEquals('user0', $share['uid_owner']);
			$this->assertEquals(null, $share['parent']);
			$i++;
		}
		$stmt->closeCursor();
		$this->assertEquals(1001, $i);
	}
}
