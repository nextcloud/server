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
namespace OCA\FederatedFileSharing\Tests;


use OCA\FederatedFileSharing\AddressHandler;
use OCA\FederatedFileSharing\FederatedShareProvider;
use OCA\FederatedFileSharing\Notifications;
use OCA\FederatedFileSharing\TokenHandler;
use OCP\Files\IRootFolder;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\ILogger;
use OCP\Share\IManager;
use Test\TestCase;

/**
 * Class FederatedShareProviderTest
 *
 * @package OCA\FederatedFileSharing\Tests
 * @group DB
 */
class FederatedShareProviderTest extends TestCase {

	/** @var IDBConnection */
	protected $connection;
	/** @var AddressHandler | \PHPUnit_Framework_MockObject_MockObject */
	protected $addressHandler;
	/** @var Notifications | \PHPUnit_Framework_MockObject_MockObject */
	protected $notifications;
	/** @var TokenHandler */
	protected $tokenHandler;
	/** @var IL10N */
	protected $l;
	/** @var ILogger */
	protected $logger;
	/** @var IRootFolder | \PHPUnit_Framework_MockObject_MockObject */
	protected $rootFolder;

	/** @var IManager */
	protected $shareManager;
	/** @var FederatedShareProvider */
	protected $provider;


	public function setUp() {
		parent::setUp();

		$this->connection = \OC::$server->getDatabaseConnection();
		$this->notifications = $this->getMockBuilder('OCA\FederatedFileSharing\Notifications')
			->disableOriginalConstructor()
			->getMock();
		$this->tokenHandler = $this->getMockBuilder('OCA\FederatedFileSharing\TokenHandler')
			->disableOriginalConstructor()
			->getMock();
		$this->l = $this->getMock('OCP\IL10N');
		$this->l->method('t')
			->will($this->returnCallback(function($text, $parameters = []) {
				return vsprintf($text, $parameters);
			}));
		$this->logger = $this->getMock('OCP\ILogger');
		$this->rootFolder = $this->getMock('OCP\Files\IRootFolder');
		$this->addressHandler = new AddressHandler(\OC::$server->getURLGenerator(), $this->l);

		$this->provider = new FederatedShareProvider(
			$this->connection,
			$this->addressHandler,
			$this->notifications,
			$this->tokenHandler,
			$this->l,
			$this->logger,
			$this->rootFolder
		);

		$this->shareManager = \OC::$server->getShareManager();
	}

	public function tearDown() {
		$this->connection->getQueryBuilder()->delete('share')->execute();

		return parent::tearDown();
	}

	public function testCreate() {
		$share = $this->shareManager->newShare();

		$node = $this->getMock('\OCP\Files\File');
		$node->method('getId')->willReturn(42);
		$node->method('getName')->willReturn('myFile');

		$share->setSharedWith('user@server.com')
			->setSharedBy('sharedBy')
			->setShareOwner('shareOwner')
			->setPermissions(19)
			->setNode($node);

		$this->tokenHandler->method('generateToken')->willReturn('token');

		$this->notifications->expects($this->once())
			->method('sendRemoteShare')
			->with(
				$this->equalTo('token'),
				$this->equalTo('user@server.com'),
				$this->equalTo('myFile'),
				$this->anything(),
				'sharedBy'
			)->willReturn(true);

		$this->rootFolder->expects($this->never())->method($this->anything());

		$share = $this->provider->create($share);

		$qb = $this->connection->getQueryBuilder();
		$stmt = $qb->select('*')
			->from('share')
			->where($qb->expr()->eq('id', $qb->createNamedParameter($share->getId())))
			->execute();

		$data = $stmt->fetch();
		$stmt->closeCursor();

		$expected = [
			'share_type' => \OCP\Share::SHARE_TYPE_REMOTE,
			'share_with' => 'user@server.com',
			'uid_owner' => 'shareOwner',
			'uid_initiator' => 'sharedBy',
			'item_type' => 'file',
			'item_source' => 42,
			'file_source' => 42,
			'permissions' => 19,
			'accepted' => 0,
			'token' => 'token',
		];
		$this->assertArraySubset($expected, $data);

		$this->assertEquals($data['id'], $share->getId());
		$this->assertEquals(\OCP\Share::SHARE_TYPE_REMOTE, $share->getShareType());
		$this->assertEquals('user@server.com', $share->getSharedWith());
		$this->assertEquals('sharedBy', $share->getSharedBy());
		$this->assertEquals('shareOwner', $share->getShareOwner());
		$this->assertEquals('file', $share->getNodeType());
		$this->assertEquals(42, $share->getNodeId());
		$this->assertEquals(19, $share->getPermissions());
		$this->assertEquals('token', $share->getToken());
	}

