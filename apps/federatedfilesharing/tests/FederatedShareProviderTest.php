<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\FederatedFileSharing\Tests;

use OC\Federation\CloudIdManager;
use OCA\FederatedFileSharing\AddressHandler;
use OCA\FederatedFileSharing\FederatedShareProvider;
use OCA\FederatedFileSharing\Notifications;
use OCA\FederatedFileSharing\TokenHandler;
use OCP\Constants;
use OCP\Contacts\IManager as IContactsManager;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Federation\ICloudFederationProviderManager;
use OCP\Federation\ICloudIdManager;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\Share\IManager;
use OCP\Share\IShare;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

/**
 * Class FederatedShareProviderTest
 *
 * @package OCA\FederatedFileSharing\Tests
 * @group DB
 */
class FederatedShareProviderTest extends \Test\TestCase {
	/** @var IDBConnection */
	protected $connection;
	/** @var AddressHandler|MockObject */
	protected $addressHandler;
	/** @var Notifications|MockObject */
	protected $notifications;
	/** @var TokenHandler|MockObject */
	protected $tokenHandler;
	/** @var IL10N */
	protected $l;
	/** @var LoggerInterface */
	protected $logger;
	/** @var IRootFolder|MockObject */
	protected $rootFolder;
	/** @var IConfig|MockObject */
	protected $config;
	/** @var IUserManager|MockObject */
	protected $userManager;
	/** @var \OCP\GlobalScale\IConfig|MockObject */
	protected $gsConfig;

	/** @var IManager */
	protected $shareManager;
	/** @var FederatedShareProvider */
	protected $provider;
	/** @var IContactsManager|MockObject */
	protected $contactsManager;

	/** @var ICloudIdManager */
	private $cloudIdManager;

	/** @var MockObject|ICloudFederationProviderManager */
	private $cloudFederationProviderManager;

	protected function setUp(): void {
		parent::setUp();

		$this->connection = \OC::$server->getDatabaseConnection();
		$this->notifications = $this->getMockBuilder('OCA\FederatedFileSharing\Notifications')
			->disableOriginalConstructor()
			->getMock();
		$this->tokenHandler = $this->getMockBuilder('OCA\FederatedFileSharing\TokenHandler')
			->disableOriginalConstructor()
			->getMock();
		$this->l = $this->getMockBuilder(IL10N::class)->getMock();
		$this->l->method('t')
			->willReturnCallback(function ($text, $parameters = []) {
				return vsprintf($text, $parameters);
			});
		$this->logger = $this->getMockBuilder(LoggerInterface::class)->getMock();
		$this->rootFolder = $this->getMockBuilder('OCP\Files\IRootFolder')->getMock();
		$this->config = $this->getMockBuilder(IConfig::class)->getMock();
		$this->userManager = $this->getMockBuilder(IUserManager::class)->getMock();
		//$this->addressHandler = new AddressHandler(\OC::$server->getURLGenerator(), $this->l);
		$this->addressHandler = $this->getMockBuilder('OCA\FederatedFileSharing\AddressHandler')->disableOriginalConstructor()->getMock();
		$this->contactsManager = $this->createMock(IContactsManager::class);
		$this->cloudIdManager = new CloudIdManager(
			$this->contactsManager,
			$this->createMock(IURLGenerator::class),
			$this->userManager,
			$this->createMock(ICacheFactory::class),
			$this->createMock(IEventDispatcher::class)
		);
		$this->gsConfig = $this->createMock(\OCP\GlobalScale\IConfig::class);

		$this->userManager->expects($this->any())->method('userExists')->willReturn(true);

		$this->cloudFederationProviderManager = $this->createMock(ICloudFederationProviderManager::class);

		$this->provider = new FederatedShareProvider(
			$this->connection,
			$this->addressHandler,
			$this->notifications,
			$this->tokenHandler,
			$this->l,
			$this->rootFolder,
			$this->config,
			$this->userManager,
			$this->cloudIdManager,
			$this->gsConfig,
			$this->cloudFederationProviderManager,
			$this->logger,
		);

		$this->shareManager = \OC::$server->getShareManager();
	}

