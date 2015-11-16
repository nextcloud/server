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


		$path = $this->getMock('OCP\Files\File');
		$path
			->expects($this->exactly(2))
			->method('getId')
			->willReturn(42);

		$sharedWith = $this->getMock('OCP\IUser');
		$sharedWith
			->expects($this->once())
			->method('getUID')
			->willReturn('sharedWith');
		$sharedBy = $this->getMock('OCP\IUser');
		$sharedBy
			->expects($this->once())
			->method('getUID')
			->willReturn('sharedBy');

		$share = $this->getMock('OC\Share20\IShare');
		$share
			->method('getId')
			->willReturn($id);
		$share
			->expects($this->once())
			->method('getShareType')
			->willReturn(\OCP\Share::SHARE_TYPE_USER);
		$share
			->expects($this->exactly(3))
			->method('getPath')
			->willReturn($path);
		$share
			->expects($this->once())
			->method('getSharedWith')
			->willReturn($sharedWith);
		$share
			->expects($this->once())
			->method('getSharedBy')
			->willReturn($sharedBy);
		$share
			->expects($this->once())
			->method('getTarget')
			->willReturn('myTarget');

		$provider = $this->getMockBuilder('OC\Share20\DefaultShareProvider')
            ->setConstructorArgs([  
                    $this->dbConn,
                    $this->userManager,
                    $this->groupManager,
                    $this->userFolder,
                ]        
            )            
            ->setMethods(['deleteChildren', 'getShareById'])
            ->getMock();
		$provider
			->expects($this->once())
			->method('deleteChildren');
		$provider
			->expects($this->once())
			->method('getShareById')
			->willReturn($share);

		$hookListner = $this->getMockBuilder('Dummy')->setMethods(['listen'])->getMock();
		\OCP\Util::connectHook('OCP\Share', 'pre_unshare', $hookListner, 'listen');
		\OCP\Util::connectHook('OCP\Share', 'post_unshare', $hookListner, 'listen');

		$hookListnerExpects = [
			'id' => $id,
			'itemType' => 'file',
			'itemSource' => 42,
			'shareType' => \OCP\Share::SHARE_TYPE_USER,
			'shareWith' => 'sharedWith',
			'itemparent' => null,
			'uidOwner' => 'sharedBy',
			'fileSource' => 42,
			'fileTarget' => 'myTarget',
		];

		$hookListner
			->expects($this->exactly(2))
			->method('listen')
			->with($hookListnerExpects);


		$provider->delete($share);

		$qb = $this->dbConn->getQueryBuilder();
		$qb->select('*')
			->from('share');

		$cursor = $qb->execute();
		$result = $cursor->fetchAll();
		$cursor->closeCursor();

		$this->assertEmpty($result);
	}

	public function testDeleteNestedShares() {
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
		$id1 = $cursor->fetch();
		$id1 = $id1['id'];
		$cursor->closeCursor();


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
				'parent' => $qb->expr()->literal($id1),
			]);
		$this->assertEquals(1, $qb->execute());

		// Get the id
		$qb = $this->dbConn->getQueryBuilder();
		$cursor = $qb->select('id')
			->from('share')
			->setMaxResults(1)
			->orderBy('id', 'DESC')
			->execute();
		$id2 = $cursor->fetch();
		$id2 = $id2['id'];
		$cursor->closeCursor();


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
				'parent' => $qb->expr()->literal($id2),
			]);
		$this->assertEquals(1, $qb->execute());

		$storage = $this->getMock('OC\Files\Storage\Storage');
		$storage
			->method('getOwner')
			->willReturn('shareOwner');
		$path = $this->getMock('OCP\Files\Node');
		$path
			->method('getStorage')
			->wilLReturn($storage);
		$this->userFolder
			->method('getById')
			->with(42)
			->willReturn([$path]);

		$sharedWith = $this->getMock('OCP\IUser');
		$sharedWith
			->method('getUID')
			->willReturn('sharedWith');
		$sharedBy = $this->getMock('OCP\IUser');
		$sharedBy
			->method('getUID')
			->willReturn('sharedBy');
		$shareOwner = $this->getMock('OCP\IUser');
		$this->userManager
			->method('get')
			->will($this->returnValueMap([
				['sharedWith', $sharedWith],
				['sharedBy', $sharedBy],
				['shareOwner', $shareOwner],
			]));

		$share = $this->provider->getShareById($id1);
		$this->provider->delete($share);

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
		$share
			->expects($this->once())
			->method('getShareType')
			->willReturn(\OCP\Share::SHARE_TYPE_LINK);

		$path = $this->getMock('OCP\Files\Folder');
		$path
			->expects($this->exactly(2))
			->method('getId')
			->willReturn(100);
		$share
			->expects($this->exactly(3))
			->method('getPath')
			->willReturn($path);

		$sharedBy = $this->getMock('OCP\IUser');
		$sharedBy
			->expects($this->once())
			->method('getUID');
		$share
			->expects($this->once())
			->method('getSharedBy')
			->willReturn($sharedBy);

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
            ->setMethods(['deleteChildren', 'getShareById'])
            ->getMock();
		$provider
			->expects($this->once())
			->method('deleteChildren')
			->with($share);
		$provider
			->expects($this->once())
			->method('getShareById')
			->with(42)
			->willReturn($share);
		
		$provider->delete($share);
	}
}
