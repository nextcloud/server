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
namespace OCA\Files_external\Tests\Service;

use \OC\Files\Filesystem;

use \OCA\Files_external\NotFoundException;
use \OCA\Files_external\Lib\StorageConfig;

abstract class StoragesServiceTest extends \Test\TestCase {

	/**
	 * @var StoragesService
	 */
	protected $service;

	/**
	 * Data directory
	 *
	 * @var string
	 */
	protected $dataDir;

	/**
	 * Hook calls
	 *
	 * @var array
	 */
	protected static $hookCalls;

	public function setUp() {
		self::$hookCalls = array();
		$config = \OC::$server->getConfig();
		$this->dataDir = $config->getSystemValue(
			'datadirectory',
			\OC::$SERVERROOT . '/data/'
		);
		\OC_Mount_Config::$skipTest = true;

		\OCP\Util::connectHook(
			Filesystem::CLASSNAME,
			Filesystem::signal_create_mount,
			get_class($this), 'createHookCallback');
		\OCP\Util::connectHook(
			Filesystem::CLASSNAME,
			Filesystem::signal_delete_mount,
			get_class($this), 'deleteHookCallback');

	}

	public function tearDown() {
		\OC_Mount_Config::$skipTest = false;
		self::$hookCalls = array();
	}

	/**
	 * Creates a StorageConfig instance based on array data
	 *
	 * @param array data
	 *
	 * @return StorageConfig storage config instance
	 */
	protected function makeStorageConfig($data) {
		$storage = new StorageConfig();
		if (isset($data['id'])) {
			$storage->setId($data['id']);
		}
		$storage->setMountPoint($data['mountPoint']);
		$storage->setBackendClass($data['backendClass']);
		$storage->setBackendOptions($data['backendOptions']);
		if (isset($data['applicableUsers'])) {
			$storage->setApplicableUsers($data['applicableUsers']);
		}
		if (isset($data['applicableGroups'])) {
			$storage->setApplicableGroups($data['applicableGroups']);
		}
		if (isset($data['priority'])) {
			$storage->setPriority($data['priority']);
		}
		if (isset($data['mountOptions'])) {
			$storage->setMountOptions($data['mountOptions']);
		}
		return $storage;
	}


	/**
	 * @expectedException \OCA\Files_external\NotFoundException
	 */
	public function testNonExistingStorage() {
		$storage = new StorageConfig(255);
		$storage->setMountPoint('mountpoint');
		$storage->setBackendClass('\OC\Files\Storage\SMB');
		$this->service->updateStorage($storage);
	}

	public function testDeleteStorage() {
		$storage = new StorageConfig(255);
		$storage->setMountPoint('mountpoint');
		$storage->setBackendClass('\OC\Files\Storage\SMB');
		$storage->setBackendOptions(['password' => 'testPassword']);

		$newStorage = $this->service->addStorage($storage);
		$this->assertEquals(1, $newStorage->getId());

		$newStorage = $this->service->removeStorage(1);

		$caught = false;
		try {
			$this->service->getStorage(1);
		} catch (NotFoundException $e) {
			$caught = true;
		}

		$this->assertTrue($caught);
	}

	/**
	 * @expectedException \OCA\Files_external\NotFoundException
	 */
	public function testDeleteUnexistingStorage() {
		$this->service->removeStorage(255);
	}

	public static function createHookCallback($params) {
		self::$hookCalls[] = array(
			'signal' => Filesystem::signal_create_mount,
			'params' => $params
		);
	}

	public static function deleteHookCallback($params) {
		self::$hookCalls[] = array(
			'signal' => Filesystem::signal_delete_mount,
			'params' => $params
		);
	}

	/**
	 * Asserts hook call
	 *
	 * @param array $callData hook call data to check
	 * @param string $signal signal name
	 * @param string $mountPath mount path
	 * @param string $mountType mount type
	 * @param string $applicable applicable users
	 */
	protected function assertHookCall($callData, $signal, $mountPath, $mountType, $applicable) {
		$this->assertEquals($signal, $callData['signal']);
		$params = $callData['params'];
		$this->assertEquals(
			$mountPath,
			$params[Filesystem::signal_param_path]
		);
		$this->assertEquals(
			$mountType,
			$params[Filesystem::signal_param_mount_type]
		);
		$this->assertEquals(
			$applicable,
			$params[Filesystem::signal_param_users]
		);
	}
}
