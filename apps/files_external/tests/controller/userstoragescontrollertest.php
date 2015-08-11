<?php
/**
 * @author Vincent Petry <pvince81@owncloud.com>
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
namespace OCA\Files_external\Tests\Controller;

use \OCA\Files_external\Controller\UserStoragesController;
use \OCA\Files_external\Service\UserStoragesService;
use \OCP\AppFramework\Http;
use \OCA\Files_external\NotFoundException;
use \OCA\Files_External\Lib\StorageConfig;
use \OCA\Files_External\Service\BackendService;

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
	}

	public function testAddOrUpdateStorageDisallowedBackend() {
		$backend = $this->getBackendMock();
		$backend->method('isVisibleFor')
			->with(BackendService::VISIBILITY_PERSONAL)
			->willReturn(false);

		$storageConfig = new StorageConfig(1);
		$storageConfig->setMountPoint('mount');
		$storageConfig->setBackend($backend);
		$storageConfig->setBackendOptions([]);

		$this->service->expects($this->exactly(2))
			->method('createStorage')
			->will($this->returnValue($storageConfig));
		$this->service->expects($this->never())
			->method('addStorage');
		$this->service->expects($this->never())
			->method('updateStorage');

		$response = $this->controller->create(
			'mount',
			'\OC\Files\Storage\SMB',
			array(),
			[],
			[],
			[],
			null
		);

		$this->assertEquals(Http::STATUS_UNPROCESSABLE_ENTITY, $response->getStatus());

		$response = $this->controller->update(
			1,
			'mount',
			'\OC\Files\Storage\SMB',
			array(),
			[],
			[],
			[],
			null
		);

		$this->assertEquals(Http::STATUS_UNPROCESSABLE_ENTITY, $response->getStatus());
	}

}
