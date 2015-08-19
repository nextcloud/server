<?php
/**
 * @author Robin McCorkell <rmccorkell@owncloud.com>
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
namespace OCA\Files_External\Tests\Service;

use \OCA\Files_External\Service\UserGlobalStoragesService;
use \OCP\IGroupManager;

use \OCA\Files_External\Lib\StorageConfig;

class UserGlobalStoragesServiceTest extends GlobalStoragesServiceTest {

	protected $groupManager;

	protected $globalStoragesService;

	protected $user;

	const USER_ID = 'test_user';
	const GROUP_ID = 'test_group';

	public function setUp() {
		parent::setUp();

		$this->globalStoragesService = $this->service;

		$this->user = new \OC\User\User(self::USER_ID, null);
		$userSession = $this->getMock('\OCP\IUserSession');
		$userSession
			->expects($this->any())
			->method('getUser')
			->will($this->returnValue($this->user));

		$this->groupManager = $this->getMock('\OCP\IGroupManager');
		$this->groupManager->method('isInGroup')
			->will($this->returnCallback(function($userId, $groupId) {
				if ($userId === self::USER_ID && $groupId === self::GROUP_ID) {
					return true;
				}
				return false;
			}));

		$this->service = new UserGlobalStoragesService(
			$this->backendService,
			$userSession,
			$this->groupManager
		);
	}

	public function applicableStorageProvider() {
		return [
			[[], [], true],

			// not applicable cases
			[['user1'], [], false],
			[[], ['group1'], false],
			[['user1'], ['group1'], false],

			// applicable cases
			[[self::USER_ID], [], true],
			[[], [self::GROUP_ID], true],
			[[self::USER_ID], ['group1'], true],
			[['user1'], [self::GROUP_ID], true],

			// sanity checks
			[['user1', 'user2', self::USER_ID, 'user3'], [], true],
		];
	}

	/**
	 * @dataProvider applicableStorageProvider
	 */
	public function testGetStorageWithApplicable($applicableUsers, $applicableGroups, $isVisible) {
		$backend = $this->backendService->getBackend('identifier:\OCA\Files_External\Lib\Backend\SMB');
		$authMechanism = $this->backendService->getAuthMechanism('identifier:\Auth\Mechanism');

		$storage = new StorageConfig();
		$storage->setMountPoint('mountpoint');
		$storage->setBackend($backend);
		$storage->setAuthMechanism($authMechanism);
		$storage->setBackendOptions(['password' => 'testPassword']);
		$storage->setApplicableUsers($applicableUsers);
		$storage->setApplicableGroups($applicableGroups);

		$newStorage = $this->globalStoragesService->addStorage($storage);

		$storages = $this->service->getAllStorages();
		if ($isVisible) {
			$this->assertEquals(1, count($storages));
			$retrievedStorage = $this->service->getStorage($newStorage->getId());
			$this->assertEquals('/mountpoint', $retrievedStorage->getMountPoint());
		} else {
			$this->assertEquals(0, count($storages));
		}

	}

	/**
	 * @expectedException \DomainException
	 */
	public function testAddStorage($storageParams = null) {
		$backend = $this->backendService->getBackend('identifier:\OCA\Files_External\Lib\Backend\SMB');
		$authMechanism = $this->backendService->getAuthMechanism('identifier:\Auth\Mechanism');

		$storage = new StorageConfig(255);
		$storage->setMountPoint('mountpoint');
		$storage->setBackend($backend);
		$storage->setAuthMechanism($authMechanism);
		$storage->setBackendOptions(['password' => 'testPassword']);

		$this->service->addStorage($storage);
	}

	/**
	 * @expectedException \DomainException
	 */
	public function testUpdateStorage($storageParams = null) {
		$backend = $this->backendService->getBackend('identifier:\OCA\Files_External\Lib\Backend\SMB');
		$authMechanism = $this->backendService->getAuthMechanism('identifier:\Auth\Mechanism');

		$storage = new StorageConfig(255);
		$storage->setMountPoint('mountpoint');
		$storage->setBackend($backend);
		$storage->setAuthMechanism($authMechanism);
		$storage->setBackendOptions(['password' => 'testPassword']);

		$newStorage = $this->globalStoragesService->addStorage($storage);

		$retrievedStorage = $this->service->getStorage($newStorage->getId());
		$retrievedStorage->setMountPoint('abc');
		$this->service->updateStorage($retrievedStorage);
	}

	/**
	 * @expectedException \DomainException
	 */
	public function testDeleteStorage() {
		$backend = $this->backendService->getBackend('identifier:\OCA\Files_External\Lib\Backend\SMB');
		$authMechanism = $this->backendService->getAuthMechanism('identifier:\Auth\Mechanism');

		$storage = new StorageConfig(255);
		$storage->setMountPoint('mountpoint');
		$storage->setBackend($backend);
		$storage->setAuthMechanism($authMechanism);
		$storage->setBackendOptions(['password' => 'testPassword']);

		$newStorage = $this->globalStoragesService->addStorage($storage);
		$this->assertEquals(1, $newStorage->getId());

		$this->service->removeStorage(1);
	}

	public function testHooksAddStorage($a = null, $b = null, $c = null) {
		// we don't test this here
		$this->assertTrue(true);
	}

	public function testHooksUpdateStorage($a = null, $b = null, $c = null, $d = null, $e = null) {
		// we don't test this here
		$this->assertTrue(true);
	}

	public function testHooksRenameMountPoint() {
		// we don't test this here
		$this->assertTrue(true);
	}

	public function testHooksDeleteStorage($a = null, $b = null, $c = null) {
		// we don't test this here
		$this->assertTrue(true);
	}

	public function testLegacyConfigConversionApplicableAll() {
		// we don't test this here
		$this->assertTrue(true);
	}

	public function testLegacyConfigConversionApplicableUserAndGroup() {
		// we don't test this here
		$this->assertTrue(true);
	}

	public function testReadLegacyConfigAndGenerateConfigId() {
		// we don't test this here
		$this->assertTrue(true);
	}

	public function testReadLegacyConfigNoAuthMechanism() {
		// we don't test this here
		$this->assertTrue(true);
	}

	public function testReadLegacyConfigClass() {
		// we don't test this here
		$this->assertTrue(true);
	}

}
