<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\Files_Sharing\Tests\External;

use OC\Federation\CloudIdManager;
use OC\Files\Mount\MountPoint;
use OC\Files\SetupManagerFactory;
use OC\Files\Storage\StorageFactory;
use OC\Files\Storage\Temporary;
use OCA\Files_Sharing\External\Manager;
use OCA\Files_Sharing\External\MountProvider;
use OCA\Files_Sharing\Tests\TestCase;
use OCP\Contacts\IManager;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Federation\ICloudFederationFactory;
use OCP\Federation\ICloudFederationProviderManager;
use OCP\Files\NotFoundException;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;
use OCP\ICacheFactory;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Share\IShare;
use Psr\Log\LoggerInterface;
use Test\Traits\UserTrait;

/**
 * Class ManagerTest
 *
 * @group DB
 *
 * @package OCA\Files_Sharing\Tests\External
 */
class ManagerTest extends TestCase {
	use UserTrait;

	/** @var IManager|\PHPUnit\Framework\MockObject\MockObject */
	protected $contactsManager;

	/** @var Manager|\PHPUnit\Framework\MockObject\MockObject **/
	private $manager;

	/** @var \OC\Files\Mount\Manager */
	private $mountManager;

	/** @var IClientService|\PHPUnit\Framework\MockObject\MockObject */
	private $clientService;

	/** @var ICloudFederationProviderManager|\PHPUnit\Framework\MockObject\MockObject */
	private $cloudFederationProviderManager;

	/** @var ICloudFederationFactory|\PHPUnit\Framework\MockObject\MockObject */
	private $cloudFederationFactory;

	/** @var \PHPUnit\Framework\MockObject\MockObject|IGroupManager */
	private $groupManager;

	/** @var \PHPUnit\Framework\MockObject\MockObject|IUserManager */
	private $userManager;

	/** @var LoggerInterface */
	private $logger;

	private $uid;

	/**
	 * @var \OCP\IUser
	 */
	private $user;
	private $testMountProvider;
	/** @var IEventDispatcher|\PHPUnit\Framework\MockObject\MockObject */
	private $eventDispatcher;

