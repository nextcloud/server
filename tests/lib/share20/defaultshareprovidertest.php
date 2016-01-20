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
use OCP\Files\IRootFolder;
use OC\Share20\DefaultShareProvider;

/**
 * Class DefaultShareProviderTest
 *
 * @package Test\Share20
 * @group DB
 */
class DefaultShareProviderTest extends \Test\TestCase {

	/** @var IDBConnection */
	protected $dbConn;

	/** @var IUserManager */
	protected $userManager;

	/** @var IGroupManager */
	protected $groupManager;

	/** @var IRootFolder */
	protected $rootFolder;

	/** @var DefaultShareProvider */
	protected $provider;

	public function setUp() {
		$this->dbConn = \OC::$server->getDatabaseConnection();
		$this->userManager = $this->getMock('OCP\IUserManager');
		$this->groupManager = $this->getMock('OCP\IGroupManager');
		$this->rootFolder = $this->getMock('OCP\Files\IRootFolder');

		//Empty share table
		$this->dbConn->getQueryBuilder()->delete('share')->execute();

		$this->provider = new DefaultShareProvider(
			$this->dbConn,
			$this->userManager,
			$this->groupManager,
			$this->rootFolder
		);
	}

	public function tearDown() {
		$this->dbConn->getQueryBuilder()->delete('share')->execute();
	}