	protected function tearDown(): void {
		$this->connection->getQueryBuilder()->delete('share')->execute();

		parent::tearDown();
	}

	public function dataTestCreate() {
		return [
			[null, null],
			[new \DateTime('2020-03-01T01:02:03'), '2020-03-01 01:02:03'],
		];
	}

	/**
	 * @dataProvider dataTestCreate
	 */
	public function testCreate($expirationDate, $expectedDataDate): void {
		$share = $this->shareManager->newShare();

		/** @var File|MockObject $node */
		$node = $this->getMockBuilder(File::class)->getMock();
		$node->method('getId')->willReturn(42);
		$node->method('getName')->willReturn('myFile');

		$share->setSharedWith('user@server.com')
			->setSharedBy('sharedBy')
			->setShareOwner('shareOwner')
			->setPermissions(19)
			->setShareType(IShare::TYPE_REMOTE)
			->setExpirationDate($expirationDate)
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
				'shareOwner@http://localhost',
				'sharedBy',
				'sharedBy@http://localhost'
			)
			->willReturn(true);

		$this->rootFolder->expects($this->never())->method($this->anything());

		$this->contactsManager->expects($this->any())
			->method('search')
			->willReturn([]);

		$share = $this->provider->create($share);

		$qb = $this->connection->getQueryBuilder();
		$stmt = $qb->select('*')
			->from('share')
			->where($qb->expr()->eq('id', $qb->createNamedParameter($share->getId())))
			->execute();

		$data = $stmt->fetch();
		$stmt->closeCursor();

		$expected = [
			'share_type' => IShare::TYPE_REMOTE,
			'share_with' => 'user@server.com',
			'uid_owner' => 'shareOwner',
			'uid_initiator' => 'sharedBy',
			'item_type' => 'file',
			'item_source' => 42,
			'file_source' => 42,
			'permissions' => 19,
			'accepted' => 0,
			'token' => 'token',
			'expiration' => $expectedDataDate,
		];
		foreach (array_keys($expected) as $key) {
			$this->assertEquals($expected[$key], $data[$key], "Assert that value for key '$key' is the same");
		}

