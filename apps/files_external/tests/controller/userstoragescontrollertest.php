<?php
/**
 * ownCloud
 *
 * @author Vincent Petry
 * Copyright (c) 2015 Vincent Petry <pvince81@owncloud.com>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\Files_external\Tests\Controller;

use \OCA\Files_external\Controller\UserStoragesController;
use \OCA\Files_external\Service\UserStoragesService;
use \OCP\AppFramework\Http;
use \OCA\Files_external\NotFoundException;

class UserStoragesControllerTest extends StoragesControllerTest {

	/**
	 * @var array
	 */
	private $oldAllowedBackends;

	public function setUp() {
		parent::setUp();
		$this->service = $this->getMockBuilder('\OCA\Files_external\Service\UserStoragesService')
			->disableOriginalConstructor()
			->getMock();

		$this->controller = new UserStoragesController(
			'files_external',
			$this->getMock('\OCP\IRequest'),
			$this->getMock('\OCP\IL10N'),
			$this->service
		);

		$config = \OC::$server->getConfig();

		$this->oldAllowedBackends = $config->getAppValue(
			'files_external',
			'user_mounting_backends',
			''
		);
		$config->setAppValue(
			'files_external',
			'user_mounting_backends',
			'\OC\Files\Storage\SMB'
		);
	}

	public function tearDown() {
		$config = \OC::$server->getConfig();
		$config->setAppValue(
			'files_external',
			'user_mounting_backends',
			$this->oldAllowedBackends
		);
		parent::tearDown();
	}

	function disallowedBackendClassProvider() {
		return array(
			array('\OC\Files\Storage\Local'),
			array('\OC\Files\Storage\FTP'),
		);
	}
	/**
	 * @dataProvider disallowedBackendClassProvider
	 */
	public function testAddOrUpdateStorageDisallowedBackend($backendClass) {
		$this->service->expects($this->never())
			->method('addStorage');
		$this->service->expects($this->never())
			->method('updateStorage');

		$response = $this->controller->create(
			'mount',
			$backendClass,
			array(),
			[],
			[],
			null
		);

		$this->assertEquals(Http::STATUS_UNPROCESSABLE_ENTITY, $response->getStatus());

		$response = $this->controller->update(
			1,
			'mount',
			$backendClass,
			array(),
			[],
			[],
			null
		);

		$this->assertEquals(Http::STATUS_UNPROCESSABLE_ENTITY, $response->getStatus());
	}

}