	/**
	 * @expectedException \OC\Share20\Exception\ShareNotFound
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

		$sharedWith = $this->getMock('OCP\IUser');
		$sharedBy = $this->getMock('OCP\IUser');
		$sharedBy->method('getUID')->willReturn('sharedBy');
		$shareOwner = $this->getMock('OCP\IUser');
		$shareOwner->method('getUID')->willReturn('shareOwner');

		$sharedByPath = $this->getMock('\OCP\Files\File');
		$ownerPath = $this->getMock('\OCP\Files\File');

		$sharedByPath->method('getOwner')->willReturn($shareOwner);

		$sharedByFolder = $this->getMock('\OCP\Files\Folder');
		$sharedByFolder->method('getById')->with(42)->willReturn([$sharedByPath]);

		$shareOwnerFolder = $this->getMock('\OCP\Files\Folder');
		$shareOwnerFolder->method('getById')->with(42)->willReturn([$ownerPath]);

		$this->rootFolder
			->method('getUserFolder')
			->will($this->returnValueMap([
				['sharedBy', $sharedByFolder],
				['shareOwner', $shareOwnerFolder],
			]));

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
		$this->assertEquals($ownerPath, $share->getPath());
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

		$sharedWith = $this->getMock('OCP\IGroup');
		$sharedBy = $this->getMock('OCP\IUser');
		$sharedBy->method('getUID')->willReturn('sharedBy');
		$shareOwner = $this->getMock('OCP\IUser');
		$shareOwner->method('getUID')->willReturn('shareOwner');

		$sharedByPath = $this->getMock('\OCP\Files\Folder');
		$ownerPath = $this->getMock('\OCP\Files\Folder');

		$sharedByPath->method('getOwner')->willReturn($shareOwner);

		$sharedByFolder = $this->getMock('\OCP\Files\Folder');
		$sharedByFolder->method('getById')->with(42)->willReturn([$sharedByPath]);

		$shareOwnerFolder = $this->getMock('\OCP\Files\Folder');
		$shareOwnerFolder->method('getById')->with(42)->willReturn([$ownerPath]);

		$this->rootFolder
				->method('getUserFolder')
				->will($this->returnValueMap([
						['sharedBy', $sharedByFolder],
						['shareOwner', $shareOwnerFolder],
				]));

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
		$this->assertEquals($ownerPath, $share->getPath());
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

		$sharedBy = $this->getMock('OCP\IUser');
		$sharedBy->method('getUID')->willReturn('sharedBy');
		$shareOwner = $this->getMock('OCP\IUser');
		$shareOwner->method('getUID')->willReturn('shareOwner');

		$sharedByPath = $this->getMock('\OCP\Files\Folder');
		$ownerPath = $this->getMock('\OCP\Files\Folder');

		$sharedByPath->method('getOwner')->willReturn($shareOwner);

		$sharedByFolder = $this->getMock('\OCP\Files\Folder');
		$sharedByFolder->method('getById')->with(42)->willReturn([$sharedByPath]);

		$shareOwnerFolder = $this->getMock('\OCP\Files\Folder');
		$shareOwnerFolder->method('getById')->with(42)->willReturn([$ownerPath]);

		$this->rootFolder
				->method('getUserFolder')
				->will($this->returnValueMap([
						['sharedBy', $sharedByFolder],
						['shareOwner', $shareOwnerFolder],
				]));

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
		$this->assertEquals($ownerPath, $share->getPath());
		$this->assertEquals(13, $share->getPermissions());
		$this->assertEquals('token', $share->getToken());
		$this->assertEquals(\DateTime::createFromFormat('Y-m-d H:i:s', '2000-01-02 00:00:00'), $share->getExpirationDate());
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
                    $this->rootFolder,
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
                    $this->rootFolder,
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

		$shareOwner = $this->getMock('OCP\IUser');
		$shareOwner->method('getUID')->willReturn('shareOwner');
		$user1 = $this->getMock('OCP\IUser');
		$user2 = $this->getMock('OCP\IUser');
		$user2->method('getUID')->willReturn('user2');
		$user3 = $this->getMock('OCP\IUser');
		$user3->method('getUID')->willReturn('user3');

		$user2Path = $this->getMock('\OCP\Files\File');
		$user2Path->expects($this->once())->method('getOwner')->willReturn($shareOwner);
		$user2Folder = $this->getMock('\OCP\Files\Folder');
		$user2Folder->expects($this->once())
			->method('getById')
			->with(1)
			->willReturn([$user2Path]);

		$user3Path = $this->getMock('\OCP\Files\Folder');
		$user3Path->expects($this->once())->method('getOwner')->willReturn($shareOwner);
		$user3Folder = $this->getMock('\OCP\Files\Folder');
		$user3Folder->expects($this->once())
			->method('getById')
			->with(3)
			->willReturn([$user3Path]);

		$ownerPath = $this->getMock('\OCP\Files\Folder');
		$ownerFolder = $this->getMock('\OCP\Files\Folder');
		$ownerFolder->method('getById')->willReturn([$ownerPath]);

		$this->rootFolder
			->method('getUserFolder')
			->will($this->returnValueMap([
				['shareOwner', $ownerFolder],
				['user2', $user2Folder],
				['user3', $user3Folder],
			]));

		$this->userManager
			->method('get')
			->will($this->returnValueMap([
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
		$this->assertEquals($ownerPath, $children[0]->getPath());
		$this->assertEquals(2, $children[0]->getPermissions());
		$this->assertEquals(null, $children[0]->getToken());
		$this->assertEquals(null, $children[0]->getExpirationDate());
		$this->assertEquals('myTarget1', $children[0]->getTarget());

		//Child2
		$this->assertEquals(\OCP\Share::SHARE_TYPE_GROUP, $children[1]->getShareType());
		$this->assertEquals($group1, $children[1]->getSharedWith());
		$this->assertEquals($user3, $children[1]->getSharedBy());
		$this->assertEquals($shareOwner, $children[1]->getShareOwner());
		$this->assertEquals($ownerPath, $children[1]->getPath());
		$this->assertEquals(4, $children[1]->getPermissions());
		$this->assertEquals(null, $children[1]->getToken());
		$this->assertEquals(null, $children[1]->getExpirationDate());
		$this->assertEquals('myTarget2', $children[1]->getTarget());
	}

	public function testCreateUserShare() {
		$share = new \OC\Share20\Share();

		$sharedWith = $this->getMock('OCP\IUser');
		$sharedWith->method('getUID')->willReturn('sharedWith');
		$sharedBy = $this->getMock('OCP\IUser');
		$sharedBy->method('getUID')->willReturn('sharedBy');
		$shareOwner = $this->getMock('OCP\IUser');
		$shareOwner->method('getUID')->WillReturn('shareOwner');

		$this->userManager
			->method('get')
			->will($this->returnValueMap([
				['sharedWith', $sharedWith],
				['sharedBy', $sharedBy],
				['shareOwner', $shareOwner],
			]));

		$path = $this->getMock('\OCP\Files\File');
		$path->method('getId')->willReturn(100);
		$path->method('getOwner')->willReturn($shareOwner);

		$ownerFolder = $this->getMock('OCP\Files\Folder');
		$userFolder = $this->getMock('OCP\Files\Folder');
		$this->rootFolder
			->method('getUserFolder')
			->will($this->returnValueMap([
				['sharedBy', $userFolder],
				['shareOwner', $ownerFolder],
			]));

		$userFolder->method('getById')
			->with(100)
			->willReturn([$path]);
		$ownerFolder->method('getById')
			->with(100)
			->willReturn([$path]);

		$share->setShareType(\OCP\Share::SHARE_TYPE_USER);
		$share->setSharedWith($sharedWith);
		$share->setSharedBy($sharedBy);
		$share->setShareOwner($shareOwner);
		$share->setPath($path);
		$share->setPermissions(1);
		$share->setTarget('/target');

		$share2 = $this->provider->create($share);

		$this->assertNotNull($share2->getId());
		$this->assertSame('ocinternal:'.$share2->getId(), $share2->getFullId());
		$this->assertSame(\OCP\Share::SHARE_TYPE_USER, $share2->getShareType());
		$this->assertSame($sharedWith, $share2->getSharedWith());
		$this->assertSame($sharedBy, $share2->getSharedBy());
		$this->assertSame($shareOwner, $share2->getShareOwner());
		$this->assertSame(1, $share2->getPermissions());
		$this->assertSame('/target', $share2->getTarget());
		$this->assertLessThanOrEqual(time(), $share2->getSharetime());
		$this->assertSame($path, $share2->getPath());
	}

	public function testCreateGroupShare() {
		$share = new \OC\Share20\Share();

		$sharedWith = $this->getMock('OCP\IGroup');
		$sharedWith->method('getGID')->willReturn('sharedWith');
		$sharedBy = $this->getMock('OCP\IUser');
		$sharedBy->method('getUID')->willReturn('sharedBy');
		$shareOwner = $this->getMock('OCP\IUser');
		$shareOwner->method('getUID')->WillReturn('shareOwner');

		$this->userManager
			->method('get')
			->will($this->returnValueMap([
				['sharedBy', $sharedBy],
				['shareOwner', $shareOwner],
			]));
		$this->groupManager
			->method('get')
			->with('sharedWith')
			->willReturn($sharedWith);

		$path = $this->getMock('\OCP\Files\Folder');
		$path->method('getId')->willReturn(100);
		$path->method('getOwner')->willReturn($shareOwner);

		$ownerFolder = $this->getMock('OCP\Files\Folder');
		$userFolder = $this->getMock('OCP\Files\Folder');
		$this->rootFolder
			->method('getUserFolder')
			->will($this->returnValueMap([
				['sharedBy', $userFolder],
				['shareOwner', $ownerFolder],
			]));

		$userFolder->method('getById')
			->with(100)
			->willReturn([$path]);
		$ownerFolder->method('getById')
			->with(100)
			->willReturn([$path]);

		$share->setShareType(\OCP\Share::SHARE_TYPE_GROUP);
		$share->setSharedWith($sharedWith);
		$share->setSharedBy($sharedBy);
		$share->setShareOwner($shareOwner);
		$share->setPath($path);
		$share->setPermissions(1);
		$share->setTarget('/target');

		$share2 = $this->provider->create($share);

		$this->assertNotNull($share2->getId());
		$this->assertSame('ocinternal:'.$share2->getId(), $share2->getFullId());
		$this->assertSame(\OCP\Share::SHARE_TYPE_GROUP, $share2->getShareType());
		$this->assertSame($sharedWith, $share2->getSharedWith());
		$this->assertSame($sharedBy, $share2->getSharedBy());
		$this->assertSame($shareOwner, $share2->getShareOwner());
		$this->assertSame(1, $share2->getPermissions());
		$this->assertSame('/target', $share2->getTarget());
		$this->assertLessThanOrEqual(time(), $share2->getSharetime());
		$this->assertSame($path, $share2->getPath());
	}

	public function testCreateLinkShare() {
		$share = new \OC\Share20\Share();

		$sharedBy = $this->getMock('OCP\IUser');
		$sharedBy->method('getUID')->willReturn('sharedBy');
		$shareOwner = $this->getMock('OCP\IUser');
		$shareOwner->method('getUID')->WillReturn('shareOwner');

		$this->userManager
				->method('get')
				->will($this->returnValueMap([
						['sharedBy', $sharedBy],
						['shareOwner', $shareOwner],
				]));

		$path = $this->getMock('\OCP\Files\Folder');
		$path->method('getId')->willReturn(100);
		$path->method('getOwner')->willReturn($shareOwner);

		$ownerFolder = $this->getMock('OCP\Files\Folder');
		$userFolder = $this->getMock('OCP\Files\Folder');
		$this->rootFolder
				->method('getUserFolder')
				->will($this->returnValueMap([
						['sharedBy', $userFolder],
						['shareOwner', $ownerFolder],
				]));

		$userFolder->method('getById')
				->with(100)
				->willReturn([$path]);
		$ownerFolder->method('getById')
				->with(100)
				->willReturn([$path]);

		$share->setShareType(\OCP\Share::SHARE_TYPE_LINK);
		$share->setSharedBy($sharedBy);
		$share->setShareOwner($shareOwner);
		$share->setPath($path);
		$share->setPermissions(1);
		$share->setPassword('password');
		$share->setToken('token');
		$expireDate = new \DateTime();
		$share->setExpirationDate($expireDate);
		$share->setTarget('/target');

		$share2 = $this->provider->create($share);

		$this->assertNotNull($share2->getId());
		$this->assertSame('ocinternal:'.$share2->getId(), $share2->getFullId());
		$this->assertSame(\OCP\Share::SHARE_TYPE_LINK, $share2->getShareType());
		$this->assertSame($sharedBy, $share2->getSharedBy());
		$this->assertSame($shareOwner, $share2->getShareOwner());
		$this->assertSame(1, $share2->getPermissions());
		$this->assertSame('/target', $share2->getTarget());
		$this->assertLessThanOrEqual(time(), $share2->getSharetime());
		$this->assertSame($path, $share2->getPath());
		$this->assertSame('password', $share2->getPassword());
		$this->assertSame('token', $share2->getToken());
		$this->assertEquals($expireDate, $share2->getExpirationDate());
	}

	public function testGetShareByToken() {
		$qb = $this->dbConn->getQueryBuilder();

		$qb->insert('share')
			->values([
				'share_type'    => $qb->expr()->literal(\OCP\Share::SHARE_TYPE_LINK),
				'share_with'    => $qb->expr()->literal('password'),
				'uid_owner'     => $qb->expr()->literal('shareOwner'),
				'uid_initiator' => $qb->expr()->literal('sharedBy'),
				'item_type'     => $qb->expr()->literal('file'),
				'file_source'   => $qb->expr()->literal(42),
				'file_target'   => $qb->expr()->literal('myTarget'),
				'permissions'   => $qb->expr()->literal(13),
				'token'         => $qb->expr()->literal('secrettoken'),
			]);
		$qb->execute();
		$id = $qb->getLastInsertId();

		$owner = $this->getMock('\OCP\IUser');
		$owner->method('getUID')->willReturn('shareOwner');
		$initiator = $this->getMock('\OCP\IUser');
		$initiator->method('getUID')->willReturn('sharedBy');

		$this->userManager->method('get')
			->will($this->returnValueMap([
				['sharedBy', $initiator],
				['shareOwner', $owner],
			]));

		$file = $this->getMock('\OCP\Files\File');

		$this->rootFolder->method('getUserFolder')->with('shareOwner')->will($this->returnSelf());
		$this->rootFolder->method('getById')->with(42)->willReturn([$file]);

		$share = $this->provider->getShareByToken('secrettoken');
		$this->assertEquals($id, $share->getId());
		$this->assertSame($owner, $share->getShareOwner());
		$this->assertSame($initiator, $share->getSharedBy());
		$this->assertSame('secrettoken', $share->getToken());
		$this->assertSame('password', $share->getPassword());
		$this->assertSame(null, $share->getSharedWith());
	}

	/**
	 * @expectedException \OC\Share20\Exception\ShareNotFound
	 */
	public function testGetShareByTokenNotFound() {
		$this->provider->getShareByToken('invalidtoken');
	}
}
