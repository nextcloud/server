<?php
/**
 * ownCloud
 *
 * @author Robin Appelman
 * @copyright 2012 Robin Appelman icewind@owncloud.com
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

namespace Test\Cache;

use Test\TestCase;

class FileGlobalGC extends TestCase {
	/**
	 * @var string
	 */
	private $cacheDir;

	/**
	 * @var \OC\Cache\FileGlobalGC
	 */
	private $gc;

	public function setUp() {
		$this->cacheDir = \OC::$server->getTempManager()->getTemporaryFolder();
		$this->gc = new \OC\Cache\FileGlobalGC();
	}

	private function addCacheFile($name, $expire) {
		file_put_contents($this->cacheDir . $name, 'foo');
		touch($this->cacheDir . $name, $expire);
	}

	public function testGetExpiredEmpty() {
		$this->assertEquals([], $this->gc->getExpiredPaths($this->cacheDir, time()));
	}

	public function testGetExpiredNone() {
		$time = time();
		$this->addCacheFile('foo', $time + 10);
		$this->assertEquals([], $this->gc->getExpiredPaths($this->cacheDir, $time));
	}

	public function testGetExpired() {
		$time = time();
		$this->addCacheFile('foo', $time + 10);
		$this->addCacheFile('bar', $time);
		$this->addCacheFile('bar2', $time - 10);
		$this->addCacheFile('asd', $time - 100);
		$this->assertEquals([$this->cacheDir . 'asd', $this->cacheDir . 'bar2'], $this->gc->getExpiredPaths($this->cacheDir, $time));
	}

	public function testGetExpiredDirectory() {
		$time = time();
		$this->addCacheFile('foo', $time - 10);
		mkdir($this->cacheDir . 'asd');
		$this->assertEquals([$this->cacheDir . 'foo'], $this->gc->getExpiredPaths($this->cacheDir, $time));
	}

	public function testGcUnlink() {
		$time = time();
		$this->addCacheFile('foo', $time - 10);
		$this->addCacheFile('bar', $time - 10);
		$this->addCacheFile('asd', $time + 10);

		$config = $this->getMock('\OCP\IConfig');
		$config->expects($this->once())
			->method('getAppValue')
			->with('core', 'global_cache_gc_lastrun', 0)
			->willReturn($time - \OC\Cache\FileGlobalGC::CLEANUP_TTL_SEC - 1);
		$config->expects($this->once())
			->method('setAppValue');

		$this->gc->gc($config, $this->cacheDir);
		$this->assertFileNotExists($this->cacheDir . 'foo');
		$this->assertFileNotExists($this->cacheDir . 'bar');
		$this->assertFileExists($this->cacheDir . 'asd');
	}

	public function testGcLastRun() {
		$time = time();

		$config = $this->getMock('\OCP\IConfig');
		$config->expects($this->once())
			->method('getAppValue')
			->with('core', 'global_cache_gc_lastrun', 0)
			->willReturn($time);
		$config->expects($this->never())
			->method('setAppValue');

		$this->gc->gc($config, $this->cacheDir);
	}
}
