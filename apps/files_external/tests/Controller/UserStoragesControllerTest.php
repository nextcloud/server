<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_External\Tests\Controller;

use OC\User\User;
use OCA\Files_External\Controller\UserStoragesController;
use OCA\Files_External\Lib\StorageConfig;
use OCA\Files_External\Service\BackendService;
use OCP\AppFramework\Http;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

class UserStoragesControllerTest extends StoragesControllerTest {

	/**
	 * @var array
	 */
	private $oldAllowedBackends;

	protected function setUp(): void {
		parent::setUp();
		$this->service = $this->getMockBuilder('\OCA\Files_External\Service\UserStoragesService')
			->disableOriginalConstructor()
			->getMock();

		$this->service->method('getVisibilityType')
			->willReturn(BackendService::VISIBILITY_PERSONAL);

		$this->controller = $this->createController(true);
	}

	private function createController($allowCreateLocal = true) {
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')
			->willReturn(new User('test', null, $this->createMock(IEventDispatcher::class)));

		$config = $this->createMock(IConfig::class);
		$config->method('getSystemValue')
			->with('files_external_allow_create_new_local', true)
			->willReturn($allowCreateLocal);

		return new UserStoragesController(
			'files_external',
			$this->createMock(IRequest::class),
			$this->createMock(IL10N::class),
			$this->service,
			$this->createMock(LoggerInterface::class),
			$session,
			$this->createMock(IGroupManager::class),
			$config
		);
	}

	public function testAddLocalStorageWhenDisabled(): void {
		$this->controller = $this->createController(false);
		parent::testAddLocalStorageWhenDisabled();
	}

	public function testAddOrUpdateStorageDisallowedBackend(): void {
		$backend = $this->getBackendMock();
		$backend->method('isVisibleFor')
			->with(BackendService::VISIBILITY_PERSONAL)
			->willReturn(false);
		$authMech = $this->getAuthMechMock();

		$storageConfig = new StorageConfig(1);
		$storageConfig->setMountPoint('mount');
		$storageConfig->setBackend($backend);
		$storageConfig->setAuthMechanism($authMech);
		$storageConfig->setBackendOptions([]);

		$this->service->expects($this->exactly(2))
			->method('createStorage')
			->willReturn($storageConfig);
		$this->service->expects($this->never())
			->method('addStorage');
		$this->service->expects($this->never())
			->method('updateStorage');

		$response = $this->controller->create(
			'mount',
			'\OCA\Files_External\Lib\Storage\SMB',
			'\Auth\Mechanism',
			[],
			[],
			[],
			[],
			null
		);

		$this->assertEquals(Http::STATUS_UNPROCESSABLE_ENTITY, $response->getStatus());

		$response = $this->controller->update(
			1,
			'mount',
			'\OCA\Files_External\Lib\Storage\SMB',
			'\Auth\Mechanism',
			[],
			[],
			[],
			[],
			null
		);

		$this->assertEquals(Http::STATUS_UNPROCESSABLE_ENTITY, $response->getStatus());
	}
}
