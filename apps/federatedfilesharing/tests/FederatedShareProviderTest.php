<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
namespace OCA\FederatedFileSharing\Tests;


use OCA\FederatedFileSharing\AddressHandler;
use OCA\FederatedFileSharing\FederatedShareProvider;
use OCA\FederatedFileSharing\Notifications;
use OCA\FederatedFileSharing\TokenHandler;
use OCP\Files\IRootFolder;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\ILogger;
use OCP\IUserManager;
use OCP\Share\IManager;

/**
 * Class FederatedShareProviderTest
 *
 * @package OCA\FederatedFileSharing\Tests
 * @group DB
 */
class FederatedShareProviderTest extends \Test\TestCase {

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
	/** @var  IConfig | \PHPUnit_Framework_MockObject_MockObject */
	protected $config;
	/** @var  IUserManager | \PHPUnit_Framework_MockObject_MockObject */
	protected $userManager;

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
		$this->config = $this->getMock('OCP\IConfig');
		$this->userManager = $this->getMock('OCP\IUserManager');
		//$this->addressHandler = new AddressHandler(\OC::$server->getURLGenerator(), $this->l);
		$this->addressHandler = $this->getMockBuilder('OCA\FederatedFileSharing\AddressHandler')->disableOriginalConstructor()->getMock();

		$this->userManager->expects($this->any())->method('userExists')->willReturn(true);