		$this->assertEquals($data['id'], $share->getId());
		$this->assertEquals(IShare::TYPE_REMOTE, $share->getShareType());
		$this->assertEquals('user@server.com', $share->getSharedWith());
		$this->assertEquals('sharedBy', $share->getSharedBy());
		$this->assertEquals('shareOwner', $share->getShareOwner());
		$this->assertEquals('file', $share->getNodeType());
		$this->assertEquals(42, $share->getNodeId());
		$this->assertEquals(19, $share->getPermissions());
		$this->assertEquals('token', $share->getToken());
		$this->assertEquals($expirationDate, $share->getExpirationDate());
	}

	public function testCreateCouldNotFindServer(): void {
		$share = $this->shareManager->newShare();

		$node = $this->getMockBuilder(File::class)->getMock();
		$node->method('getId')->willReturn(42);
		$node->method('getName')->willReturn('myFile');

		$share->setSharedWith('user@server.com')
			->setSharedBy('sharedBy')
			->setShareOwner('shareOwner')
			->setPermissions(19)
			->setShareType(IShare::TYPE_REMOTE)
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
				'shareOwner@http://localhost',
				'sharedBy',
				'sharedBy@http://localhost'
			)->willReturn(false);

		$this->rootFolder->method('getById')
			->with('42')
			->willReturn([$node]);

		$this->contactsManager->expects($this->any())
			->method('search')
			->willReturn([]);

		try {
			$share = $this->provider->create($share);
			$this->fail();
		} catch (\Exception $e) {
			$this->assertEquals('Sharing myFile failed, could not find user@server.com, maybe the server is currently unreachable or uses a self-signed certificate.', $e->getMessage());
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

	public function testCreateException(): void {
		$share = $this->shareManager->newShare();

		$node = $this->getMockBuilder(File::class)->getMock();
		$node->method('getId')->willReturn(42);
		$node->method('getName')->willReturn('myFile');

		$share->setSharedWith('user@server.com')
			->setSharedBy('sharedBy')
			->setShareOwner('shareOwner')
			->setPermissions(19)
			->setShareType(IShare::TYPE_REMOTE)
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
				'shareOwner@http://localhost',
				'sharedBy',
				'sharedBy@http://localhost'
			)->willThrowException(new \Exception('dummy'));

		$this->rootFolder->method('getById')
			->with('42')
			->willReturn([$node]);

		$this->contactsManager->expects($this->any())
			->method('search')
			->willReturn([]);

		try {
			$share = $this->provider->create($share);
			$this->fail();
		} catch (\Exception $e) {
			$this->assertEquals('Sharing myFile failed, could not find user@server.com, maybe the server is currently unreachable or uses a self-signed certificate.', $e->getMessage());
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

	public function testCreateShareWithSelf(): void {
		$share = $this->shareManager->newShare();

		$node = $this->getMockBuilder(File::class)->getMock();
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

		$this->contactsManager->expects($this->any())
			->method('search')
			->willReturn([]);

		$this->rootFolder->expects($this->never())->method($this->anything());

		try {
			$share = $this->provider->create($share);
			$this->fail();
		} catch (\Exception $e) {
			$this->assertEquals('Not allowed to create a federated share to the same account', $e->getMessage());
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

	public function testCreateAlreadyShared(): void {
		$share = $this->shareManager->newShare();

		$node = $this->getMockBuilder(File::class)->getMock();
		$node->method('getId')->willReturn(42);
		$node->method('getName')->willReturn('myFile');


		$this->addressHandler->expects($this->any())->method('splitUserRemote')
			->willReturn(['user', 'server.com']);

		$share->setSharedWith('user@server.com')
			->setSharedBy('sharedBy')
			->setShareOwner('shareOwner')
			->setPermissions(19)
			->setShareType(IShare::TYPE_REMOTE)
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
				'shareOwner@http://localhost',
				'sharedBy',
				'sharedBy@http://localhost'
			)->willReturn(true);

		$this->rootFolder->expects($this->never())->method($this->anything());

		$this->contactsManager->expects($this->any())
			->method('search')
			->willReturn([]);

		$this->provider->create($share);

		try {
			$this->provider->create($share);
		} catch (\Exception $e) {
			$this->assertEquals('Sharing myFile failed, because this item is already shared with the account user@server.com', $e->getMessage());
		}
	}

	/**
	 * @dataProvider dataTestUpdate
	 */
	public function testUpdate($owner, $sharedBy, $expirationDate): void {
		$this->provider = $this->getMockBuilder('OCA\FederatedFileSharing\FederatedShareProvider')
			->setConstructorArgs(
				[
					$this->connection,
					$this->addressHandler,
					$this->notifications,
					$this->tokenHandler,
					$this->l,
					$this->rootFolder,
					$this->config,
					$this->userManager,
					$this->cloudIdManager,
					$this->gsConfig,
					$this->cloudFederationProviderManager,
					$this->logger,
				]
			)->setMethods(['sendPermissionUpdate'])->getMock();

		$share = $this->shareManager->newShare();

		$node = $this->getMockBuilder(File::class)->getMock();
		$node->method('getId')->willReturn(42);
		$node->method('getName')->willReturn('myFile');

		$this->addressHandler->expects($this->any())->method('splitUserRemote')
			->willReturn(['user', 'server.com']);

		$share->setSharedWith('user@server.com')
			->setSharedBy($sharedBy)
			->setShareOwner($owner)
			->setPermissions(19)
			->setShareType(IShare::TYPE_REMOTE)
			->setExpirationDate(new \DateTime('2019-02-01T01:02:03'))
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
				$owner . '@http://localhost',
				$sharedBy,
				$sharedBy . '@http://localhost'
			)->willReturn(true);

		if ($owner === $sharedBy) {
			$this->provider->expects($this->never())->method('sendPermissionUpdate');
		} else {
			$this->provider->expects($this->once())->method('sendPermissionUpdate');
		}

		$this->rootFolder->expects($this->never())->method($this->anything());

		$this->contactsManager->expects($this->any())
			->method('search')
			->willReturn([]);

		$share = $this->provider->create($share);

		$share->setPermissions(1);
		$share->setExpirationDate($expirationDate);
		$this->provider->update($share);

		$share = $this->provider->getShareById($share->getId());

		$this->assertEquals(1, $share->getPermissions());
		$this->assertEquals($expirationDate, $share->getExpirationDate());
	}

	public function dataTestUpdate() {
		return [
			['sharedBy', 'shareOwner', new \DateTime('2020-03-01T01:02:03')],
			['shareOwner', 'shareOwner', null],
		];
	}

	public function testGetSharedBy(): void {
		$node = $this->getMockBuilder(File::class)->getMock();
		$node->method('getId')->willReturn(42);
		$node->method('getName')->willReturn('myFile');

		$this->addressHandler->expects($this->never())->method('splitUserRemote');

		$this->addressHandler->method('generateRemoteURL')
			->willReturn('remoteurl.com');

		$this->tokenHandler->method('generateToken')->willReturn('token');
		$this->notifications
			->method('sendRemoteShare')
			->willReturn(true);

		$this->rootFolder->expects($this->never())->method($this->anything());

		$this->contactsManager->expects($this->any())
			->method('search')
			->willReturn([]);

		$share = $this->shareManager->newShare();
		$share->setSharedWith('user@server.com')
			->setSharedBy('sharedBy')
			->setShareOwner('shareOwner')
			->setPermissions(19)
			->setShareType(IShare::TYPE_REMOTE)
			->setNode($node);
		$this->provider->create($share);

		$share2 = $this->shareManager->newShare();
		$share2->setSharedWith('user2@server.com')
			->setSharedBy('sharedBy2')
			->setShareOwner('shareOwner')
			->setPermissions(19)
			->setShareType(IShare::TYPE_REMOTE)
			->setNode($node);
		$this->provider->create($share2);

		$shares = $this->provider->getSharesBy('sharedBy', IShare::TYPE_REMOTE, null, false, -1, 0);

		$this->assertCount(1, $shares);
		$this->assertEquals('user@server.com', $shares[0]->getSharedWith());
		$this->assertEquals('sharedBy', $shares[0]->getSharedBy());
	}

	public function testGetSharedByWithNode(): void {
		$node = $this->getMockBuilder(File::class)->getMock();
		$node->method('getId')->willReturn(42);
		$node->method('getName')->willReturn('myFile');

		$this->tokenHandler->method('generateToken')->willReturn('token');
		$this->notifications
			->method('sendRemoteShare')
			->willReturn(true);

		$this->rootFolder->expects($this->never())->method($this->anything());

		$this->addressHandler->method('generateRemoteURL')
			->willReturn('remoteurl.com');

		$this->contactsManager->expects($this->any())
			->method('search')
			->willReturn([]);

		$share = $this->shareManager->newShare();
		$share->setSharedWith('user@server.com')
			->setSharedBy('sharedBy')
			->setShareOwner('shareOwner')
			->setPermissions(19)
			->setShareType(IShare::TYPE_REMOTE)
			->setNode($node);
		$this->provider->create($share);

		$node2 = $this->getMockBuilder(File::class)->getMock();
		$node2->method('getId')->willReturn(43);
		$node2->method('getName')->willReturn('myOtherFile');

		$share2 = $this->shareManager->newShare();
		$share2->setSharedWith('user@server.com')
			->setSharedBy('sharedBy')
			->setShareOwner('shareOwner')
			->setPermissions(19)
			->setShareType(IShare::TYPE_REMOTE)
			->setNode($node2);
		$this->provider->create($share2);

		$shares = $this->provider->getSharesBy('sharedBy', IShare::TYPE_REMOTE, $node2, false, -1, 0);

		$this->assertCount(1, $shares);
		$this->assertEquals(43, $shares[0]->getNodeId());
	}

	public function testGetSharedByWithReshares(): void {
		$node = $this->getMockBuilder(File::class)->getMock();
		$node->method('getId')->willReturn(42);
		$node->method('getName')->willReturn('myFile');

		$this->tokenHandler->method('generateToken')->willReturn('token');
		$this->notifications
			->method('sendRemoteShare')
			->willReturn(true);

		$this->rootFolder->expects($this->never())->method($this->anything());

		$this->addressHandler->method('generateRemoteURL')
			->willReturn('remoteurl.com');

		$this->contactsManager->expects($this->any())
			->method('search')
			->willReturn([]);

		$share = $this->shareManager->newShare();
		$share->setSharedWith('user@server.com')
			->setSharedBy('shareOwner')
			->setShareOwner('shareOwner')
			->setPermissions(19)
			->setShareType(IShare::TYPE_REMOTE)
			->setNode($node);
		$this->provider->create($share);

		$share2 = $this->shareManager->newShare();
		$share2->setSharedWith('user2@server.com')
			->setSharedBy('sharedBy')
			->setShareOwner('shareOwner')
			->setPermissions(19)
			->setShareType(IShare::TYPE_REMOTE)
			->setNode($node);
		$this->provider->create($share2);

		$shares = $this->provider->getSharesBy('shareOwner', IShare::TYPE_REMOTE, null, true, -1, 0);

		$this->assertCount(2, $shares);
	}

	public function testGetSharedByWithLimit(): void {
		$node = $this->getMockBuilder(File::class)->getMock();
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

		$this->addressHandler->method('generateRemoteURL')
			->willReturn('remoteurl.com');

		$this->contactsManager->expects($this->any())
			->method('search')
			->willReturn([]);

		$share = $this->shareManager->newShare();
		$share->setSharedWith('user@server.com')
			->setSharedBy('sharedBy')
			->setShareOwner('shareOwner')
			->setPermissions(19)
			->setShareType(IShare::TYPE_REMOTE)
			->setNode($node);
		$this->provider->create($share);

		$share2 = $this->shareManager->newShare();
		$share2->setSharedWith('user2@server.com')
			->setSharedBy('sharedBy')
			->setShareOwner('shareOwner')
			->setPermissions(19)
			->setShareType(IShare::TYPE_REMOTE)
			->setNode($node);
		$this->provider->create($share2);

		$shares = $this->provider->getSharesBy('shareOwner', IShare::TYPE_REMOTE, null, true, 1, 1);

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
	public function testDeleteUser($owner, $initiator, $recipient, $deletedUser, $rowDeleted): void {
		$qb = $this->connection->getQueryBuilder();
		$qb->insert('share')
			->setValue('share_type', $qb->createNamedParameter(IShare::TYPE_REMOTE))
			->setValue('uid_owner', $qb->createNamedParameter($owner))
			->setValue('uid_initiator', $qb->createNamedParameter($initiator))
			->setValue('share_with', $qb->createNamedParameter($recipient))
			->setValue('item_type', $qb->createNamedParameter('file'))
			->setValue('item_source', $qb->createNamedParameter(42))
			->setValue('file_source', $qb->createNamedParameter(42))
			->execute();

		$id = $qb->getLastInsertId();

		$this->provider->userDeleted($deletedUser, IShare::TYPE_REMOTE);

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
	 * @dataProvider dataTestIsOutgoingServer2serverShareEnabled
	 *
	 * @param string $isEnabled
	 * @param bool $expected
	 */
	public function testIsOutgoingServer2serverShareEnabled($internalOnly, $isEnabled, $expected): void {
		$this->gsConfig->expects($this->once())->method('onlyInternalFederation')
			->willReturn($internalOnly);
		$this->config->expects($this->any())->method('getAppValue')
			->with('files_sharing', 'outgoing_server2server_share_enabled', 'yes')
			->willReturn($isEnabled);

		$this->assertSame($expected,
			$this->provider->isOutgoingServer2serverShareEnabled()
		);
	}

	public function dataTestIsOutgoingServer2serverShareEnabled() {
		return [
			[false, 'yes', true],
			[false, 'no', false],
			[true, 'yes', false],
			[true, 'no', false],
		];
	}

	/**
	 * @dataProvider dataTestIsIncomingServer2serverShareEnabled
	 *
	 * @param string $isEnabled
	 * @param bool $expected
	 */
	public function testIsIncomingServer2serverShareEnabled($onlyInternal, $isEnabled, $expected): void {
		$this->gsConfig->expects($this->once())->method('onlyInternalFederation')
			->willReturn($onlyInternal);
		$this->config->expects($this->any())->method('getAppValue')
			->with('files_sharing', 'incoming_server2server_share_enabled', 'yes')
			->willReturn($isEnabled);

		$this->assertSame($expected,
			$this->provider->isIncomingServer2serverShareEnabled()
		);
	}

	public function dataTestIsIncomingServer2serverShareEnabled() {
		return [
			[false, 'yes', true],
			[false, 'no', false],
			[true, 'yes', false],
			[true, 'no', false],
		];
	}

	/**
	 * @dataProvider dataTestIsLookupServerQueriesEnabled
	 *
	 * @param string $isEnabled
	 * @param bool $expected
	 */
	public function testIsLookupServerQueriesEnabled($gsEnabled, $isEnabled, $expected): void {
		$this->gsConfig->expects($this->once())->method('isGlobalScaleEnabled')
			->willReturn($gsEnabled);
		$this->config->expects($this->any())->method('getAppValue')
			->with('files_sharing', 'lookupServerEnabled', 'yes')
			->willReturn($isEnabled);

		$this->assertSame($expected,
			$this->provider->isLookupServerQueriesEnabled()
		);
	}


	public function dataTestIsLookupServerQueriesEnabled() {
		return [
			[false, 'yes', true],
			[false, 'no', false],
			[true, 'yes', true],
			[true, 'no', true],
		];
	}

	/**
	 * @dataProvider dataTestIsLookupServerUploadEnabled
	 *
	 * @param string $isEnabled
	 * @param bool $expected
	 */
	public function testIsLookupServerUploadEnabled($gsEnabled, $isEnabled, $expected): void {
		$this->gsConfig->expects($this->once())->method('isGlobalScaleEnabled')
			->willReturn($gsEnabled);
		$this->config->expects($this->any())->method('getAppValue')
			->with('files_sharing', 'lookupServerUploadEnabled', 'yes')
			->willReturn($isEnabled);

		$this->assertSame($expected,
			$this->provider->isLookupServerUploadEnabled()
		);
	}

	public function dataTestIsLookupServerUploadEnabled() {
		return [
			[false, 'yes', true],
			[false, 'no', false],
			[true, 'yes', false],
			[true, 'no', false],
		];
	}

	public function testGetSharesInFolder(): void {
		$userManager = \OC::$server->getUserManager();
		$rootFolder = \OC::$server->getRootFolder();

		$u1 = $userManager->createUser('testFed', md5(time()));
		$u2 = $userManager->createUser('testFed2', md5(time()));

		$folder1 = $rootFolder->getUserFolder($u1->getUID())->newFolder('foo');
		$file1 = $folder1->newFile('bar1');
		$file2 = $folder1->newFile('bar2');

		$this->tokenHandler->method('generateToken')->willReturn('token');
		$this->notifications
			->method('sendRemoteShare')
			->willReturn(true);

		$this->addressHandler->method('generateRemoteURL')
			->willReturn('remoteurl.com');

		$this->contactsManager->expects($this->any())
			->method('search')
			->willReturn([]);

		$share1 = $this->shareManager->newShare();
		$share1->setSharedWith('user@server.com')
			->setSharedBy($u1->getUID())
			->setShareOwner($u1->getUID())
			->setPermissions(Constants::PERMISSION_READ)
			->setShareType(IShare::TYPE_REMOTE)
			->setNode($file1);
		$this->provider->create($share1);

		$share2 = $this->shareManager->newShare();
		$share2->setSharedWith('user@server.com')
			->setSharedBy($u2->getUID())
			->setShareOwner($u1->getUID())
			->setPermissions(Constants::PERMISSION_READ)
			->setShareType(IShare::TYPE_REMOTE)
			->setNode($file2);
		$this->provider->create($share2);

		$result = $this->provider->getSharesInFolder($u1->getUID(), $folder1, false);
		$this->assertCount(1, $result);
		$this->assertCount(1, $result[$file1->getId()]);

		$result = $this->provider->getSharesInFolder($u1->getUID(), $folder1, true);
		$this->assertCount(2, $result);
		$this->assertCount(1, $result[$file1->getId()]);
		$this->assertCount(1, $result[$file2->getId()]);

		$u1->delete();
		$u2->delete();
	}

	public function testGetAccessList(): void {
		$userManager = \OC::$server->getUserManager();
		$rootFolder = \OC::$server->getRootFolder();

		$u1 = $userManager->createUser('testFed', md5(time()));

		$folder1 = $rootFolder->getUserFolder($u1->getUID())->newFolder('foo');
		$file1 = $folder1->newFile('bar1');

		$this->tokenHandler->expects($this->exactly(2))
			->method('generateToken')
			->willReturnOnConsecutiveCalls('token1', 'token2');
		$this->notifications->expects($this->atLeastOnce())
			->method('sendRemoteShare')
			->willReturn(true);

		$this->contactsManager->expects($this->any())
			->method('search')
			->willReturn([]);

		$result = $this->provider->getAccessList([$file1], true);
		$this->assertEquals(['remote' => []], $result);

		$result = $this->provider->getAccessList([$file1], false);
		$this->assertEquals(['remote' => false], $result);

		$this->addressHandler->method('generateRemoteURL')
			->willReturn('remoteurl.com');

		$share1 = $this->shareManager->newShare();
		$share1->setSharedWith('user@server.com')
			->setSharedBy($u1->getUID())
			->setShareOwner($u1->getUID())
			->setPermissions(Constants::PERMISSION_READ)
			->setShareType(IShare::TYPE_REMOTE)
			->setNode($file1);
		$this->provider->create($share1);

		$share2 = $this->shareManager->newShare();
		$share2->setSharedWith('foobar@localhost')
			->setSharedBy($u1->getUID())
			->setShareOwner($u1->getUID())
			->setPermissions(Constants::PERMISSION_READ)
			->setShareType(IShare::TYPE_REMOTE)
			->setNode($file1);
		$this->provider->create($share2);

		$result = $this->provider->getAccessList([$file1], true);
		$this->assertEquals(['remote' => [
			'user@server.com' => [
				'token' => 'token1',
				'node_id' => $file1->getId(),
			],
			'foobar@localhost' => [
				'token' => 'token2',
				'node_id' => $file1->getId(),
			],
		]], $result);

		$result = $this->provider->getAccessList([$file1], false);
		$this->assertEquals(['remote' => true], $result);

		$u1->delete();
	}
}
