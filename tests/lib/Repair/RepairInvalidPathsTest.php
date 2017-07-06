<?php
/**
 * @copyright Copyright (c) 2017 Robin Appelman <robin@icewind.nl>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace Test\Repair;

use OC\Files\Cache\Cache;
use OC\Files\Storage\Temporary;
use OC\Repair\NC13\RepairInvalidPaths;
use OCP\IConfig;
use OCP\Migration\IOutput;
use Test\TestCase;

/**
 * @group DB
 */
class RepairInvalidPathsTest extends TestCase {
	/** @var Temporary */
	private $storage;
	/** @var Cache */
	private $cache;
	/** @var RepairInvalidPaths */
	private $repair;

	protected function setUp() {
		parent::setUp();

		$this->storage = new Temporary();
		$this->cache = $this->storage->getCache();
		$config = $this->createMock(IConfig::class);
		$config->expects($this->any())
			->method('getSystemValue')
			->with('version', '0.0.0')
			->willReturn('12.0.0.0');
		$this->repair = new RepairInvalidPaths(\OC::$server->getDatabaseConnection(), $config);
	}

	protected function tearDown() {
		$this->cache->clear();

		return parent::tearDown();
	}

	public function testRepairNonDuplicate() {
		$this->storage->mkdir('foo/bar/asd');
		$this->storage->mkdir('foo2');
		$this->storage->getScanner()->scan('');

		$folderId = $this->cache->getId('foo/bar');
		$newParentFolderId = $this->cache->getId('foo2');
		// failed rename, moved entry is updated but not it's children
		$this->cache->update($folderId, ['path' => 'foo2/bar', 'parent' => $newParentFolderId]);

		$this->assertTrue($this->cache->inCache('foo2/bar'));
		$this->assertTrue($this->cache->inCache('foo/bar/asd'));
		$this->assertFalse($this->cache->inCache('foo2/bar/asd'));

		$this->assertEquals($folderId, $this->cache->get('foo/bar/asd')['parent']);

		$this->repair->run($this->createMock(IOutput::class));

		$this->assertTrue($this->cache->inCache('foo2/bar'));
		$this->assertTrue($this->cache->inCache('foo2/bar/asd'));
		$this->assertFalse($this->cache->inCache('foo/bar/asd'));

		$this->assertEquals($folderId, $this->cache->get('foo2/bar/asd')['parent']);
		$this->assertEquals($folderId, $this->cache->getId('foo2/bar'));
	}

	public function testRepairDuplicate() {
		$this->storage->mkdir('foo/bar/asd');
		$this->storage->mkdir('foo2');
		$this->storage->getScanner()->scan('');

		$folderId = $this->cache->getId('foo/bar');
		$newParentFolderId = $this->cache->getId('foo2');
		// failed rename, moved entry is updated but not it's children
		$this->cache->update($folderId, ['path' => 'foo2/bar', 'parent' => $newParentFolderId]);
		$this->storage->rename('foo/bar', 'foo2/bar');
		$this->storage->mkdir('foo2/bar/asd/foo');

		// usage causes the renamed subfolder to be scanned
		$this->storage->getScanner()->scan('foo2/bar/asd');

		$this->assertTrue($this->cache->inCache('foo2/bar'));
		$this->assertTrue($this->cache->inCache('foo/bar/asd'));
		$this->assertTrue($this->cache->inCache('foo2/bar/asd'));

		$this->assertEquals($folderId, $this->cache->get('foo/bar/asd')['parent']);

		$this->repair->run($this->createMock(IOutput::class));

		$this->assertTrue($this->cache->inCache('foo2/bar'));
		$this->assertTrue($this->cache->inCache('foo2/bar/asd'));
		$this->assertFalse($this->cache->inCache('foo/bar/asd'));

		$this->assertEquals($this->cache->getId('foo2/bar'), $this->cache->get('foo2/bar/asd')['parent']);
		$this->assertEquals($this->cache->getId('foo2/bar/asd'), $this->cache->get('foo2/bar/asd/foo')['parent']);
	}
}
