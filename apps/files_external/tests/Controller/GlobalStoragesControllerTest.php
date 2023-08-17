<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Vincent Petry <vincent@nextcloud.com>
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
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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

	public function testAddLocalStorageWhenDisabled() {
		$this->controller = $this->createController(false);
		parent::testAddLocalStorageWhenDisabled();
	}
}