	public function testCreateCouldNotFindServer() {
		$share = $this->shareManager->newShare();

		$node = $this->getMock('\OCP\Files\File');
		$node->method('getId')->willReturn(42);
		$node->method('getName')->willReturn('myFile');

		$share->setSharedWith('user@server.com')
			->setSharedBy('sharedBy')
			->setShareOwner('shareOwner')
			->setPermissions(19)
			->setNode($node);

		$this->tokenHandler->method('generateToken')->willReturn('token');

		$this->notifications->expects($this->once())
			->method('sendRemoteShare')
			->with(
				$this->equalTo('token'),
				$this->equalTo('user@server.com'),
				$this->equalTo('myFile'),
				$this->anything(),
				'sharedBy'
			)->willReturn(false);

		$this->rootFolder->expects($this->once())
			->method('getUserFolder')
			->with('shareOwner')
			->will($this->returnSelf());
		$this->rootFolder->method('getById')
			->with('42')
			->willReturn([$node]);

		try {
			$share = $this->provider->create($share);
			$this->fail();
		} catch (\Exception $e) {
			$this->assertEquals('Sharing myFile failed, could not find user@server.com, maybe the server is currently unreachable.', $e->getMessage());
		}

		$qb = $this->connection->getQueryBuilder();
		$stmt = $qb->select('*')
			->from('share')
			->where($qb->expr()->eq('id', $qb->createNamedParameter($share->getId())))
			->execute();

		$data = $stmt->fetch();
		$stmt->closeCursor();

		$this->assertFalse($data);
	}

	public function testCreateShareWithSelf() {
		$share = $this->shareManager->newShare();

		$node = $this->getMock('\OCP\Files\File');
		$node->method('getId')->willReturn(42);
		$node->method('getName')->willReturn('myFile');

		$shareWith = 'sharedBy@' . $this->addressHandler->generateRemoteURL();

		$share->setSharedWith($shareWith)
			->setSharedBy('sharedBy')
			->setShareOwner('shareOwner')
			->setPermissions(19)
			->setNode($node);

		$this->rootFolder->expects($this->never())->method($this->anything());

		try {
			$share = $this->provider->create($share);
			$this->fail();
		} catch (\Exception $e) {
			$this->assertEquals('Not allowed to create a federated share with the same user', $e->getMessage());
		}

		$qb = $this->connection->getQueryBuilder();
		$stmt = $qb->select('*')
			->from('share')
			->where($qb->expr()->eq('id', $qb->createNamedParameter($share->getId())))
			->execute();

		$data = $stmt->fetch();
		$stmt->closeCursor();

		$this->assertFalse($data);
	}

	public function testCreateAlreadyShared() {
		$share = $this->shareManager->newShare();

		$node = $this->getMock('\OCP\Files\File');
		$node->method('getId')->willReturn(42);
		$node->method('getName')->willReturn('myFile');

		$share->setSharedWith('user@server.com')
			->setSharedBy('sharedBy')
			->setShareOwner('shareOwner')
			->setPermissions(19)
			->setNode($node);

		$this->tokenHandler->method('generateToken')->willReturn('token');

		$this->notifications->expects($this->once())
			->method('sendRemoteShare')
			->with(
				$this->equalTo('token'),
				$this->equalTo('user@server.com'),
				$this->equalTo('myFile'),
				$this->anything(),
				'sharedBy'
			)->willReturn(true);

		$this->rootFolder->expects($this->never())->method($this->anything());

		$this->provider->create($share);

		try {
			$this->provider->create($share);
		} catch (\Exception $e) {
			$this->assertEquals('Sharing myFile failed, because this item is already shared with user@server.com', $e->getMessage());
		}
	}

	public function testUpdate() {
		$share = $this->shareManager->newShare();

		$node = $this->getMock('\OCP\Files\File');
		$node->method('getId')->willReturn(42);
		$node->method('getName')->willReturn('myFile');

		$share->setSharedWith('user@server.com')
			->setSharedBy('sharedBy')
			->setShareOwner('shareOwner')
			->setPermissions(19)
			->setNode($node);

		$this->tokenHandler->method('generateToken')->willReturn('token');

		$this->notifications->expects($this->once())
			->method('sendRemoteShare')
			->with(
				$this->equalTo('token'),
				$this->equalTo('user@server.com'),
				$this->equalTo('myFile'),
				$this->anything(),
				'sharedBy'
			)->willReturn(true);

		$this->rootFolder->expects($this->never())->method($this->anything());

		$share = $this->provider->create($share);

		$share->setPermissions(1);
		$this->provider->update($share);

		$share = $this->provider->getShareById($share->getId());

		$this->assertEquals(1, $share->getPermissions());
	}

