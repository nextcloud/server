<?php

declare(strict_types = 1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
		$this->oldEnv = getenv('PATH');
		// BinaryFinder always includes the "PATH" environment variable into the search path,
		// which we want to avoid in this test because they are not usually found in webserver
		// deployments.
		putenv('PATH=""');
		$this->cacheFactory = $this->createMock(ICacheFactory::class);
		$this->cache = new ArrayCache();
		$this->cacheFactory->method('createLocal')->with('findBinaryPath')->willReturn($this->cache);
	}

	protected function tearDown(): void {
		putenv('PATH=' . $this->oldEnv);
	}

	public function testDefaultFindsCat() {
		$config = $this->createMock(IConfig::class);
		$config
			->method('getSystemValue')
			->with('binary_search_paths', $this->anything())
			->willReturnCallback(function ($key, $default) {
				return $default;
			});
		$finder = new BinaryFinder($this->cacheFactory, $config);
		$this->assertEquals($finder->findBinaryPath('cat'), '/usr/bin/cat');
		$this->assertEquals($this->cache->get('cat'), '/usr/bin/cat');
	}

	public function testDefaultDoesNotFindCata() {
		$config = $this->createMock(IConfig::class);
		$config
			->method('getSystemValue')
			->with('binary_search_paths', $this->anything())
			->willReturnCallback(function ($key, $default) {
				return $default;
			});
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
