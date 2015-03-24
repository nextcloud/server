<?php
/**
 * @author Robin Appelman <icewind@owncloud.com>
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
namespace OCA\Files_sharing\Tests\External;

use OC\Files\Storage\StorageFactory;
use OCA\Files_Sharing\Tests\TestCase;

class Manager extends TestCase {
	private $uid;

	/**
	 * @var \OC\Files\Mount\Manager
	 */
	private $mountManager;

	/**
	 * @var \OCA\Files_Sharing\External\Manager
	 */
	private $instance;

	public function setUp() {
		parent::setUp();

		$this->uid = $this->getUniqueID('user');

		$this->mountManager = new \OC\Files\Mount\Manager();
		$this->instance = new \OCA\Files_Sharing\External\Manager(
			\OC::$server->getDatabaseConnection(),
			$this->mountManager,
			new StorageFactory(),
			$this->getMockBuilder('\OC\HTTPHelper')->disableOriginalConstructor()->getMock(),
			$this->uid
		);
	}

	public function tearDown() {
		$this->instance->removeUserShares($this->uid);
		parent::tearDown();
	}

	private function getFullPath($path) {
		return '/' . $this->uid . '/files' . $path;
	}

	private function assertMount($mountPoint) {
		$mountPoint = rtrim($mountPoint, '/');
		$mount = $this->mountManager->find($this->getFullPath($mountPoint));
		$this->assertInstanceOf('\OCP\Files\Mount\IMountPoint', $mount);
		$this->assertEquals($this->getFullPath($mountPoint), rtrim($mount->getMountPoint(), '/'));
		$storage = $mount->getStorage();
		$this->assertInstanceOf('\OCA\Files_Sharing\External\Storage', $storage);
	}

	private function assertNotMount($mountPoint) {
		$mountPoint = rtrim($mountPoint, '/');
		$mount = $this->mountManager->find($this->getFullPath($mountPoint));
		if ($mount) {
			$this->assertInstanceOf('\OCP\Files\Mount\IMountPoint', $mount);
			$this->assertNotEquals($this->getFullPath($mountPoint), rtrim($mount->getMountPoint(), '/'));
		} else {
			$this->assertNull($mount);
		}
	}

	public function testAddBasic() {
		$this->instance->addShare('http://example.com', 'foo', 'bar', 'example', 'me', true);
		\Test_Helper::invokePrivate($this->instance, 'setupMounts');
		$this->assertMount('/example');
	}

	public function testAddBasicEmptyPassword() {
		$this->instance->addShare('http://example.com', 'foo', '', 'example', 'me', true);
		\Test_Helper::invokePrivate($this->instance, 'setupMounts');
		$this->assertMount('/example');
	}

	public function testAddNotAcceptedShare() {
		$this->instance->addShare('http://example.com', 'foo', 'bar', 'example', 'me', false);
		\Test_Helper::invokePrivate($this->instance, 'setupMounts');
		$this->assertNotMount('/example');
	}

	public function testAcceptMount() {
		$this->instance->addShare('http://example.com', 'foo', 'bar', 'example', 'me', false);
		$open = $this->instance->getOpenShares();
		$this->assertCount(1, $open);
		$this->instance->acceptShare($open[0]['id']);
		$this->assertEquals([], $this->instance->getOpenShares());
		\Test_Helper::invokePrivate($this->instance, 'setupMounts');
		$this->assertMount('/example');
	}

	public function testDeclineMount() {
		$this->instance->addShare('http://example.com', 'foo', 'bar', 'example', 'me', false);
		$open = $this->instance->getOpenShares();
		$this->assertCount(1, $open);
		$this->instance->declineShare($open[0]['id']);
		$this->assertEquals([], $this->instance->getOpenShares());
		\Test_Helper::invokePrivate($this->instance, 'setupMounts');
		$this->assertNotMount('/example');
	}

	public function testSetMountPoint() {
		$this->instance->addShare('http://example.com', 'foo', 'bar', 'example', 'me', true);
		\Test_Helper::invokePrivate($this->instance, 'setupMounts');
		$this->assertMount('/example');
		$this->instance->setMountPoint($this->getFullPath('/example'), $this->getFullPath('/renamed'));
		$this->mountManager->clear();
		\Test_Helper::invokePrivate($this->instance, 'setupMounts');
		$this->assertMount('/renamed');
		$this->assertNotMount('/example');
	}

	public function testRemoveShare() {
		$this->instance->addShare('http://example.com', 'foo', 'bar', 'example', 'me', true);
		\Test_Helper::invokePrivate($this->instance, 'setupMounts');
		$this->assertMount('/example');
		$this->instance->removeShare($this->getFullPath('/example'));
		$this->mountManager->clear();
		\Test_Helper::invokePrivate($this->instance, 'setupMounts');
		$this->assertNotMount('/example');
	}

	public function testRemoveShareForUser() {
		$this->instance->addShare('http://example.com', 'foo', 'bar', 'example', 'me', true);
		\Test_Helper::invokePrivate($this->instance, 'setupMounts');
		$this->assertMount('/example');
		$this->instance->removeUserShares($this->uid);
		$this->mountManager->clear();
		\Test_Helper::invokePrivate($this->instance, 'setupMounts');
		$this->assertNotMount('/example');
	}
}
