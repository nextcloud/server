<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Sharing\Tests\External;

use OC\Federation\CloudIdManager;
use OC\Files\Mount\MountPoint;
use OC\Files\SetupManager;
use OC\Files\SetupManagerFactory;
use OC\Files\Storage\StorageFactory;
use OC\Files\Storage\Temporary;
use OCA\Files_Sharing\External\ExternalShare;
use OCA\Files_Sharing\External\ExternalShareMapper;
use OCA\Files_Sharing\External\Manager;
use OCA\Files_Sharing\External\MountProvider;
use OCA\Files_Sharing\Tests\TestCase;
use OCP\Contacts\IManager;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Federation\ICloudFederationFactory;
use OCP\Federation\ICloudFederationProviderManager;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;
use OCP\ICacheFactory;
use OCP\ICertificateManager;
use OCP\IDBConnection;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\OCS\IDiscoveryService;
use OCP\Server;
use OCP\Share\IShare;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\Traits\UserTrait;

/**
 * Class ManagerTest
 *
 *
 * @package OCA\Files_Sharing\Tests\External
 */
#[\PHPUnit\Framework\Attributes\Group(name: 'DB')]
class ManagerTest extends TestCase {
	use UserTrait;

	protected IUser $user;
	protected IGroup&MockObject $group1;
	protected IGroup&MockObject $group2;
	protected MountProvider $testMountProvider;
	protected IEventDispatcher&MockObject $eventDispatcher;
	protected LoggerInterface&MockObject $logger;
	protected \OC\Files\Mount\Manager $mountManager;
	protected IManager&MockObject $contactsManager;
	protected Manager&MockObject $manager;
	protected IClientService&MockObject $clientService;
	protected ICloudFederationProviderManager&MockObject $cloudFederationProviderManager;
	protected ICloudFederationFactory&MockObject $cloudFederationFactory;
	protected IGroupManager&MockObject $groupManager;
	protected IUserManager&MockObject $userManager;
	protected SetupManager&MockObject $setupManager;
	protected ICertificateManager&MockObject $certificateManager;
	private ExternalShareMapper $externalShareMapper;

