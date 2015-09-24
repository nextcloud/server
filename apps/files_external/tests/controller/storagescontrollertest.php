<?php
/**
 * @author Vincent Petry <pvince81@owncloud.com>
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
namespace OCA\Files_external\Tests\Controller;

use \OCP\AppFramework\Http;

use \OCA\Files_external\Controller\GlobalStoragesController;
use \OCA\Files_external\Service\GlobalStoragesService;
use \OCA\Files_external\Lib\StorageConfig;
use \OCA\Files_external\NotFoundException;

abstract class StoragesControllerTest extends \Test\TestCase {

	/**
	 * @var GlobalStoragesController
	 */
	protected $controller;

	/**
	 * @var GlobalStoragesService
	 */
	protected $service;

	public function setUp() {
		\OC_Mount_Config::$skipTest = true;
	}

	public function tearDown() {
		\OC_Mount_Config::$skipTest = false;
	}

	protected function getBackendMock($class = '\OCA\Files_External\Lib\Backend\SMB', $storageClass = '\OC\Files\Storage\SMB') {
		$backend = $this->getMockBuilder('\OCA\Files_External\Lib\Backend\Backend')
			->disableOriginalConstructor()
			->getMock();
		$backend->method('getStorageClass')
			->willReturn($storageClass);
		$backend->method('getIdentifier')
			->willReturn('identifier:'.$class);
		return $backend;
	}

	protected function getAuthMechMock($scheme = 'null', $class = '\OCA\Files_External\Lib\Auth\NullMechanism') {
		$authMech = $this->getMockBuilder('\OCA\Files_External\Lib\Auth\AuthMechanism')
			->disableOriginalConstructor()
			->getMock();
		$authMech->method('getScheme')
			->willReturn($scheme);
		$authMech->method('getIdentifier')
			->willReturn('identifier:'.$class);

		return $authMech;
	}

	public function testAddStorage() {
		$authMech = $this->getAuthMechMock();
		$authMech->method('validateStorage')
			->willReturn(true);
		$authMech->method('isVisibleFor')
			->willReturn(true);
		$backend = $this->getBackendMock();
		$backend->method('validateStorage')
			->willReturn(true);
		$backend->method('isVisibleFor')
			->willReturn(true);

		$storageConfig = new StorageConfig(1);
		$storageConfig->setMountPoint('mount');
		$storageConfig->setBackend($backend);
		$storageConfig->setAuthMechanism($authMech);
		$storageConfig->setBackendOptions([]);

		$this->service->expects($this->once())
			->method('createStorage')
			->will($this->returnValue($storageConfig));
		$this->service->expects($this->once())
			->method('addStorage')
			->will($this->returnValue($storageConfig));

		$response = $this->controller->create(
			'mount',
			'\OC\Files\Storage\SMB',
			'\OCA\Files_External\Lib\Auth\NullMechanism',
			array(),
			[],
			[],
			[],
			null
		);

		$data = $response->getData();
		$this->assertEquals(Http::STATUS_CREATED, $response->getStatus());
		$this->assertEquals($storageConfig, $data);
	}

	public function testUpdateStorage() {
		$authMech = $this->getAuthMechMock();
		$authMech->method('validateStorage')
			->willReturn(true);
		$authMech->method('isVisibleFor')
			->willReturn(true);
		$backend = $this->getBackendMock();
		$backend->method('validateStorage')
			->willReturn(true);
		$backend->method('isVisibleFor')
			->willReturn(true);

		$storageConfig = new StorageConfig(1);
		$storageConfig->setMountPoint('mount');
		$storageConfig->setBackend($backend);
		$storageConfig->setAuthMechanism($authMech);
		$storageConfig->setBackendOptions([]);

		$this->service->expects($this->once())
			->method('createStorage')
			->will($this->returnValue($storageConfig));
		$this->service->expects($this->once())
			->method('updateStorage')
			->will($this->returnValue($storageConfig));

		$response = $this->controller->update(
			1,
			'mount',
			'\OC\Files\Storage\SMB',
			'\OCA\Files_External\Lib\Auth\NullMechanism',
			array(),
			[],
			[],
			[],
			null
		);

		$data = $response->getData();
		$this->assertEquals(Http::STATUS_OK, $response->getStatus());
		$this->assertEquals($storageConfig, $data);
	}

	function mountPointNamesProvider() {
		return array(
			array(''),
			array('/'),
			array('//'),
		);
	}

	/**
	 * @dataProvider mountPointNamesProvider
	 */
	public function testAddOrUpdateStorageInvalidMountPoint($mountPoint) {
		$storageConfig = new StorageConfig(1);
		$storageConfig->setMountPoint($mountPoint);
		$storageConfig->setBackend($this->getBackendMock());
		$storageConfig->setAuthMechanism($this->getAuthMechMock());
		$storageConfig->setBackendOptions([]);

		$this->service->expects($this->exactly(2))
			->method('createStorage')
			->will($this->returnValue($storageConfig));
		$this->service->expects($this->never())
			->method('addStorage');
		$this->service->expects($this->never())
			->method('updateStorage');

		$response = $this->controller->create(
			$mountPoint,
			'\OC\Files\Storage\SMB',
			'\OCA\Files_External\Lib\Auth\NullMechanism',
			array(),
			[],
			[],
			[],
			null
		);

		$this->assertEquals(Http::STATUS_UNPROCESSABLE_ENTITY, $response->getStatus());

		$response = $this->controller->update(
			1,
			$mountPoint,
			'\OC\Files\Storage\SMB',
			'\OCA\Files_External\Lib\Auth\NullMechanism',
			array(),
			[],
			[],
			[],
			null
		);

		$this->assertEquals(Http::STATUS_UNPROCESSABLE_ENTITY, $response->getStatus());
	}

	public function testAddOrUpdateStorageInvalidBackend() {
		$this->service->expects($this->exactly(2))
			->method('createStorage')
			->will($this->throwException(new \InvalidArgumentException()));
		$this->service->expects($this->never())
			->method('addStorage');
		$this->service->expects($this->never())
			->method('updateStorage');

		$response = $this->controller->create(
			'mount',
			'\OC\Files\Storage\InvalidStorage',
			'\OCA\Files_External\Lib\Auth\NullMechanism',
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
			'\OC\Files\Storage\InvalidStorage',
			'\OCA\Files_External\Lib\Auth\NullMechanism',
			array(),
			[],
			[],
			[],
			null
		);

		$this->assertEquals(Http::STATUS_UNPROCESSABLE_ENTITY, $response->getStatus());
	}

	public function testUpdateStorageNonExisting() {
		$authMech = $this->getAuthMechMock();
		$authMech->method('validateStorage')
			->willReturn(true);
		$authMech->method('isVisibleFor')
			->willReturn(true);
		$backend = $this->getBackendMock();
		$backend->method('validateStorage')
			->willReturn(true);
		$backend->method('isVisibleFor')
			->willReturn(true);

		$storageConfig = new StorageConfig(255);
		$storageConfig->setMountPoint('mount');
		$storageConfig->setBackend($backend);
		$storageConfig->setAuthMechanism($authMech);
		$storageConfig->setBackendOptions([]);

		$this->service->expects($this->once())
			->method('createStorage')
			->will($this->returnValue($storageConfig));
		$this->service->expects($this->once())
			->method('updateStorage')
			->will($this->throwException(new NotFoundException()));

		$response = $this->controller->update(
			255,
			'mount',
			'\OC\Files\Storage\SMB',
			'\OCA\Files_External\Lib\Auth\NullMechanism',
			array(),
			[],
			[],
			[],
			null
		);

		$this->assertEquals(Http::STATUS_NOT_FOUND, $response->getStatus());
	}

	public function testDeleteStorage() {
		$this->service->expects($this->once())
			->method('removeStorage');

		$response = $this->controller->destroy(1);
		$this->assertEquals(Http::STATUS_NO_CONTENT, $response->getStatus());
	}

	public function testDeleteStorageNonExisting() {
		$this->service->expects($this->once())
			->method('removeStorage')
			->will($this->throwException(new NotFoundException()));

		$response = $this->controller->destroy(255);
		$this->assertEquals(Http::STATUS_NOT_FOUND, $response->getStatus());
	}

	public function testGetStorage() {
		$backend = $this->getBackendMock();
		$authMech = $this->getAuthMechMock();
		$storageConfig = new StorageConfig(1);
		$storageConfig->setMountPoint('test');
		$storageConfig->setBackend($backend);
		$storageConfig->setAuthMechanism($authMech);
		$storageConfig->setBackendOptions(['user' => 'test', 'password', 'password123']);
		$storageConfig->setMountOptions(['priority' => false]);

		$this->service->expects($this->once())
			->method('getStorage')
			->with(1)
			->will($this->returnValue($storageConfig));
		$response = $this->controller->show(1);

		$this->assertEquals(Http::STATUS_OK, $response->getStatus());
		$this->assertEquals($storageConfig, $response->getData());
	}

	public function validateStorageProvider() {
		return [
			[true, true, true],
			[false, true, false],
			[true, false, false],
			[false, false, false]
		];
	}

	/**
	 * @dataProvider validateStorageProvider
	 */
	public function testValidateStorage($backendValidate, $authMechValidate, $expectSuccess) {
		$backend = $this->getBackendMock();
		$backend->method('validateStorage')
			->willReturn($backendValidate);
		$backend->method('isVisibleFor')
			->willReturn(true);

		$authMech = $this->getAuthMechMock();
		$authMech->method('validateStorage')
			->will($this->returnValue($authMechValidate));
		$authMech->method('isVisibleFor')
			->willReturn(true);

		$storageConfig = new StorageConfig();
		$storageConfig->setMountPoint('mount');
		$storageConfig->setBackend($backend);
		$storageConfig->setAuthMechanism($authMech);
		$storageConfig->setBackendOptions([]);

		$this->service->expects($this->once())
			->method('createStorage')
			->will($this->returnValue($storageConfig));

		if ($expectSuccess) {
			$this->service->expects($this->once())
				->method('addStorage')
				->with($storageConfig)
				->will($this->returnValue($storageConfig));
		} else {
			$this->service->expects($this->never())
				->method('addStorage');
		}

		$response = $this->controller->create(
			'mount',
			'\OC\Files\Storage\SMB',
			'\OCA\Files_External\Lib\Auth\NullMechanism',
			array(),
			[],
			[],
			[],
			null
		);

		if ($expectSuccess) {
			$this->assertEquals(Http::STATUS_CREATED, $response->getStatus());
		} else {
			$this->assertEquals(Http::STATUS_UNPROCESSABLE_ENTITY, $response->getStatus());
		}
	}

}