	protected function setUp(): void {
		parent::setUp();

		$this->uid = $this->getUniqueID('user');
		$this->user = $this->createUser($this->uid, '');
		$this->mountManager = new \OC\Files\Mount\Manager($this->createMock(SetupManagerFactory::class));
		$this->clientService = $this->getMockBuilder(IClientService::class)
			->disableOriginalConstructor()->getMock();
		$this->cloudFederationProviderManager = $this->createMock(ICloudFederationProviderManager::class);
		$this->cloudFederationFactory = $this->createMock(ICloudFederationFactory::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->eventDispatcher = $this->createMock(IEventDispatcher::class);

		$this->contactsManager = $this->createMock(IManager::class);
		// needed for MountProvider() initialization
		$this->contactsManager->expects($this->any())
			->method('search')
			->willReturn([]);

		$this->logger = $this->createMock(LoggerInterface::class);
		$this->logger->expects($this->never())->method('emergency');

		$this->manager = $this->createManagerForUser($this->uid);

		$this->testMountProvider = new MountProvider(\OC::$server->getDatabaseConnection(), function () {
			return $this->manager;
		}, new CloudIdManager(
			$this->contactsManager,
			$this->createMock(IURLGenerator::class),
			$this->userManager,
			$this->createMock(ICacheFactory::class),
			$this->createMock(IEventDispatcher::class)
		));

		$group1 = $this->createMock(IGroup::class);
		$group1->expects($this->any())->method('getGID')->willReturn('group1');
		$group1->expects($this->any())->method('inGroup')->with($this->user)->willReturn(true);

		$group2 = $this->createMock(IGroup::class);
		$group2->expects($this->any())->method('getGID')->willReturn('group2');
		$group2->expects($this->any())->method('inGroup')->with($this->user)->willReturn(true);

		$this->userManager->expects($this->any())->method('get')->willReturn($this->user);
		$this->groupManager->expects($this->any())->method(('getUserGroups'))->willReturn([$group1, $group2]);
		$this->groupManager->expects($this->any())->method(('get'))
			->will($this->returnValueMap([
				['group1', $group1],
				['group2', $group2],
			]));
	}

	protected function tearDown(): void {
		// clear the share external table to avoid side effects
		$query = \OC::$server->getDatabaseConnection()->prepare('DELETE FROM `*PREFIX*share_external`');
		$result = $query->execute();
		$result->closeCursor();

		parent::tearDown();
	}

	private function createManagerForUser($userId) {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')
			->willReturn($userId);
		$userSession = $this->createMock(IUserSession::class);
		$userSession->method('getUser')
			->willReturn($user);

		return $this->getMockBuilder(Manager::class)
			->setConstructorArgs(
				[
					\OC::$server->getDatabaseConnection(),
					$this->mountManager,
					new StorageFactory(),
					$this->clientService,
					\OC::$server->getNotificationManager(),
					\OC::$server->query(\OCP\OCS\IDiscoveryService::class),
					$this->cloudFederationProviderManager,
					$this->cloudFederationFactory,
					$this->groupManager,
					$this->userManager,
					$userSession,
					$this->eventDispatcher,
					$this->logger,
				]
			)->setMethods(['tryOCMEndPoint'])->getMock();
	}

	private function setupMounts() {
		$this->clearMounts();
		$mounts = $this->testMountProvider->getMountsForUser($this->user, new StorageFactory());
		foreach ($mounts as $mount) {
			$this->mountManager->addMount($mount);
		}
	}

	private function clearMounts() {
		$this->mountManager->clear();
		$this->mountManager->addMount(new MountPoint(Temporary::class, '', []));
	}

	public function testAddUserShare() {
		$this->doTestAddShare([
			'remote' => 'http://localhost',
			'token' => 'token1',
			'password' => '',
			'name' => '/SharedFolder',
			'owner' => 'foobar',
			'shareType' => IShare::TYPE_USER,
			'accepted' => false,
			'user' => $this->uid,
			'remoteId' => '2342'
		], false);
	}

	public function testAddGroupShare() {
		$this->doTestAddShare([
			'remote' => 'http://localhost',
			'token' => 'token1',
			'password' => '',
			'name' => '/SharedFolder',
			'owner' => 'foobar',
			'shareType' => IShare::TYPE_GROUP,
			'accepted' => false,
			'user' => 'group1',
			'remoteId' => '2342'
		], true);
	}

	public function doTestAddShare($shareData1, $isGroup = false) {
		$shareData2 = $shareData1;
		$shareData2['token'] = 'token2';
		$shareData3 = $shareData1;
		$shareData3['token'] = 'token3';

		if ($isGroup) {
			$this->manager->expects($this->never())->method('tryOCMEndPoint');
		} else {
			$this->manager->method('tryOCMEndPoint')
				->withConsecutive(
					['http://localhost', 'token1', '2342', 'accept'],
					['http://localhost', 'token3', '2342', 'decline'],
				)->willReturnOnConsecutiveCalls(
					false,
					false,
				);
		}

		// Add a share for "user"
		$this->assertSame(null, call_user_func_array([$this->manager, 'addShare'], $shareData1));
		$openShares = $this->manager->getOpenShares();
		$this->assertCount(1, $openShares);
		$this->assertExternalShareEntry($shareData1, $openShares[0], 1, '{{TemporaryMountPointName#' . $shareData1['name'] . '}}', $shareData1['user']);

		$this->setupMounts();
		$this->assertNotMount('SharedFolder');
		$this->assertNotMount('{{TemporaryMountPointName#' . $shareData1['name'] . '}}');

		// Add a second share for "user" with the same name
		$this->assertSame(null, call_user_func_array([$this->manager, 'addShare'], $shareData2));
		$openShares = $this->manager->getOpenShares();
		$this->assertCount(2, $openShares);
		$this->assertExternalShareEntry($shareData1, $openShares[0], 1, '{{TemporaryMountPointName#' . $shareData1['name'] . '}}', $shareData1['user']);
		// New share falls back to "-1" appendix, because the name is already taken
		$this->assertExternalShareEntry($shareData2, $openShares[1], 2, '{{TemporaryMountPointName#' . $shareData2['name'] . '}}-1', $shareData2['user']);

		$this->setupMounts();
		$this->assertNotMount('SharedFolder');
		$this->assertNotMount('{{TemporaryMountPointName#' . $shareData1['name'] . '}}');
		$this->assertNotMount('{{TemporaryMountPointName#' . $shareData1['name'] . '}}-1');

		if (!$isGroup) {
			$client = $this->getMockBuilder('OCP\Http\Client\IClient')
				->disableOriginalConstructor()->getMock();
			$this->clientService->expects($this->at(0))
				->method('newClient')
				->willReturn($client);
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
				->with($this->stringStartsWith('http://localhost/ocs/v2.php/cloud/shares/' . $openShares[0]['remote_id']), $this->anything())
				->willReturn($response);
		}

		// Accept the first share
		$this->assertTrue($this->manager->acceptShare($openShares[0]['id']));

		// Check remaining shares - Accepted
		$acceptedShares = self::invokePrivate($this->manager, 'getShares', [true]);
		$this->assertCount(1, $acceptedShares);
		$shareData1['accepted'] = true;
		$this->assertExternalShareEntry($shareData1, $acceptedShares[0], 1, $shareData1['name'], $this->uid);
		// Check remaining shares - Open
		$openShares = $this->manager->getOpenShares();
		$this->assertCount(1, $openShares);
		$this->assertExternalShareEntry($shareData2, $openShares[0], 2, '{{TemporaryMountPointName#' . $shareData2['name'] . '}}-1', $shareData2['user']);

		$this->setupMounts();
		$this->assertMount($shareData1['name']);
		$this->assertNotMount('{{TemporaryMountPointName#' . $shareData1['name'] . '}}');
		$this->assertNotMount('{{TemporaryMountPointName#' . $shareData1['name'] . '}}-1');

		// Add another share for "user" with the same name
		$this->assertSame(null, call_user_func_array([$this->manager, 'addShare'], $shareData3));
		$openShares = $this->manager->getOpenShares();
		$this->assertCount(2, $openShares);
		$this->assertExternalShareEntry($shareData2, $openShares[0], 2, '{{TemporaryMountPointName#' . $shareData2['name'] . '}}-1', $shareData2['user']);
		if (!$isGroup) {
			// New share falls back to the original name (no "-\d", because the name is not taken)
			$this->assertExternalShareEntry($shareData3, $openShares[1], 3, '{{TemporaryMountPointName#' . $shareData3['name'] . '}}', $shareData3['user']);
		} else {
			$this->assertExternalShareEntry($shareData3, $openShares[1], 3, '{{TemporaryMountPointName#' . $shareData3['name'] . '}}-2', $shareData3['user']);
		}

		$this->setupMounts();
		$this->assertMount($shareData1['name']);
		$this->assertNotMount('{{TemporaryMountPointName#' . $shareData1['name'] . '}}');
		$this->assertNotMount('{{TemporaryMountPointName#' . $shareData1['name'] . '}}-1');

		if (!$isGroup) {
			$client = $this->getMockBuilder('OCP\Http\Client\IClient')
				->disableOriginalConstructor()->getMock();
			$this->clientService->expects($this->at(0))
				->method('newClient')
				->willReturn($client);
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
				->with($this->stringStartsWith('http://localhost/ocs/v2.php/cloud/shares/' . $openShares[1]['remote_id'] . '/decline'), $this->anything())
				->willReturn($response);
		}

		// Decline the third share
		$this->assertTrue($this->manager->declineShare($openShares[1]['id']));

		$this->setupMounts();
		$this->assertMount($shareData1['name']);
		$this->assertNotMount('{{TemporaryMountPointName#' . $shareData1['name'] . '}}');
		$this->assertNotMount('{{TemporaryMountPointName#' . $shareData1['name'] . '}}-1');

		// Check remaining shares - Accepted
		$acceptedShares = self::invokePrivate($this->manager, 'getShares', [true]);
		$this->assertCount(1, $acceptedShares);
		$shareData1['accepted'] = true;
		$this->assertExternalShareEntry($shareData1, $acceptedShares[0], 1, $shareData1['name'], $this->uid);
		// Check remaining shares - Open
		$openShares = $this->manager->getOpenShares();
		if ($isGroup) {
			// declining a group share adds it back to pending instead of deleting it
			$this->assertCount(2, $openShares);
			// this is a group share that is still open
			$this->assertExternalShareEntry($shareData2, $openShares[0], 2, '{{TemporaryMountPointName#' . $shareData2['name'] . '}}-1', $shareData2['user']);
			// this is the user share sub-entry matching the group share which got declined
			$this->assertExternalShareEntry($shareData3, $openShares[1], 2, '{{TemporaryMountPointName#' . $shareData3['name'] . '}}-2', $this->uid);
		} else {
			$this->assertCount(1, $openShares);
			$this->assertExternalShareEntry($shareData2, $openShares[0], 2, '{{TemporaryMountPointName#' . $shareData2['name'] . '}}-1', $this->uid);
		}

		$this->setupMounts();
		$this->assertMount($shareData1['name']);
		$this->assertNotMount('{{TemporaryMountPointName#' . $shareData1['name'] . '}}');
		$this->assertNotMount('{{TemporaryMountPointName#' . $shareData1['name'] . '}}-1');

		if ($isGroup) {
			// no http requests here
			$this->manager->removeGroupShares('group1');
		} else {
			$client1 = $this->getMockBuilder('OCP\Http\Client\IClient')
				->disableOriginalConstructor()->getMock();
			$client2 = $this->getMockBuilder('OCP\Http\Client\IClient')
				->disableOriginalConstructor()->getMock();
			$this->clientService->expects($this->exactly(2))
				->method('newClient')
				->willReturnOnConsecutiveCalls(
					$client1,
					$client2,
				);
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
				->with($this->stringStartsWith('http://localhost/ocs/v2.php/cloud/shares/' . $openShares[0]['remote_id'] . '/decline'), $this->anything())
				->willReturn($response);
			$client2->expects($this->once())
				->method('post')
				->with($this->stringStartsWith('http://localhost/ocs/v2.php/cloud/shares/' . $acceptedShares[0]['remote_id'] . '/decline'), $this->anything())
				->willReturn($response);

			$this->manager->removeUserShares($this->uid);
		}

		$this->assertEmpty(self::invokePrivate($this->manager, 'getShares', [null]), 'Asserting all shares for the user have been deleted');

		$this->clearMounts();
		self::invokePrivate($this->manager, 'setupMounts');
		$this->assertNotMount($shareData1['name']);
		$this->assertNotMount('{{TemporaryMountPointName#' . $shareData1['name'] . '}}');
		$this->assertNotMount('{{TemporaryMountPointName#' . $shareData1['name'] . '}}-1');
	}

	private function verifyAcceptedGroupShare($shareData) {
		$openShares = $this->manager->getOpenShares();
		$this->assertCount(0, $openShares);
		$acceptedShares = self::invokePrivate($this->manager, 'getShares', [true]);
		$this->assertCount(1, $acceptedShares);
		$shareData['accepted'] = true;
		$this->assertExternalShareEntry($shareData, $acceptedShares[0], 0, $shareData['name'], $this->uid);
		$this->setupMounts();
		$this->assertMount($shareData['name']);
	}

	private function verifyDeclinedGroupShare($shareData, $tempMount = null) {
		if ($tempMount === null) {
			$tempMount = '{{TemporaryMountPointName#/SharedFolder}}';
		}
		$openShares = $this->manager->getOpenShares();
		$this->assertCount(1, $openShares);
		$acceptedShares = self::invokePrivate($this->manager, 'getShares', [true]);
		$this->assertCount(0, $acceptedShares);
		$this->assertExternalShareEntry($shareData, $openShares[0], 0, $tempMount, $this->uid);
		$this->setupMounts();
		$this->assertNotMount($shareData['name']);
		$this->assertNotMount($tempMount);
	}

	private function createTestUserShare($userId = 'user1') {
		$shareData = [
			'remote' => 'http://localhost',
			'token' => 'token1',
			'password' => '',
			'name' => '/SharedFolder',
			'owner' => 'foobar',
			'shareType' => IShare::TYPE_USER,
			'accepted' => false,
			'user' => $userId,
			'remoteId' => '2342'
		];

		$this->assertSame(null, call_user_func_array([$this->manager, 'addShare'], $shareData));

		return $shareData;
	}
	private function createTestGroupShare($groupId = 'group1') {
		$shareData = [
			'remote' => 'http://localhost',
			'token' => 'token1',
			'password' => '',
			'name' => '/SharedFolder',
			'owner' => 'foobar',
			'shareType' => IShare::TYPE_GROUP,
			'accepted' => false,
			'user' => $groupId,
			'remoteId' => '2342'
		];

		$this->assertSame(null, call_user_func_array([$this->manager, 'addShare'], $shareData));

		$allShares = self::invokePrivate($this->manager, 'getShares', [null]);
		foreach ($allShares as $share) {
			if ($share['user'] === $groupId) {
				// this will hold the main group entry
				$groupShare = $share;
				break;
			}
		}

		return [$shareData, $groupShare];
	}

	public function testAcceptOriginalGroupShare() {
		[$shareData, $groupShare] = $this->createTestGroupShare();
		$this->assertTrue($this->manager->acceptShare($groupShare['id']));
		$this->verifyAcceptedGroupShare($shareData);

		// a second time
		$this->assertTrue($this->manager->acceptShare($groupShare['id']));
		$this->verifyAcceptedGroupShare($shareData);
	}

	public function testAcceptGroupShareAgainThroughGroupShare() {
		[$shareData, $groupShare] = $this->createTestGroupShare();
		$this->assertTrue($this->manager->acceptShare($groupShare['id']));
		$this->verifyAcceptedGroupShare($shareData);

		// decline again, this keeps the sub-share
		$this->assertTrue($this->manager->declineShare($groupShare['id']));
		$this->verifyDeclinedGroupShare($shareData, '/SharedFolder');

		// this will return sub-entries
		$openShares = $this->manager->getOpenShares();
		$this->assertCount(1, $openShares);

		// accept through group share
		$this->assertTrue($this->manager->acceptShare($groupShare['id']));
		$this->verifyAcceptedGroupShare($shareData, '/SharedFolder');

		// accept a second time
		$this->assertTrue($this->manager->acceptShare($groupShare['id']));
		$this->verifyAcceptedGroupShare($shareData, '/SharedFolder');
	}

	public function testAcceptGroupShareAgainThroughSubShare() {
		[$shareData, $groupShare] = $this->createTestGroupShare();
		$this->assertTrue($this->manager->acceptShare($groupShare['id']));
		$this->verifyAcceptedGroupShare($shareData);

		// decline again, this keeps the sub-share
		$this->assertTrue($this->manager->declineShare($groupShare['id']));
		$this->verifyDeclinedGroupShare($shareData, '/SharedFolder');

		// this will return sub-entries
		$openShares = $this->manager->getOpenShares();
		$this->assertCount(1, $openShares);

		// accept through sub-share
		$this->assertTrue($this->manager->acceptShare($openShares[0]['id']));
		$this->verifyAcceptedGroupShare($shareData);

		// accept a second time
		$this->assertTrue($this->manager->acceptShare($openShares[0]['id']));
		$this->verifyAcceptedGroupShare($shareData);
	}

	public function testDeclineOriginalGroupShare() {
		[$shareData, $groupShare] = $this->createTestGroupShare();
		$this->assertTrue($this->manager->declineShare($groupShare['id']));
		$this->verifyDeclinedGroupShare($shareData);

		// a second time
		$this->assertTrue($this->manager->declineShare($groupShare['id']));
		$this->verifyDeclinedGroupShare($shareData);
	}

	public function testDeclineGroupShareAgainThroughGroupShare() {
		[$shareData, $groupShare] = $this->createTestGroupShare();
		$this->assertTrue($this->manager->acceptShare($groupShare['id']));
		$this->verifyAcceptedGroupShare($shareData);

		// decline again, this keeps the sub-share
		$this->assertTrue($this->manager->declineShare($groupShare['id']));
		$this->verifyDeclinedGroupShare($shareData, '/SharedFolder');

		// a second time
		$this->assertTrue($this->manager->declineShare($groupShare['id']));
		$this->verifyDeclinedGroupShare($shareData, '/SharedFolder');
	}

	public function testDeclineGroupShareAgainThroughSubshare() {
		[$shareData, $groupShare] = $this->createTestGroupShare();
		$this->assertTrue($this->manager->acceptShare($groupShare['id']));
		$this->verifyAcceptedGroupShare($shareData);

		// this will return sub-entries
		$allShares = self::invokePrivate($this->manager, 'getShares', [null]);
		$this->assertCount(1, $allShares);

		// decline again through sub-share
		$this->assertTrue($this->manager->declineShare($allShares[0]['id']));
		$this->verifyDeclinedGroupShare($shareData, '/SharedFolder');

		// a second time
		$this->assertTrue($this->manager->declineShare($allShares[0]['id']));
		$this->verifyDeclinedGroupShare($shareData, '/SharedFolder');
	}

	public function testDeclineGroupShareAgainThroughMountPoint() {
		[$shareData, $groupShare] = $this->createTestGroupShare();
		$this->assertTrue($this->manager->acceptShare($groupShare['id']));
		$this->verifyAcceptedGroupShare($shareData);

		// decline through mount point name
		$this->assertTrue($this->manager->removeShare($this->uid . '/files/' . $shareData['name']));
		$this->verifyDeclinedGroupShare($shareData, '/SharedFolder');

		// second time must fail as the mount point is gone
		$this->assertFalse($this->manager->removeShare($this->uid . '/files/' . $shareData['name']));
	}

	public function testDeclineThenAcceptGroupShareAgainThroughGroupShare() {
		[$shareData, $groupShare] = $this->createTestGroupShare();
		// decline, this creates a declined sub-share
		$this->assertTrue($this->manager->declineShare($groupShare['id']));
		$this->verifyDeclinedGroupShare($shareData);

		// this will return sub-entries
		$openShares = $this->manager->getOpenShares();

		// accept through sub-share
		$this->assertTrue($this->manager->acceptShare($groupShare['id']));
		$this->verifyAcceptedGroupShare($shareData, '/SharedFolder');

		// accept a second time
		$this->assertTrue($this->manager->acceptShare($groupShare['id']));
		$this->verifyAcceptedGroupShare($shareData, '/SharedFolder');
	}

	public function testDeclineThenAcceptGroupShareAgainThroughSubShare() {
		[$shareData, $groupShare] = $this->createTestGroupShare();
		// decline, this creates a declined sub-share
		$this->assertTrue($this->manager->declineShare($groupShare['id']));
		$this->verifyDeclinedGroupShare($shareData);

		// this will return sub-entries
		$openShares = $this->manager->getOpenShares();

		// accept through sub-share
		$this->assertTrue($this->manager->acceptShare($openShares[0]['id']));
		$this->verifyAcceptedGroupShare($shareData);

		// accept a second time
		$this->assertTrue($this->manager->acceptShare($openShares[0]['id']));
		$this->verifyAcceptedGroupShare($shareData);
	}

	public function testDeleteUserShares() {
		// user 1 shares

		$shareData = $this->createTestUserShare($this->uid);

		[$shareData, $groupShare] = $this->createTestGroupShare();

		$shares = $this->manager->getOpenShares();
		$this->assertCount(2, $shares);

		$this->assertTrue($this->manager->acceptShare($groupShare['id']));

		// user 2 shares
		$manager2 = $this->createManagerForUser('user2');
		$shareData2 = [
			'remote' => 'http://localhost',
			'token' => 'token1',
			'password' => '',
			'name' => '/SharedFolder',
			'owner' => 'foobar',
			'shareType' => IShare::TYPE_USER,
			'accepted' => false,
			'user' => 'user2',
			'remoteId' => '2342'
		];
		$this->assertSame(null, call_user_func_array([$manager2, 'addShare'], $shareData2));

		$user2Shares = $manager2->getOpenShares();
		$this->assertCount(2, $user2Shares);

		$this->manager->expects($this->once())->method('tryOCMEndPoint')->with('http://localhost', 'token1', '2342', 'decline')->willReturn([]);
		$this->manager->removeUserShares($this->uid);

		$user1Shares = $this->manager->getOpenShares();
		// user share is gone, group is still there
		$this->assertCount(1, $user1Shares);
		$this->assertEquals($user1Shares[0]['share_type'], IShare::TYPE_GROUP);

		// user 2 shares untouched
		$user2Shares = $manager2->getOpenShares();
		$this->assertCount(2, $user2Shares);
		$this->assertEquals($user2Shares[0]['share_type'], IShare::TYPE_GROUP);
		$this->assertEquals($user2Shares[0]['user'], 'group1');
		$this->assertEquals($user2Shares[1]['share_type'], IShare::TYPE_USER);
		$this->assertEquals($user2Shares[1]['user'], 'user2');
	}

	public function testDeleteGroupShares() {
		$shareData = $this->createTestUserShare($this->uid);

		[$shareData, $groupShare] = $this->createTestGroupShare();

		$shares = $this->manager->getOpenShares();
		$this->assertCount(2, $shares);

		$this->assertTrue($this->manager->acceptShare($groupShare['id']));

		// user 2 shares
		$manager2 = $this->createManagerForUser('user2');
		$shareData2 = [
			'remote' => 'http://localhost',
			'token' => 'token1',
			'password' => '',
			'name' => '/SharedFolder',
			'owner' => 'foobar',
			'shareType' => IShare::TYPE_USER,
			'accepted' => false,
			'user' => 'user2',
			'remoteId' => '2342'
		];
		$this->assertSame(null, call_user_func_array([$manager2, 'addShare'], $shareData2));

		$user2Shares = $manager2->getOpenShares();
		$this->assertCount(2, $user2Shares);

		$this->manager->expects($this->never())->method('tryOCMEndPoint');
		$this->manager->removeGroupShares('group1');

		$user1Shares = $this->manager->getOpenShares();
		// user share is gone, group is still there
		$this->assertCount(1, $user1Shares);
		$this->assertEquals($user1Shares[0]['share_type'], IShare::TYPE_USER);

		// user 2 shares untouched
		$user2Shares = $manager2->getOpenShares();
		$this->assertCount(1, $user2Shares);
		$this->assertEquals($user2Shares[0]['share_type'], IShare::TYPE_USER);
		$this->assertEquals($user2Shares[0]['user'], 'user2');
	}

	/**
	 * @param array $expected
	 * @param array $actual
	 * @param int $share
	 * @param string $mountPoint
	 */
	protected function assertExternalShareEntry($expected, $actual, $share, $mountPoint, $targetEntity) {
		$this->assertEquals($expected['remote'], $actual['remote'], 'Asserting remote of a share #' . $share);
		$this->assertEquals($expected['token'], $actual['share_token'], 'Asserting token of a share #' . $share);
		$this->assertEquals($expected['name'], $actual['name'], 'Asserting name of a share #' . $share);
		$this->assertEquals($expected['owner'], $actual['owner'], 'Asserting owner of a share #' . $share);
		$this->assertEquals($expected['accepted'], (int) $actual['accepted'], 'Asserting accept of a share #' . $share);
		$this->assertEquals($targetEntity, $actual['user'], 'Asserting user of a share #' . $share);
		$this->assertEquals($mountPoint, $actual['mountpoint'], 'Asserting mountpoint of a share #' . $share);
	}

	private function assertMount($mountPoint) {
		$mountPoint = rtrim($mountPoint, '/');
		$mount = $this->mountManager->find($this->getFullPath($mountPoint));
		$this->assertInstanceOf('\OCA\Files_Sharing\External\Mount', $mount);
		$this->assertInstanceOf('\OCP\Files\Mount\IMountPoint', $mount);
		$this->assertEquals($this->getFullPath($mountPoint), rtrim($mount->getMountPoint(), '/'));
		$storage = $mount->getStorage();
		$this->assertInstanceOf('\OCA\Files_Sharing\External\Storage', $storage);
	}

	private function assertNotMount($mountPoint) {
		$mountPoint = rtrim($mountPoint, '/');
		try {
			$mount = $this->mountManager->find($this->getFullPath($mountPoint));
			$this->assertInstanceOf('\OCP\Files\Mount\IMountPoint', $mount);
			$this->assertNotEquals($this->getFullPath($mountPoint), rtrim($mount->getMountPoint(), '/'));
		} catch (NotFoundException $e) {

		}
	}

	private function getFullPath($path) {
		return '/' . $this->uid . '/files' . $path;
	}
}
