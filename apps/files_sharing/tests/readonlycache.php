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
namespace OCA\Files_Sharing\Tests;

class ReadOnlyCache extends TestCase {

	/** @var \OC\Files\Storage\Storage */
	protected $storage;

	/** @var \OC\Files\Storage\StorageFactory */
	protected $loader;

	/** @var \OC\Files\Mount\MountPoint */
	protected $readOnlyMount;

	/** @var \OCA\Files_Sharing\ReadOnlyWrapper */
	protected $readOnlyStorage;

	/** @var \OC\Files\Cache\Cache */
	protected $readOnlyCache;

	protected function setUp() {
		parent::setUp();

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

	public function testSetup() {
		$this->assertTrue($this->view->file_exists('/readonly/foo.txt'));

		$perms = $this->readOnlyStorage->getPermissions('files/readonly/foo.txt');
		$this->assertEquals(17, $perms);

		$this->assertFalse($this->readOnlyStorage->unlink('files/readonly/foo.txt'));
		$this->assertTrue($this->readOnlyStorage->file_exists('files/readonly/foo.txt'));

		$this->assertInstanceOf('\OCA\Files_Sharing\ReadOnlyCache', $this->readOnlyCache);
	}

	public function testGetWhenFileExists() {
		$result = $this->readOnlyCache->get('files/readonly/foo.txt');
		$this->assertNotEmpty($result);
	}

	public function testGetWhenFileDoesNotExist() {
		$result = $this->readOnlyCache->get('files/readonly/proof does not exist.md');
		$this->assertFalse($result);
	}

	public function testGetFolderContentsWhenFolderExists() {
		$results = $this->readOnlyCache->getFolderContents('files/readonly');
		$this->assertNotEmpty($results);

		foreach ($results as $result) {
			$this->assertNotEmpty($result);
		}
	}

	public function testGetFolderContentsWhenFolderDoesNotExist() {
		$results = $this->readOnlyCache->getFolderContents('files/iamaghost');
		$this->assertEmpty($results);
	}

}