	protected function setUp(): void {
		parent::setUp();

		$this->user = $this->createUser($this->getUniqueID('user'), '');
		$this->mountManager = new \OC\Files\Mount\Manager($this->createMock(SetupManagerFactory::class));
		$this->clientService = $this->getMockBuilder(IClientService::class)
			->disableOriginalConstructor()->getMock();
		$this->cloudFederationProviderManager = $this->createMock(ICloudFederationProviderManager::class);
		$this->cloudFederationFactory = $this->createMock(ICloudFederationFactory::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->eventDispatcher = $this->createMock(IEventDispatcher::class);
		$this->setupManager = $this->createMock(SetupManager::class);
		$this->rootFolder = $this->createMock(IRootFolder::class);
		$this->rootFolder->method('getUserFolder')
			->willReturnCallback(function (string $userId): Folder {
				$folder = $this->createMock(Folder::class);
				$folder->method('get')
					->willReturn($folder);
				$folder->method('getNonExistingName')
					->willReturnCallback(fn (string $name): string => $name);
				return $folder;
			});

		$this->externalShareMapper = new ExternalShareMapper(Server::get(IDBConnection::class), $this->groupManager);

		$this->contactsManager = $this->createMock(IManager::class);
		// needed for MountProvider() initialization
		$this->contactsManager->expects($this->any())
			->method('search')
			->willReturn([]);

		$this->certificateManager = $this->createMock(ICertificateManager::class);

		$this->logger = $this->createMock(LoggerInterface::class);
		$this->logger->expects($this->never())->method('emergency');

		$this->manager = $this->createManagerForUser($this->user);

		$this->testMountProvider = new MountProvider(Server::get(IDBConnection::class), function () {
			return $this->manager;
		}, new CloudIdManager(
			$this->createMock(ICacheFactory::class),
			$this->createMock(IEventDispatcher::class),
			$this->contactsManager,
			$this->createMock(IURLGenerator::class),
			$this->userManager,
		));

		$this->group1 = $this->createMock(IGroup::class);
		$this->group1->expects($this->any())->method('getGID')->willReturn('group1');
		$this->group1->expects($this->any())->method('inGroup')->with($this->user)->willReturn(true);

		$this->group2 = $this->createMock(IGroup::class);
		$this->group2->expects($this->any())->method('getGID')->willReturn('group2');
		$this->group2->expects($this->any())->method('inGroup')->with($this->user)->willReturn(true);

		$this->userManager->expects($this->any())->method('get')->willReturn($this->user);
		$this->groupManager->expects($this->any())->method(('getUserGroups'))->willReturn([$this->group1, $this->group2]);
		$this->groupManager->expects($this->any())->method(('get'))
			->willReturnMap([
				['group1', $this->group1],
				['group2', $this->group2],
			]);
	}

	protected function tearDown(): void {
		// clear the share external table to avoid side effects
		Server::get(IDBConnection::class)->getQueryBuilder()->delete('share_external')->executeStatement();

		parent::tearDown();
	}

	private function createManagerForUser(IUser $user): Manager&MockObject {
		$userSession = $this->createMock(IUserSession::class);
		$userSession->method('getUser')
			->willReturn($user);

		return $this->getMockBuilder(Manager::class)
			->setConstructorArgs(
				[
					Server::get(IDBConnection::class),
					$this->mountManager,
					new StorageFactory(),
					$this->clientService,
					Server::get(\OCP\Notification\IManager::class),
					Server::get(IDiscoveryService::class),
					$this->cloudFederationProviderManager,
					$this->cloudFederationFactory,
					$this->groupManager,
					$userSession,
					$this->eventDispatcher,
					$this->logger,
					$this->rootFolder,
					$this->setupManager,
					$this->certificateManager,
					$this->externalShareMapper,
				]
			)->onlyMethods(['tryOCMEndPoint'])->getMock();
	}

	private function setupMounts(): void {
		$this->clearMounts();
		$mounts = $this->testMountProvider->getMountsForUser($this->user, new StorageFactory());
		foreach ($mounts as $mount) {
			$this->mountManager->addMount($mount);
		}
	}

	private function clearMounts(): void {
		$this->mountManager->clear();
		$this->mountManager->addMount(new MountPoint(Temporary::class, '', []));
	}

	public function testAddUserShare(): void {
		$userShare = new ExternalShare();
		$userShare->generateId();
		$userShare->setRemote('http://localhost');
		$userShare->setShareToken('token1');
		$userShare->setPassword('');
		$userShare->setName('/SharedFolder');
		$userShare->setOwner('foobar');
		$userShare->setShareType(IShare::TYPE_USER);
		$userShare->setAccepted(IShare::STATUS_PENDING);
		$userShare->setRemoteId('2342');

		$this->doTestAddShare($userShare, $this->user);
	}

	public function testAddGroupShare(): void {
		$groupShare = new ExternalShare();
		$groupShare->generateId();
		$groupShare->setRemote('http://localhost');
		$groupShare->setOwner('foobar');
		$groupShare->setShareType(IShare::TYPE_GROUP);
		$groupShare->setAccepted(IShare::STATUS_PENDING);
		$groupShare->setRemoteId('2342');
		$groupShare->setShareToken('token1');
		$groupShare->setPassword('');
		$groupShare->setName('/SharedFolder');
		$this->doTestAddShare($groupShare, $this->group1, isGroup: true);
	}

	public function doTestAddShare(ExternalShare $shareData1, IUser|IGroup $userOrGroup, bool $isGroup = false): void {
		if ($isGroup) {
			$this->manager->expects($this->never())->method('tryOCMEndPoint')->willReturn(false);
		} else {
			$this->manager->expects(self::atLeast(2))
				->method('tryOCMEndPoint')
				->willReturnMap([
					['http://localhost', 'token1', '2342', 'accept', false],
					['http://localhost', 'token3', '2342', 'decline', false],
				]);
		}

		// Add a share for "user"
		$this->assertSame(null, call_user_func_array([$this->manager, 'addShare'], [$shareData1, $userOrGroup]));
		$openShares = $this->manager->getOpenShares();
		$this->assertCount(1, $openShares);
		$this->assertExternalShareEntry($shareData1, $openShares[0], 1, '{{TemporaryMountPointName#' . $shareData1->getName() . '}}', $userOrGroup);

		$shareData2 = $shareData1->clone();
		$shareData2->setShareToken('token2');
		$shareData2->generateId();
		$shareData3 = $shareData1->clone();
		$shareData3->setShareToken('token3');
		$shareData3->generateId();

		$this->setupMounts();
		$this->assertNotMount('SharedFolder');
		$this->assertNotMount('{{TemporaryMountPointName#' . $shareData1->getName() . '}}');

		// Add a second share for "user" with the same name
		$this->assertSame(null, call_user_func_array([$this->manager, 'addShare'], [$shareData2, $userOrGroup]));
		$openShares = $this->manager->getOpenShares();
		$this->assertCount(2, $openShares);
		$this->assertExternalShareEntry($shareData1, $openShares[0], 1, '{{TemporaryMountPointName#' . $shareData1->getName() . '}}', $userOrGroup);
		// New share falls back to "-1" appendix, because the name is already taken
		$this->assertExternalShareEntry($shareData2, $openShares[1], 2, '{{TemporaryMountPointName#' . $shareData2->getName() . '}}-1', $userOrGroup);

		$this->setupMounts();
		$this->assertNotMount('SharedFolder');
		$this->assertNotMount('{{TemporaryMountPointName#' . $shareData1->getName() . '}}');
		$this->assertNotMount('{{TemporaryMountPointName#' . $shareData1->getName() . '}}-1');

		$newClientCalls = [];
		$this->clientService
			->method('newClient')
			->willReturnCallback(function () use (&$newClientCalls): IClient {
				if (!empty($newClientCalls)) {
					return array_shift($newClientCalls);
				}
				return $this->createMock(IClient::class);
			});
		if (!$isGroup) {
			$client = $this->createMock(IClient::class);
			$newClientCalls[] = $client;
			$response = $this->createMock(IResponse::class);
			$response->method('getBody')
				->willReturn(json_encode([
					'ocs' => [
						'meta' => [
							'statuscode' => 200,
						]
					]
				]));
			$client->expects($this->once())
				->method('post')
				->with($this->stringStartsWith('http://localhost/ocs/v2.php/cloud/shares/' . $openShares[0]->getRemoteId()), $this->anything())
				->willReturn($response);
		}

		// Accept the first share
		$this->assertTrue($this->manager->acceptShare($openShares[0]));

		// Check remaining shares - Accepted
		$acceptedShares = $this->externalShareMapper->getShares($this->user, IShare::STATUS_ACCEPTED);
		$this->assertCount(1, $acceptedShares);
		$shareData1->setAccepted(true);
		$this->assertExternalShareEntry($shareData1, $acceptedShares[0], 1, $shareData1->getName(), $this->user);
		// Check remaining shares - Open
		$openShares = $this->manager->getOpenShares();
		$this->assertCount(1, $openShares);
		$this->assertExternalShareEntry($shareData2, $openShares[0], 2, '{{TemporaryMountPointName#' . $shareData2->getName() . '}}-1', $userOrGroup);

		$this->setupMounts();
		$this->assertMount($shareData1->getName());
		$this->assertNotMount('{{TemporaryMountPointName#' . $shareData1->getName() . '}}');
		$this->assertNotMount('{{TemporaryMountPointName#' . $shareData1->getName() . '}}-1');

		// Add another share for "user" with the same name
		$this->assertSame(null, call_user_func_array([$this->manager, 'addShare'], [$shareData3, $userOrGroup]));
		$openShares = $this->manager->getOpenShares();
		$this->assertCount(2, $openShares);
		$this->assertExternalShareEntry($shareData2, $openShares[0], 2, '{{TemporaryMountPointName#' . $shareData2->getName() . '}}-1', $userOrGroup);
		if (!$isGroup) {
			// New share falls back to the original name (no "-\d", because the name is not taken)
			$this->assertExternalShareEntry($shareData3, $openShares[1], 3, '{{TemporaryMountPointName#' . $shareData3->getName() . '}}', $userOrGroup);
		} else {
			$this->assertExternalShareEntry($shareData3, $openShares[1], 3, '{{TemporaryMountPointName#' . $shareData3->getName() . '}}-2', $userOrGroup);
		}

		$this->setupMounts();
		$this->assertMount($shareData1->getName());
		$this->assertNotMount('{{TemporaryMountPointName#' . $shareData1->getName() . '}}');
		$this->assertNotMount('{{TemporaryMountPointName#' . $shareData1->getName() . '}}-1');

		if (!$isGroup) {
			$client = $this->createMock(IClient::class);
			$newClientCalls[] = $client;
			$response = $this->createMock(IResponse::class);
			$response->method('getBody')
				->willReturn(json_encode([
					'ocs' => [
						'meta' => [
							'statuscode' => 200,
						]
					]
				]));
			$client->expects($this->once())
				->method('post')
				->with($this->stringStartsWith('http://localhost/ocs/v2.php/cloud/shares/' . $openShares[1]->getRemoteId() . '/decline'), $this->anything())
				->willReturn($response);
		}

		// Decline the third share
		$this->assertTrue($this->manager->declineShare($openShares[1]));

		$this->setupMounts();
		$this->assertMount($shareData1->getName());
		$this->assertNotMount('{{TemporaryMountPointName#' . $shareData1->getName() . '}}');
		$this->assertNotMount('{{TemporaryMountPointName#' . $shareData1->getName() . '}}-1');

		// Check remaining shares - Accepted
		$acceptedShares = $this->externalShareMapper->getShares($this->user, IShare::STATUS_ACCEPTED);
		$this->assertCount(1, $acceptedShares);
		$shareData1->setAccepted(true);
		$this->assertExternalShareEntry($shareData1, $acceptedShares[0], 1, $shareData1->getName(), $this->user);
		// Check remaining shares - Open
		$openShares = $this->manager->getOpenShares();
		if ($isGroup) {
			// declining a group share adds it back to pending instead of deleting it
			$this->assertCount(2, $openShares);
			// this is a group share that is still open
			$this->assertExternalShareEntry($shareData2, $openShares[0], 2, '{{TemporaryMountPointName#' . $shareData2->getName() . '}}-1', $userOrGroup);
			// this is the user share sub-entry matching the group share which got declined
			$this->assertExternalShareEntry($shareData3, $openShares[1], 2, '{{TemporaryMountPointName#' . $shareData3->getName() . '}}-2', $this->user);
		} else {
			$this->assertCount(1, $openShares);
			$this->assertExternalShareEntry($shareData2, $openShares[0], 2, '{{TemporaryMountPointName#' . $shareData2->getName() . '}}-1', $this->user);
		}

		$this->setupMounts();
		$this->assertMount($shareData1->getName());
		$this->assertNotMount('{{TemporaryMountPointName#' . $shareData1->getName() . '}}');
		$this->assertNotMount('{{TemporaryMountPointName#' . $shareData1->getName() . '}}-1');

		if ($isGroup) {
			// no http requests here
			$this->manager->removeGroupShares($this->group1);
		} else {
			$client1 = $this->createMock(IClient::class);
			$client2 = $this->createMock(IClient::class);
			$newClientCalls[] = $client1;
			$newClientCalls[] = $client2;
			$response = $this->createMock(IResponse::class);
			$response->method('getBody')
				->willReturn(json_encode([
					'ocs' => [
						'meta' => [
							'statuscode' => 200,
						]
					]
				]));

			$client1->expects($this->once())
				->method('post')
				->with($this->stringStartsWith('http://localhost/ocs/v2.php/cloud/shares/' . $openShares[0]->getRemoteId() . '/decline'), $this->anything())
				->willReturn($response);
			$client2->expects($this->once())
				->method('post')
				->with($this->stringStartsWith('http://localhost/ocs/v2.php/cloud/shares/' . $acceptedShares[0]->getRemoteId() . '/decline'), $this->anything())
				->willReturn($response);

			$this->manager->removeUserShares($this->user);
		}

		$this->assertEmpty($this->externalShareMapper->getShares($this->user, null), 'Asserting all shares for the user have been deleted');

		$this->clearMounts();
		self::invokePrivate($this->manager, 'setupMounts');
		$this->assertNotMount($shareData1->getName());
		$this->assertNotMount('{{TemporaryMountPointName#' . $shareData1->getName() . '}}');
		$this->assertNotMount('{{TemporaryMountPointName#' . $shareData1->getName() . '}}-1');
	}

	private function verifyAcceptedGroupShare(ExternalShare $share): void {
		$openShares = $this->manager->getOpenShares();
		$this->assertCount(0, $openShares);
		$acceptedShares = $this->externalShareMapper->getShares($this->user, IShare::STATUS_ACCEPTED);
		$this->assertCount(1, $acceptedShares);
		$share->setAccepted(IShare::STATUS_ACCEPTED);
		$this->assertExternalShareEntry($share, $acceptedShares[0], 0, $share->getName(), $this->user);
		$this->setupMounts();
		$this->assertMount($share->getName());
	}

	private function verifyDeclinedGroupShare(ExternalShare $share, ?string $tempMount = null): void {
		if ($tempMount === null) {
			$tempMount = '{{TemporaryMountPointName#/SharedFolder}}';
		}
		$openShares = $this->manager->getOpenShares();
		$this->assertCount(1, $openShares);
		$acceptedShares = $this->externalShareMapper->getShares($this->user, IShare::STATUS_ACCEPTED);
		$this->assertCount(0, $acceptedShares);
		$share->setAccepted(IShare::STATUS_PENDING);
		$this->assertExternalShareEntry($share, $openShares[0], 0, $tempMount, $this->user);
		$this->setupMounts();
		$this->assertNotMount($share->getName());
		$this->assertNotMount($tempMount);
	}

	private function createTestUserShare(string $userId = 'user1'): ExternalShare {
		$user = $this->createMock(IUser::class);
		$user->expects($this->any())->method('getUID')->willReturn($userId);
		$share = new ExternalShare();
		$share->generateId();
		$share->setRemote('http://localhost');
		$share->setShareToken('token1');
		$share->setPassword('');
		$share->setName('/SharedFolder');
		$share->setOwner('foobar');
		$share->setShareType(IShare::TYPE_USER);
		$share->setAccepted(IShare::STATUS_PENDING);
		$share->setRemoteId('2346');

		$this->assertSame(null, call_user_func_array([$this->manager, 'addShare'], [$share, $user]));

		return $share;
	}

	/**
	 * @return array{0: ExternalShare, 1: ExternalShare}
	 */
	private function createTestGroupShare(string $groupId = 'group1'): array {
		$share = new ExternalShare();
		$share->generateId();
		$share->setRemote('http://localhost');
		$share->setShareToken('token1');
		$share->setPassword('');
		$share->setName('/SharedFolder');
		$share->setOwner('foobar');
		$share->setShareType(IShare::TYPE_GROUP);
		$share->setAccepted(IShare::STATUS_PENDING);
		$share->setRemoteId('2342');

		$this->assertSame(null, call_user_func_array([$this->manager, 'addShare'], [$share, $groupId === 'group1' ? $this->group1 : $this->group2]));

		$allShares = $this->externalShareMapper->getShares($this->user, null);
		$groupShare = null;
		foreach ($allShares as $share) {
			if ($share->getUser() === $groupId) {
				// this will hold the main group entry
				$groupShare = $share;
				break;
			}
		}

		$this->assertEquals($share->getId(), $groupShare->getId());

		return [$share, $groupShare];
	}

	public function testAcceptOriginalGroupShare(): void {
		[$shareData, $groupShare] = $this->createTestGroupShare();
		$this->assertTrue($this->manager->acceptShare($groupShare));
		$this->verifyAcceptedGroupShare($shareData);

		// a second time
		$this->assertTrue($this->manager->acceptShare($groupShare));
		$this->verifyAcceptedGroupShare($shareData);
	}

	public function testAcceptGroupShareAgainThroughGroupShare(): void {
		[$shareData, $groupShare] = $this->createTestGroupShare();
		$this->assertTrue($this->manager->acceptShare($groupShare));
		$this->verifyAcceptedGroupShare($shareData);

		// decline again, this keeps the sub-share
		$this->assertTrue($this->manager->declineShare($groupShare));
		$this->verifyDeclinedGroupShare($shareData, '/SharedFolder');

		// this will return sub-entries
		$openShares = $this->manager->getOpenShares();
		$this->assertCount(1, $openShares);

		// accept through group share
		$this->assertTrue($this->manager->acceptShare($groupShare));
		$this->verifyAcceptedGroupShare($shareData, '/SharedFolder');

		// accept a second time
		$this->assertTrue($this->manager->acceptShare($groupShare));
		$this->verifyAcceptedGroupShare($shareData, '/SharedFolder');
	}

	public function testAcceptGroupShareAgainThroughSubShare(): void {
		[$shareData, $groupShare] = $this->createTestGroupShare();
		$this->assertTrue($this->manager->acceptShare($groupShare));
		$this->verifyAcceptedGroupShare($shareData);

		// decline again, this keeps the sub-share
		$this->assertTrue($this->manager->declineShare($groupShare));
		$this->verifyDeclinedGroupShare($shareData, '/SharedFolder');

		// this will return sub-entries
		$openShares = $this->manager->getOpenShares();
		$this->assertCount(1, $openShares);

		// accept through sub-share
		$this->assertTrue($this->manager->acceptShare($openShares[0]));
		$this->verifyAcceptedGroupShare($shareData);

		// accept a second time
		$this->assertTrue($this->manager->acceptShare($openShares[0]));
		$this->verifyAcceptedGroupShare($shareData);
	}

	public function testDeclineOriginalGroupShare(): void {
		[$shareData, $groupShare] = $this->createTestGroupShare();
		$this->assertTrue($this->manager->declineShare($groupShare));
		$this->verifyDeclinedGroupShare($shareData);

		// a second time
		$this->assertTrue($this->manager->declineShare($groupShare));
		$this->verifyDeclinedGroupShare($shareData);
	}

	public function testDeclineGroupShareAgainThroughGroupShare(): void {
		[$shareData, $groupShare] = $this->createTestGroupShare();
		$this->assertTrue($this->manager->acceptShare($groupShare));
		$this->verifyAcceptedGroupShare($shareData);

		// decline again, this keeps the sub-share
		$this->assertTrue($this->manager->declineShare($groupShare));
		$this->verifyDeclinedGroupShare($shareData, '/SharedFolder');

		// a second time
		$this->assertTrue($this->manager->declineShare($groupShare));
		$this->verifyDeclinedGroupShare($shareData, '/SharedFolder');
	}

	public function testDeclineGroupShareAgainThroughSubshare(): void {
		[$shareData, $groupShare] = $this->createTestGroupShare();
		$this->assertTrue($this->manager->acceptShare($groupShare));
		$this->verifyAcceptedGroupShare($shareData);

		// this will return sub-entries
		$allShares = $this->externalShareMapper->getShares($this->user, null);
		$this->assertCount(1, $allShares);

		// decline again through sub-share
		$this->assertTrue($this->manager->declineShare($allShares[0]));
		$this->verifyDeclinedGroupShare($shareData, '/SharedFolder');

		// a second time
		$this->assertTrue($this->manager->declineShare($allShares[0]));
		$this->verifyDeclinedGroupShare($shareData, '/SharedFolder');
	}

	public function testDeclineGroupShareAgainThroughMountPoint(): void {
		[$shareData, $groupShare] = $this->createTestGroupShare();
		$this->assertTrue($this->manager->acceptShare($groupShare));
		$this->verifyAcceptedGroupShare($shareData);

		// decline through mount point name
		$this->assertTrue($this->manager->removeShare($this->user->getUID() . '/files/' . $shareData->getName()));
		$this->verifyDeclinedGroupShare($shareData, '/SharedFolder');

		// second time must fail as the mount point is gone
		$this->assertFalse($this->manager->removeShare($this->user->getUID() . '/files/' . $shareData->getName()));
	}

	public function testDeclineThenAcceptGroupShareAgainThroughGroupShare(): void {
		[$shareData, $groupShare] = $this->createTestGroupShare();
		// decline, this creates a declined sub-share
		$this->assertTrue($this->manager->declineShare($groupShare));
		$this->verifyDeclinedGroupShare($shareData);

		// accept through sub-share
		$this->assertTrue($this->manager->acceptShare($groupShare));
		$this->verifyAcceptedGroupShare($shareData, '/SharedFolder');

		// accept a second time
		$this->assertTrue($this->manager->acceptShare($groupShare));
		$this->verifyAcceptedGroupShare($shareData, '/SharedFolder');
	}

	public function testDeclineThenAcceptGroupShareAgainThroughSubShare(): void {
		[$shareData, $groupShare] = $this->createTestGroupShare();
		// decline, this creates a declined sub-share
		$this->assertTrue($this->manager->declineShare($groupShare));
		$this->verifyDeclinedGroupShare($shareData);

		// this will return sub-entries
		$openShares = $this->manager->getOpenShares();

		// accept through sub-share
		$this->assertTrue($this->manager->acceptShare($openShares[0]));
		$this->verifyAcceptedGroupShare($shareData);

		// accept a second time
		$this->assertTrue($this->manager->acceptShare($openShares[0]));
		$this->verifyAcceptedGroupShare($shareData);
	}

	public function testDeleteUserShares(): void {
		// user 1 shares

		$userShare = $this->createTestUserShare($this->user->getUID());

		[$shareData, $groupShare] = $this->createTestGroupShare();

		$shares = $this->manager->getOpenShares();
		$this->assertCount(2, $shares);

		$this->assertTrue($this->manager->acceptShare($groupShare));
		$user2 = $this->createMock(IUser::class);
		$user2->method('getUID')->willReturn('user2');

		// user 2 shares
		$manager2 = $this->createManagerForUser($user2);
		$share = new ExternalShare();
		$share->generateId();
		$share->setRemote('http://localhost');
		$share->setShareToken('token1');
		$share->setPassword('');
		$share->setName('/SharedFolder');
		$share->setOwner('foobar');
		$share->setShareType(IShare::TYPE_USER);
		$share->setAccepted(IShare::STATUS_PENDING);
		$share->setRemoteId('2342');

		$this->assertCount(1, $manager2->getOpenShares());
		$this->assertSame(null, call_user_func_array([$manager2, 'addShare'], [$share, $user2]));
		$this->assertCount(2, $manager2->getOpenShares());

		$userShare = $this->externalShareMapper->getById($userShare->getId()); // Simpler to compare

		$this->manager->expects($this->once())->method('tryOCMEndPoint')->with($userShare, 'decline')->willReturn([]);
		$this->manager->removeUserShares($this->user);

		$user1Shares = $this->manager->getOpenShares();
		// user share is gone, group is still there
		$this->assertCount(1, $user1Shares);
		$this->assertEquals($user1Shares[0]->getShareType(), IShare::TYPE_GROUP);

		// user 2 shares untouched
		$user2Shares = $manager2->getOpenShares();
		$this->assertCount(2, $user2Shares);
		$this->assertEquals($user2Shares[0]->getShareType(), IShare::TYPE_GROUP);
		$this->assertEquals($user2Shares[0]->getUser(), 'group1');
		$this->assertEquals($user2Shares[1]->getShareType(), IShare::TYPE_USER);
		$this->assertEquals($user2Shares[1]->getUser(), 'user2');
	}

	public function testDeleteGroupShares(): void {
		$shareData = $this->createTestUserShare($this->user->getUID());

		[$shareData, $groupShare] = $this->createTestGroupShare();

		$shares = $this->manager->getOpenShares();
		$this->assertCount(2, $shares);

		$this->assertTrue($this->manager->acceptShare($groupShare));

		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('user2');

		// user 2 shares
		$manager2 = $this->createManagerForUser($user);

		$share = new ExternalShare();
		$share->generateId();
		$share->setRemote('http://localhost');
		$share->setShareToken('token1');
		$share->setPassword('');
		$share->setName('/SharedFolder');
		$share->setOwner('foobar');
		$share->setShareType(IShare::TYPE_USER);
		$share->setAccepted(IShare::STATUS_PENDING);
		$share->setRemoteId('2343');

		$this->assertCount(1, $manager2->getOpenShares());
		$this->assertSame(null, call_user_func_array([$manager2, 'addShare'], [$share, $user]));
		$this->assertCount(2, $manager2->getOpenShares());

		$this->manager->expects($this->never())->method('tryOCMEndPoint');
		$this->manager->removeGroupShares($this->group1);

		$user1Shares = $this->manager->getOpenShares();
		// user share is gone, group is still there
		$this->assertCount(1, $user1Shares);
		$this->assertEquals($user1Shares[0]->getShareType(), IShare::TYPE_USER);

		// user 2 shares untouched
		$user2Shares = $manager2->getOpenShares();
		$this->assertCount(1, $user2Shares);
		$this->assertEquals($user2Shares[0]->getShareType(), IShare::TYPE_USER);
		$this->assertEquals($user2Shares[0]->getUser(), 'user2');
	}

	protected function assertExternalShareEntry(ExternalShare $expected, ExternalShare $actual, int $share, string $mountPoint, IUser|IGroup $targetEntity): void {
		$this->assertEquals($expected->getRemote(), $actual->getRemote(), 'Asserting remote of a share #' . $share);
		$this->assertEquals($expected->getShareToken(), $actual->getShareToken(), 'Asserting token of a share #' . $share);
		$this->assertEquals($expected->getName(), $actual->getName(), 'Asserting name of a share #' . $share);
		$this->assertEquals($expected->getOwner(), $actual->getOwner(), 'Asserting owner of a share #' . $share);
		$this->assertEquals($expected->getAccepted(), $actual->getAccepted(), 'Asserting accept of a share #' . $share);
		$this->assertEquals($targetEntity instanceof IGroup ? $targetEntity->getGID() : $targetEntity->getUID(), $actual->getUser(), 'Asserting user of a share #' . $share);
		$this->assertEquals($mountPoint, $actual->getMountpoint(), 'Asserting mountpoint of a share #' . $share);
	}

	private function assertMount(string $mountPoint): void {
		$mountPoint = rtrim($mountPoint, '/');
		$mount = $this->mountManager->find($this->getFullPath($mountPoint));
		$this->assertInstanceOf('\OCA\Files_Sharing\External\Mount', $mount);
		$this->assertInstanceOf('\OCP\Files\Mount\IMountPoint', $mount);
		$this->assertEquals($this->getFullPath($mountPoint), rtrim($mount->getMountPoint(), '/'));
		$storage = $mount->getStorage();
		$this->assertInstanceOf('\OCA\Files_Sharing\External\Storage', $storage);
	}

	private function assertNotMount(string $mountPoint): void {
		$mountPoint = rtrim($mountPoint, '/');
		try {
			$mount = $this->mountManager->find($this->getFullPath($mountPoint));
			$this->assertInstanceOf('\OCP\Files\Mount\IMountPoint', $mount);
			$this->assertNotEquals($this->getFullPath($mountPoint), rtrim($mount->getMountPoint(), '/'));
		} catch (NotFoundException $e) {

		}
	}

	private function getFullPath(string $path): string {
		return '/' . $this->user->getUID() . '/files' . $path;
	}
}
