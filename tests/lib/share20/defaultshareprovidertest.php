<?php
/**
 * @author Roeland Jago Douma <rullzer@owncloud.com>
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
namespace Test\Share20;

use OCP\IDBConnection;
use OCP\IUserManager;
use OCP\IGroupManager;
use OCP\Files\Folder;
use OC\Share20\DefaultShareProvider;

class DefaultShareProviderTest extends \Test\TestCase {

	/** @var IDBConnection */
	protected $dbConn;

	/** @var IUserManager */
	protected $userManager;

	/** @var IGroupManager */
	protected $groupManager;

	/** @var Folder */
	protected $userFolder;

	/** @var DefaultShareProvider */
	protected $provider;

	public function setUp() {
		$this->dbConn = \OC::$server->getDatabaseConnection();
		$this->userManager = $this->getMock('OCP\IUserManager');
		$this->groupManager = $this->getMock('OCP\IGroupManager');
		$this->userFolder = $this->getMock('OCP\Files\Folder');

		//Empty share table
		$this->dbConn->getQueryBuilder()->delete('share')->execute();

		$this->provider = new DefaultShareProvider(
			$this->dbConn,
			$this->userManager,
			$this->groupManager,
			$this->userFolder
		);
	}

	public function tearDown() {
		$this->dbConn->getQueryBuilder()->delete('share')->execute();
	}

	/**
	 * @expectedException OC\Share20\Exception\ShareNotFound
	 */
	public function testGetShareByIdNotExist() {
		$this->provider->getShareById(1);
	}

	public function testGetShareByIdUserShare() {
		$qb = $this->dbConn->getQueryBuilder();

		$qb->insert('share')
			->values([
				'share_type'  => $qb->expr()->literal(\OCP\Share::SHARE_TYPE_USER),
				'share_with'  => $qb->expr()->literal('sharedWith'),
				'uid_owner'   => $qb->expr()->literal('sharedBy'),
				'item_type'   => $qb->expr()->literal('file'),
				'file_source' => $qb->expr()->literal(42),
				'file_target' => $qb->expr()->literal('myTarget'),
				'permissions' => $qb->expr()->literal(13),
			]);
		$qb->execute();

		// Get the id
		$qb = $this->dbConn->getQueryBuilder();
		$cursor = $qb->select('id')
			->from('share')
			->setMaxResults(1)
			->orderBy('id', 'DESC')
			->execute();
		$id = $cursor->fetch();
		$id = $id['id'];
		$cursor->closeCursor();

		$storage = $this->getMock('OC\Files\Storage\Storage');
		$storage
			->expects($this->once())
			->method('getOwner')
			->willReturn('shareOwner');
		$path = $this->getMock('OCP\Files\File');
		$path
			->expects($this->once())
			->method('getStorage')
			->wilLReturn($storage);
		$this->userFolder
			->expects($this->once())
			->method('getById')
			->with(42)
			->willReturn([$path]);

		$sharedWith = $this->getMock('OCP\IUser');
		$sharedBy = $this->getMock('OCP\IUser');
		$shareOwner = $this->getMock('OCP\IUser');
		$this->userManager
			->method('get')
			->will($this->returnValueMap([
				['sharedWith', $sharedWith],
				['sharedBy', $sharedBy],
				['shareOwner', $shareOwner],
			]));

		$share = $this->provider->getShareById($id);

		$this->assertEquals($id, $share->getId());
		$this->assertEquals(\OCP\Share::SHARE_TYPE_USER, $share->getShareType());
		$this->assertEquals($sharedWith, $share->getSharedWith());
		$this->assertEquals($sharedBy, $share->getSharedBy());
		$this->assertEquals($shareOwner, $share->getShareOwner());
		$this->assertEquals($path, $share->getPath());
		$this->assertEquals(13, $share->getPermissions());
		$this->assertEquals(null, $share->getToken());
		$this->assertEquals(null, $share->getExpirationDate());
		$this->assertEquals('myTarget', $share->getTarget());
	}

	public function testGetShareByIdGroupShare() {
		$qb = $this->dbConn->getQueryBuilder();

		$qb->insert('share')
			->values([
				'share_type' => $qb->expr()->literal(\OCP\Share::SHARE_TYPE_GROUP),
				'share_with' => $qb->expr()->literal('sharedWith'),
				'uid_owner' => $qb->expr()->literal('sharedBy'),
				'item_type'   => $qb->expr()->literal('file'),
				'file_source' => $qb->expr()->literal(42),
				'file_target' => $qb->expr()->literal('myTarget'),
				'permissions' => $qb->expr()->literal(13),
			]);
		$this->assertEquals(1, $qb->execute());

		// Get the id
		$qb = $this->dbConn->getQueryBuilder();
		$cursor = $qb->select('id')
			->from('share')
			->setMaxResults(1)
			->orderBy('id', 'DESC')
			->execute();
		$id = $cursor->fetch();
		$id = $id['id'];
		$cursor->closeCursor();

		$storage = $this->getMock('OC\Files\Storage\Storage');
		$storage
			->expects($this->once())
			->method('getOwner')
			->willReturn('shareOwner');
		$path = $this->getMock('OCP\Files\Folder');
		$path
			->expects($this->once())
			->method('getStorage')
			->wilLReturn($storage);
		$this->userFolder
			->expects($this->once())
			->method('getById')
			->with(42)
			->willReturn([$path]);

		$sharedWith = $this->getMock('OCP\IGroup');
		$sharedBy = $this->getMock('OCP\IUser');
		$shareOwner = $this->getMock('OCP\IUser');
		$this->userManager
			->method('get')
			->will($this->returnValueMap([
				['sharedBy', $sharedBy],
				['shareOwner', $shareOwner],
			]));
		$this->groupManager
			->expects($this->once())
			->method('get')
			->with('sharedWith')
			->willReturn($sharedWith);

		$share = $this->provider->getShareById($id);

		$this->assertEquals($id, $share->getId());
		$this->assertEquals(\OCP\Share::SHARE_TYPE_GROUP, $share->getShareType());
		$this->assertEquals($sharedWith, $share->getSharedWith());
		$this->assertEquals($sharedBy, $share->getSharedBy());
		$this->assertEquals($shareOwner, $share->getShareOwner());
		$this->assertEquals($path, $share->getPath());
		$this->assertEquals(13, $share->getPermissions());
		$this->assertEquals(null, $share->getToken());
		$this->assertEquals(null, $share->getExpirationDate());
		$this->assertEquals('myTarget', $share->getTarget());
	}

	public function testGetShareByIdLinkShare() {
		$qb = $this->dbConn->getQueryBuilder();

		$qb->insert('share')
			->values([
				'share_type' => $qb->expr()->literal(\OCP\Share::SHARE_TYPE_LINK),
				'share_with' => $qb->expr()->literal('sharedWith'),
				'uid_owner' => $qb->expr()->literal('sharedBy'),
				'item_type'   => $qb->expr()->literal('file'),
				'file_source' => $qb->expr()->literal(42),
				'file_target' => $qb->expr()->literal('myTarget'),
				'permissions' => $qb->expr()->literal(13),
				'token' => $qb->expr()->literal('token'),
				'expiration' => $qb->expr()->literal('2000-01-02 00:00:00'),
			]);
		$this->assertEquals(1, $qb->execute());

		// Get the id
		$qb = $this->dbConn->getQueryBuilder();
		$cursor = $qb->select('id')
			->from('share')
			->setMaxResults(1)
			->orderBy('id', 'DESC')
			->execute();
		$id = $cursor->fetch();
		$id = $id['id'];
		$cursor->closeCursor();

		$storage = $this->getMock('OC\Files\Storage\Storage');
		$storage
			->expects($this->once())
			->method('getOwner')
			->willReturn('shareOwner');
		$path = $this->getMock('OCP\Files\Node');
		$path
			->expects($this->once())
			->method('getStorage')
			->wilLReturn($storage);
		$this->userFolder
			->expects($this->once())
			->method('getById')
			->with(42)
			->willReturn([$path]);

		$sharedBy = $this->getMock('OCP\IUser');
		$shareOwner = $this->getMock('OCP\IUser');
		$this->userManager
			->method('get')
			->will($this->returnValueMap([
				['sharedBy', $sharedBy],
				['shareOwner', $shareOwner],
			]));

		$share = $this->provider->getShareById($id);

		$this->assertEquals($id, $share->getId());
		$this->assertEquals(\OCP\Share::SHARE_TYPE_LINK, $share->getShareType());
		$this->assertEquals('sharedWith', $share->getPassword());
		$this->assertEquals($sharedBy, $share->getSharedBy());
		$this->assertEquals($shareOwner, $share->getShareOwner());
		$this->assertEquals($path, $share->getPath());
		$this->assertEquals(13, $share->getPermissions());
		$this->assertEquals('token', $share->getToken());
		$this->assertEquals(\DateTime::createFromFormat('Y-m-d H:i:s', '2000-01-02 00:00:00'), $share->getExpirationDate());
		$this->assertEquals('myTarget', $share->getTarget());
	}

	public function testGetShareByIdRemoteShare() {
		$qb = $this->dbConn->getQueryBuilder();

		$qb->insert('share')
			->values([
				'share_type' => $qb->expr()->literal(\OCP\Share::SHARE_TYPE_REMOTE),
				'share_with' => $qb->expr()->literal('sharedWith'),
				'uid_owner' => $qb->expr()->literal('sharedBy'),
				'item_type'   => $qb->expr()->literal('file'),
				'file_source' => $qb->expr()->literal(42),
				'file_target' => $qb->expr()->literal('myTarget'),
				'permissions' => $qb->expr()->literal(13),
			]);
		$this->assertEquals(1, $qb->execute());

		// Get the id
		$qb = $this->dbConn->getQueryBuilder();
		$cursor = $qb->select('id')
			->from('share')
			->setMaxResults(1)
			->orderBy('id', 'DESC')
			->execute();
		$id = $cursor->fetch();
		$id = $id['id'];
		$cursor->closeCursor();


		$storage = $this->getMock('OC\Files\Storage\Storage');
		$storage
			->expects($this->once())
			->method('getOwner')
			->willReturn('shareOwner');
		$path = $this->getMock('OCP\Files\Node');
		$path
			->expects($this->once())
			->method('getStorage')
			->wilLReturn($storage);
		$this->userFolder
			->expects($this->once())
			->method('getById')
			->with(42)
			->willReturn([$path]);

		$sharedBy = $this->getMock('OCP\IUser');
		$shareOwner = $this->getMock('OCP\IUser');
		$this->userManager
			->method('get')
			->will($this->returnValueMap([
				['sharedBy', $sharedBy],
				['shareOwner', $shareOwner],
			]));

		$share = $this->provider->getShareById($id);

		$this->assertEquals($id, $share->getId());
		$this->assertEquals(\OCP\Share::SHARE_TYPE_REMOTE, $share->getShareType());
		$this->assertEquals('sharedWith', $share->getSharedWith());
		$this->assertEquals($sharedBy, $share->getSharedBy());
		$this->assertEquals($shareOwner, $share->getShareOwner());
		$this->assertEquals($path, $share->getPath());
		$this->assertEquals(13, $share->getPermissions());
		$this->assertEquals(null, $share->getToken());
		$this->assertEquals(null, $share->getExpirationDate());
		$this->assertEquals('myTarget', $share->getTarget());
	}

	public function testDeleteSingleShare() {
		$qb = $this->dbConn->getQueryBuilder();
		$qb->insert('share')
			->values([
				'share_type' => $qb->expr()->literal(\OCP\Share::SHARE_TYPE_USER),
				'share_with' => $qb->expr()->literal('sharedWith'),
				'uid_owner' => $qb->expr()->literal('sharedBy'),
				'item_type'   => $qb->expr()->literal('file'),
				'file_source' => $qb->expr()->literal(42),
				'file_target' => $qb->expr()->literal('myTarget'),
				'permissions' => $qb->expr()->literal(13),
			]);
		$this->assertEquals(1, $qb->execute());

		// Get the id
		$qb = $this->dbConn->getQueryBuilder();
		$cursor = $qb->select('id')
			->from('share')
			->setMaxResults(1)
			->orderBy('id', 'DESC')
			->execute();
		$id = $cursor->fetch();
		$id = $id['id'];
		$cursor->closeCursor();


		$share = $this->getMock('OC\Share20\IShare');
		$share->method('getId')->willReturn($id);

		$provider = $this->getMockBuilder('OC\Share20\DefaultShareProvider')
            ->setConstructorArgs([  
                    $this->dbConn,
                    $this->userManager,
                    $this->groupManager,
                    $this->userFolder,
                ]        
            )            
            ->setMethods(['getShareById'])
            ->getMock();
		$provider
			->expects($this->once())
			->method('getShareById')
			->willReturn($share);

		$provider->delete($share);

		$qb = $this->dbConn->getQueryBuilder();
		$qb->select('*')
			->from('share');

		$cursor = $qb->execute();
		$result = $cursor->fetchAll();
		$cursor->closeCursor();

		$this->assertEmpty($result);
	}

	/**
	 * @expectedException \OC\Share20\Exception\BackendError
	 */
	public function testDeleteFails() {
		$share = $this->getMock('OC\Share20\IShare');
		$share
			->method('getId')
			->willReturn(42);

		$expr = $this->getMock('OCP\DB\QueryBuilder\IExpressionBuilder');
		$qb = $this->getMock('OCP\DB\QueryBuilder\IQueryBuilder');
		$qb->expects($this->once())
			->method('delete')
			->will($this->returnSelf());
		$qb->expects($this->once())
			->method('expr')
			->willReturn($expr);
		$qb->expects($this->once())
			->method('where')
			->will($this->returnSelf());
		$qb->expects($this->once())
			->method('setParameter')
			->will($this->returnSelf());
		$qb->expects($this->once())
			->method('execute')
			->will($this->throwException(new \Exception));

		$db = $this->getMock('OCP\IDBConnection');
		$db->expects($this->once())
			->method('getQueryBuilder')
			->with()
			->willReturn($qb);

		$provider = $this->getMockBuilder('OC\Share20\DefaultShareProvider')
            ->setConstructorArgs([  
                    $db,
                    $this->userManager,
                    $this->groupManager,
                    $this->userFolder,
                ]        
            )            
            ->setMethods(['getShareById'])
            ->getMock();
		$provider
			->expects($this->once())
			->method('getShareById')
			->with(42)
			->willReturn($share);
		
		$provider->delete($share);
	}

	public function testGetChildren() {
		$qb = $this->dbConn->getQueryBuilder();
		$qb->insert('share')
			->values([
				'share_type'  => $qb->expr()->literal(\OCP\Share::SHARE_TYPE_USER),
				'share_with'  => $qb->expr()->literal('sharedWith'),
				'uid_owner'   => $qb->expr()->literal('sharedBy'),
				'item_type'   => $qb->expr()->literal('file'),
				'file_source' => $qb->expr()->literal(42),
				'file_target' => $qb->expr()->literal('myTarget'),
				'permissions' => $qb->expr()->literal(13),
			]);
		$qb->execute();

		// Get the id
		$qb = $this->dbConn->getQueryBuilder();
		$cursor = $qb->select('id')
			->from('share')
			->setMaxResults(1)
			->orderBy('id', 'DESC')
			->execute();
		$id = $cursor->fetch();
		$id = $id['id'];
		$cursor->closeCursor();

		$qb = $this->dbConn->getQueryBuilder();
		$qb->insert('share')
			->values([
				'share_type'  => $qb->expr()->literal(\OCP\Share::SHARE_TYPE_USER),
				'share_with'  => $qb->expr()->literal('user1'),
				'uid_owner'   => $qb->expr()->literal('user2'),
				'item_type'   => $qb->expr()->literal('file'),
				'file_source' => $qb->expr()->literal(1),
				'file_target' => $qb->expr()->literal('myTarget1'),
				'permissions' => $qb->expr()->literal(2),
				'parent'      => $qb->expr()->literal($id),
			]);
		$qb->execute();

		$qb = $this->dbConn->getQueryBuilder();
		$qb->insert('share')
			->values([
				'share_type'  => $qb->expr()->literal(\OCP\Share::SHARE_TYPE_GROUP),
				'share_with'  => $qb->expr()->literal('group1'),
				'uid_owner'   => $qb->expr()->literal('user3'),
				'item_type'   => $qb->expr()->literal('folder'),
				'file_source' => $qb->expr()->literal(3),
				'file_target' => $qb->expr()->literal('myTarget2'),
				'permissions' => $qb->expr()->literal(4),
				'parent'      => $qb->expr()->literal($id),
			]);
		$qb->execute();


		$storage = $this->getMock('OC\Files\Storage\Storage');
		$storage
			->method('getOwner')
			->willReturn('shareOwner');
		$path1 = $this->getMock('OCP\Files\File');
		$path1->expects($this->once())->method('getStorage')->willReturn($storage);
		$path2 = $this->getMock('OCP\Files\Folder');
		$path2->expects($this->once())->method('getStorage')->willReturn($storage);
		$this->userFolder
			->method('getById')
			->will($this->returnValueMap([
				[1, [$path1]],
				[3, [$path2]],
			]));

		$shareOwner = $this->getMock('OCP\IUser');
		$user1 = $this->getMock('OCP\IUser');
		$user2 = $this->getMock('OCP\IUser');
		$user3 = $this->getMock('OCP\IUser');
		$this->userManager
			->method('get')
			->will($this->returnValueMap([
				['shareOwner', $shareOwner],
				['user1', $user1],
				['user2', $user2],
				['user3', $user3],
			]));

		$group1 = $this->getMock('OCP\IGroup');
		$this->groupManager
			->method('get')
			->will($this->returnValueMap([
				['group1', $group1]
			]));

		$share = $this->getMock('\OC\Share20\IShare');
		$share->method('getId')->willReturn($id);

		$children = $this->provider->getChildren($share);

		$this->assertCount(2, $children);

		//Child1
		$this->assertEquals(\OCP\Share::SHARE_TYPE_USER, $children[0]->getShareType());
		$this->assertEquals($user1, $children[0]->getSharedWith());
		$this->assertEquals($user2, $children[0]->getSharedBy());
		$this->assertEquals($shareOwner, $children[0]->getShareOwner());
		$this->assertEquals($path1, $children[0]->getPath());
		$this->assertEquals(2, $children[0]->getPermissions());
		$this->assertEquals(null, $children[0]->getToken());
		$this->assertEquals(null, $children[0]->getExpirationDate());
		$this->assertEquals('myTarget1', $children[0]->getTarget());

		//Child2
		$this->assertEquals(\OCP\Share::SHARE_TYPE_GROUP, $children[1]->getShareType());
		$this->assertEquals($group1, $children[1]->getSharedWith());
		$this->assertEquals($user3, $children[1]->getSharedBy());
		$this->assertEquals($shareOwner, $children[1]->getShareOwner());
		$this->assertEquals($path2, $children[1]->getPath());
		$this->assertEquals(4, $children[1]->getPermissions());
		$this->assertEquals(null, $children[1]->getToken());
		$this->assertEquals(null, $children[1]->getExpirationDate());
		$this->assertEquals('myTarget2', $children[1]->getTarget());
	}
}