		$this->provider = new FederatedShareProvider(
			$this->connection,
			$this->addressHandler,
			$this->notifications,
			$this->tokenHandler,
			$this->l,
			$this->logger,
			$this->rootFolder,
			$this->config,
			$this->userManager
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

		$this->addressHandler->expects($this->any())->method('generateRemoteURL')
			->willReturn('http://localhost/');
		$this->addressHandler->expects($this->any())->method('splitUserRemote')
			->willReturn(['user', 'server.com']);

		$this->notifications->expects($this->once())
			->method('sendRemoteShare')
			->with(
				$this->equalTo('token'),
				$this->equalTo('user@server.com'),
				$this->equalTo('myFile'),
				$this->anything(),
				'shareOwner',
				'shareOwner@http://localhost/',
				'sharedBy',
				'sharedBy@http://localhost/'
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

		$this->addressHandler->expects($this->any())->method('generateRemoteURL')
			->willReturn('http://localhost/');
		$this->addressHandler->expects($this->any())->method('splitUserRemote')
			->willReturn(['user', 'server.com']);

		$this->notifications->expects($this->once())
			->method('sendRemoteShare')
			->with(
				$this->equalTo('token'),
				$this->equalTo('user@server.com'),
				$this->equalTo('myFile'),
				$this->anything(),
				'shareOwner',
				'shareOwner@http://localhost/',
				'sharedBy',
				'sharedBy@http://localhost/'
			)->willReturn(false);

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

	public function testCreateException() {
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

		$this->addressHandler->expects($this->any())->method('generateRemoteURL')
			->willReturn('http://localhost/');
		$this->addressHandler->expects($this->any())->method('splitUserRemote')
			->willReturn(['user', 'server.com']);

		$this->notifications->expects($this->once())
			->method('sendRemoteShare')
			->with(
				$this->equalTo('token'),
				$this->equalTo('user@server.com'),
				$this->equalTo('myFile'),
				$this->anything(),
				'shareOwner',
				'shareOwner@http://localhost/',
				'sharedBy',
				'sharedBy@http://localhost/'
			)->willThrowException(new \Exception('dummy'));

		$this->rootFolder->method('getById')
			->with('42')
			->willReturn([$node]);

		try {
			$share = $this->provider->create($share);
			$this->fail();
		} catch (\Exception $e) {
			$this->assertEquals('dummy', $e->getMessage());
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

		$this->addressHandler->expects($this->any())->method('compareAddresses')
			->willReturn(true);

		$shareWith = 'sharedBy@localhost';

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


		$this->addressHandler->expects($this->any())->method('splitUserRemote')
			->willReturn(['user', 'server.com']);

		$share->setSharedWith('user@server.com')
			->setSharedBy('sharedBy')
			->setShareOwner('shareOwner')
			->setPermissions(19)
			->setNode($node);

		$this->tokenHandler->method('generateToken')->willReturn('token');

		$this->addressHandler->expects($this->any())->method('generateRemoteURL')
			->willReturn('http://localhost/');

		$this->notifications->expects($this->once())
			->method('sendRemoteShare')
			->with(
				$this->equalTo('token'),
				$this->equalTo('user@server.com'),
				$this->equalTo('myFile'),
				$this->anything(),
				'shareOwner',
				'shareOwner@http://localhost/',
				'sharedBy',
				'sharedBy@http://localhost/'
			)->willReturn(true);

		$this->rootFolder->expects($this->never())->method($this->anything());

		$this->provider->create($share);

		try {
			$this->provider->create($share);
		} catch (\Exception $e) {
			$this->assertEquals('Sharing myFile failed, because this item is already shared with user@server.com', $e->getMessage());
		}
	}

	/**
	 * @dataProvider datatTestUpdate
	 *
	 */
	public function testUpdate($owner, $sharedBy) {

		$this->provider = $this->getMockBuilder('OCA\FederatedFileSharing\FederatedShareProvider')
			->setConstructorArgs(
				[
					$this->connection,
					$this->addressHandler,
					$this->notifications,
					$this->tokenHandler,
					$this->l,
					$this->logger,
					$this->rootFolder,
					$this->config,
					$this->userManager
				]
			)->setMethods(['sendPermissionUpdate'])->getMock();

		$share = $this->shareManager->newShare();

		$node = $this->getMock('\OCP\Files\File');
		$node->method('getId')->willReturn(42);
		$node->method('getName')->willReturn('myFile');


		$this->addressHandler->expects($this->any())->method('splitUserRemote')
			->willReturn(['user', 'server.com']);

		$share->setSharedWith('user@server.com')
			->setSharedBy($sharedBy)
			->setShareOwner($owner)
			->setPermissions(19)
			->setNode($node);

		$this->tokenHandler->method('generateToken')->willReturn('token');
		$this->addressHandler->expects($this->any())->method('generateRemoteURL')
			->willReturn('http://localhost/');

		$this->notifications->expects($this->once())
			->method('sendRemoteShare')
			->with(
				$this->equalTo('token'),
				$this->equalTo('user@server.com'),
				$this->equalTo('myFile'),
				$this->anything(),
				$owner,
				$owner . '@http://localhost/',
				$sharedBy,
				$sharedBy . '@http://localhost/'
			)->willReturn(true);

		if($owner === $sharedBy) {
			$this->provider->expects($this->never())->method('sendPermissionUpdate');
		} else {
			$this->provider->expects($this->once())->method('sendPermissionUpdate');
		}

		$this->rootFolder->expects($this->never())->method($this->anything());

		$share = $this->provider->create($share);

		$share->setPermissions(1);
		$this->provider->update($share);

		$share = $this->provider->getShareById($share->getId());

		$this->assertEquals(1, $share->getPermissions());
	}

	public function datatTestUpdate() {
		return [
			['sharedBy', 'shareOwner'],
			['shareOwner', 'shareOwner']
		];
	}

	public function testGetSharedBy() {
		$node = $this->getMock('\OCP\Files\File');
		$node->method('getId')->willReturn(42);
		$node->method('getName')->willReturn('myFile');

		$this->addressHandler->expects($this->at(0))->method('splitUserRemote')
			->willReturn(['user', 'server.com']);

		$this->addressHandler->expects($this->at(1))->method('splitUserRemote')
			->willReturn(['user2', 'server.com']);

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

		$this->addressHandler->expects($this->any())->method('splitUserRemote')
			->willReturnCallback(function ($uid) {
				if ($uid === 'user@server.com') {
					return ['user', 'server.com'];
				}
				return ['user2', 'server.com'];
		});

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

	public function dataDeleteUser() {
		return [
			['a', 'b', 'c', 'a', true],
			['a', 'b', 'c', 'b', false],
			// The recipient is non local.
			['a', 'b', 'c', 'c', false],
			['a', 'b', 'c', 'd', false],
		];
	}

	/**
	 * @dataProvider dataDeleteUser
	 *
	 * @param string $owner The owner of the share (uid)
	 * @param string $initiator The initiator of the share (uid)
	 * @param string $recipient The recipient of the share (uid/gid/pass)
	 * @param string $deletedUser The user that is deleted
	 * @param bool $rowDeleted Is the row deleted in this setup
	 */
	public function testDeleteUser($owner, $initiator, $recipient, $deletedUser, $rowDeleted) {
		$qb = $this->connection->getQueryBuilder();
		$qb->insert('share')
			->setValue('share_type', $qb->createNamedParameter(\OCP\Share::SHARE_TYPE_REMOTE))
			->setValue('uid_owner', $qb->createNamedParameter($owner))
			->setValue('uid_initiator', $qb->createNamedParameter($initiator))
			->setValue('share_with', $qb->createNamedParameter($recipient))
			->setValue('item_type', $qb->createNamedParameter('file'))
			->setValue('item_source', $qb->createNamedParameter(42))
			->setValue('file_source', $qb->createNamedParameter(42))
			->execute();

		$id = $qb->getLastInsertId();

		$this->provider->userDeleted($deletedUser, \OCP\Share::SHARE_TYPE_REMOTE);

		$qb = $this->connection->getQueryBuilder();
		$qb->select('*')
			->from('share')
			->where(
				$qb->expr()->eq('id', $qb->createNamedParameter($id))
			);
		$cursor = $qb->execute();
		$data = $cursor->fetchAll();
		$cursor->closeCursor();

		$this->assertCount($rowDeleted ? 0 : 1, $data);
	}

	/**
	 * @dataProvider dataTestFederatedSharingSettings
	 *
	 * @param string $isEnabled
	 * @param bool $expected
	 */
	public function testIsOutgoingServer2serverShareEnabled($isEnabled, $expected) {
		$this->config->expects($this->once())->method('getAppValue')
			->with('files_sharing', 'outgoing_server2server_share_enabled', 'yes')
			->willReturn($isEnabled);

		$this->assertSame($expected,
			$this->provider->isOutgoingServer2serverShareEnabled()
		);
	}

	/**
	 * @dataProvider dataTestFederatedSharingSettings
	 *
	 * @param string $isEnabled
	 * @param bool $expected
	 */
	public function testIsIncomingServer2serverShareEnabled($isEnabled, $expected) {
		$this->config->expects($this->once())->method('getAppValue')
			->with('files_sharing', 'incoming_server2server_share_enabled', 'yes')
			->willReturn($isEnabled);

		$this->assertSame($expected,
			$this->provider->isIncomingServer2serverShareEnabled()
		);
	}

	public function dataTestFederatedSharingSettings() {
		return [
			['yes', true],
			['no', false]
		];
	}
}
