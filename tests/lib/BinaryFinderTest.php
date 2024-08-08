<?php

declare(strict_types = 1);
/**
 * @copyright 2024 Reno Reckling <e-github@wthack.de>
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace Test;

use OC\BinaryFinder;
use OC\Memcache\ArrayCache;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IConfig;

class BinaryFinderTest extends TestCase {
	private ICache $cache;
	private ICacheFactory $cacheFactory;
	private $oldEnv;

	protected function setUp(): void {
		$this->oldEnv = getenv("PATH");
		// BinaryFinder always includes the "PATH" environment variable into the search path,
		// which we want to avoid in this test because they are not usually found in webserver
		// deployments.
		putenv('PATH=""');
		$this->cacheFactory = $this->createMock(ICacheFactory::class);
		$this->cache = new ArrayCache();
		$this->cacheFactory->method('createLocal')->with('findBinaryPath')->willReturn($this->cache);
	}

	protected function tearDown(): void {
		putenv('PATH='.$this->oldEnv);
	}

	public function testDefaultFindsCat() {
		$config = $this->createMock(IConfig::class);
		$config
			->method('getSystemValue')
			->with('binary_search_paths', $this->anything())
			->will($this->returnCallback(function ($key, $default) {
				return $default;
			}));
		$finder = new BinaryFinder($this->cacheFactory, $config);
		$this->assertEquals($finder->findBinaryPath('cat'), '/usr/bin/cat');
		$this->assertEquals($this->cache->get('cat'), '/usr/bin/cat');
	}

	public function testDefaultDoesNotFindCata() {
		$config = $this->createMock(IConfig::class);
		$config
			->method('getSystemValue')
			->with('binary_search_paths', $this->anything())
			->will($this->returnCallback(function ($key, $default) {
				return $default;
			}));
		$finder = new BinaryFinder($this->cacheFactory, $config);
		$this->assertFalse($finder->findBinaryPath('cata'));
		$this->assertFalse($this->cache->get('cata'));
	}

	public function testCustomPathFindsCat() {
		$config = $this->createMock(IConfig::class);
		$config
			->method('getSystemValue')
			->with('binary_search_paths', $this->anything())
			->willReturn(['/usr/bin']);
		$finder = new BinaryFinder($this->cacheFactory, $config);
		$this->assertEquals($finder->findBinaryPath('cat'), '/usr/bin/cat');
		$this->assertEquals($this->cache->get('cat'), '/usr/bin/cat');
	}

	public function testWrongCustomPathDoesNotFindCat() {
		$config = $this->createMock(IConfig::class);
		$config
			->method('getSystemValue')
			->with('binary_search_paths')
			->willReturn(['/wrong']);
		$finder = new BinaryFinder($this->cacheFactory, $config);
		$this->assertFalse($finder->findBinaryPath('cat'));
		$this->assertFalse($this->cache->get('cat'));
	}
}