	public function testGetSharedBy() {
		$node = $this->getMock('\OCP\Files\File');
		$node->method('getId')->willReturn(42);
		$node->method('getName')->willReturn('myFile');

		$this->tokenHandler->method('generateToken')->willReturn('token');
		$this->notifications
			->method('sendRemoteShare')
			->willReturn(true);

		$this->rootFolder->expects($this->never())->method($this->anything());

		$share = $this->shareManager->newShare();
		$share->setSharedWith('user@server.com')
			->setSharedBy('sharedBy')
			->setShareOwner('shareOwner')
			->setPermissions(19)
			->setNode($node);
		$this->provider->create($share);

		$share2 = $this->shareManager->newShare();
		$share2->setSharedWith('user2@server.com')
			->setSharedBy('sharedBy2')
			->setShareOwner('shareOwner')
			->setPermissions(19)
			->setNode($node);
		$this->provider->create($share2);

		$shares = $this->provider->getSharesBy('sharedBy', \OCP\Share::SHARE_TYPE_REMOTE, null, false, -1, 0);

		$this->assertCount(1, $shares);
		$this->assertEquals('user@server.com', $shares[0]->getSharedWith());
		$this->assertEquals('sharedBy', $shares[0]->getSharedBy());
	}

	public function testGetSharedByWithNode() {
		$node = $this->getMock('\OCP\Files\File');
		$node->method('getId')->willReturn(42);
		$node->method('getName')->willReturn('myFile');

		$this->tokenHandler->method('generateToken')->willReturn('token');
		$this->notifications
			->method('sendRemoteShare')
			->willReturn(true);

		$this->rootFolder->expects($this->never())->method($this->anything());

		$share = $this->shareManager->newShare();
		$share->setSharedWith('user@server.com')
			->setSharedBy('sharedBy')
			->setShareOwner('shareOwner')
			->setPermissions(19)
			->setNode($node);
		$this->provider->create($share);

		$node2 = $this->getMock('\OCP\Files\File');
		$node2->method('getId')->willReturn(43);
		$node2->method('getName')->willReturn('myOtherFile');

		$share2 = $this->shareManager->newShare();
		$share2->setSharedWith('user@server.com')
			->setSharedBy('sharedBy')
			->setShareOwner('shareOwner')
			->setPermissions(19)
			->setNode($node2);
		$this->provider->create($share2);

		$shares = $this->provider->getSharesBy('sharedBy', \OCP\Share::SHARE_TYPE_REMOTE, $node2, false, -1, 0);

		$this->assertCount(1, $shares);
		$this->assertEquals(43, $shares[0]->getNodeId());
	}

	public function testGetSharedByWithReshares() {
		$node = $this->getMock('\OCP\Files\File');
		$node->method('getId')->willReturn(42);
		$node->method('getName')->willReturn('myFile');

		$this->tokenHandler->method('generateToken')->willReturn('token');
		$this->notifications
			->method('sendRemoteShare')
			->willReturn(true);

		$this->rootFolder->expects($this->never())->method($this->anything());

		$share = $this->shareManager->newShare();
		$share->setSharedWith('user@server.com')
			->setSharedBy('shareOwner')
			->setShareOwner('shareOwner')
			->setPermissions(19)
			->setNode($node);
		$this->provider->create($share);

		$share2 = $this->shareManager->newShare();
		$share2->setSharedWith('user2@server.com')
			->setSharedBy('sharedBy')
			->setShareOwner('shareOwner')
			->setPermissions(19)
			->setNode($node);
		$this->provider->create($share2);

		$shares = $this->provider->getSharesBy('shareOwner', \OCP\Share::SHARE_TYPE_REMOTE, null, true, -1, 0);

		$this->assertCount(2, $shares);
	}

	public function testGetSharedByWithLimit() {
		$node = $this->getMock('\OCP\Files\File');
		$node->method('getId')->willReturn(42);
		$node->method('getName')->willReturn('myFile');

		$this->tokenHandler->method('generateToken')->willReturn('token');
		$this->notifications
			->method('sendRemoteShare')
			->willReturn(true);

		$this->rootFolder->expects($this->never())->method($this->anything());

		$share = $this->shareManager->newShare();
		$share->setSharedWith('user@server.com')
			->setSharedBy('sharedBy')
			->setShareOwner('shareOwner')
			->setPermissions(19)
			->setNode($node);
		$this->provider->create($share);

		$share2 = $this->shareManager->newShare();
		$share2->setSharedWith('user2@server.com')
			->setSharedBy('sharedBy')
			->setShareOwner('shareOwner')
			->setPermissions(19)
			->setNode($node);
		$this->provider->create($share2);

		$shares = $this->provider->getSharesBy('shareOwner', \OCP\Share::SHARE_TYPE_REMOTE, null, true, 1, 1);

		$this->assertCount(1, $shares);
		$this->assertEquals('user2@server.com', $shares[0]->getSharedWith());
	}
}
