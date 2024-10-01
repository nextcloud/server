<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_External\Tests\Controller;

use OC\User\User;
use OCA\Files_External\Controller\GlobalStoragesController;
use OCA\Files_External\Service\BackendService;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

class GlobalStoragesControllerTest extends StoragesControllerTest {
	protected function setUp(): void {
		parent::setUp();

		$this->service = $this->getMockBuilder('\OCA\Files_External\Service\GlobalStoragesService')
			->disableOriginalConstructor()
			->getMock();

		$this->service->method('getVisibilityType')
			->willReturn(BackendService::VISIBILITY_ADMIN);

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

		return new GlobalStoragesController(
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
}
