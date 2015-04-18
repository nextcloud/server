<?php
/**
 * @author Olivier Paroz <owncloud@interfasys.ch>
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
use OCA\Files_sharing\Tests\TestCase;

class Test_Files_Sharing_ReadOnly_Cache extends TestCase {

	/**
	 * @type \OC\Files\Storage\Storage
	 */
	protected $storage;

	/**
	 * @type OC\Files\Storage\StorageFactory
	 */
	protected $loader;

	/**
	 * @type OC\Files\Mount\MountPoint
	 */
	protected $readOnlyMount;

	/**
	 * @type \OCA\Files_Sharing\ReadOnlyWrapper
	 */
	protected $readOnlyStorage;

	/**
	 * @type \OC\Files\Cache\Cache
	 */
	protected $readOnlyCache;

	protected function setUp() {
		parent::setUp();

		//error_reporting(E_ERROR | E_PARSE);

		$this->view->mkdir('readonly');
		$this->view->file_put_contents('readonly/foo.txt', 'foo');
		$this->view->file_put_contents('readonly/bar.txt', 'bar');

		list($this->storage) = $this->view->resolvePath('');
		$this->loader = new \OC\Files\Storage\StorageFactory();
		$this->readOnlyMount = new \OC\Files\Mount\MountPoint($this->storage,
			'/readonly', [[]], $this->loader);
		$this->readOnlyStorage = $this->loader->getInstance($this->readOnlyMount,
			'\OCA\Files_Sharing\ReadOnlyWrapper', ['storage' => $this->storage]);

		$this->readOnlyCache = $this->readOnlyStorage->getCache();
	}

	protected function tearDown() {
		parent::tearDown();
	}

	public function testSetup() {
		$this->assertTrue($this->view->file_exists('/readonly/foo.txt'));

		$perms = $this->readOnlyStorage->getPermissions('files/readonly/foo.txt');
		$this->assertEquals(17, $perms);

		$this->assertFalse($this->readOnlyStorage->unlink('files/readonly/foo.txt'));
		$this->assertTrue($this->readOnlyStorage->file_exists('files/readonly/foo.txt'));

		$this->assertInstanceOf('\OCA\Files_Sharing\ReadOnlyCache', $this->readOnlyCache);
	}

	public function testGet() {
		$result = $this->readOnlyCache->get('files/readonly/foo.txt');
		$this->assertNotEmpty($result);

		$result = $this->readOnlyCache->get('files/readonly/proof does not exist.md');
		$this->assertFalse($result);
	}

	public function testGetFolderContents() {
		$results = $this->readOnlyCache->getFolderContents('files/readonly');
		$this->assertNotEmpty($results);

		foreach ($results as $result) {
			$this->assertNotEmpty($result);
		}

		$results = $this->readOnlyCache->getFolderContents('files/iamaghost');
		$this->assertEmpty($results);
	}

}
