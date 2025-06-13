<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\Memcache;

use OC\Memcache\ArrayCache;
use Test\TestCase;

/**
 * @group Memcache
 */
class CasTraitTest extends TestCase {
	/**
	 * @return \OC\Memcache\CasTrait
	 */
	private function getCache() {
		$sourceCache = new ArrayCache();
		$mock = $this->getMockForTrait('\OC\Memcache\CasTrait');

		$mock->expects($this->any())
			->method('set')
			->willReturnCallback(function ($key, $value, $ttl) use ($sourceCache) {
				return $sourceCache->set($key, $value, $ttl);
			});

		$mock->expects($this->any())
			->method('get')
			->willReturnCallback(function ($key) use ($sourceCache) {
				return $sourceCache->get($key);
			});

		$mock->expects($this->any())
			->method('add')
			->willReturnCallback(function ($key, $value, $ttl) use ($sourceCache) {
				return $sourceCache->add($key, $value, $ttl);
			});

		$mock->expects($this->any())
			->method('remove')
			->willReturnCallback(function ($key) use ($sourceCache) {
				return $sourceCache->remove($key);
			});
		return $mock;
	}

	public function testCasNotChanged(): void {
		$cache = $this->getCache();
		$cache->set('foo', 'bar');
		$this->assertTrue($cache->cas('foo', 'bar', 'asd'));
		$this->assertEquals('asd', $cache->get('foo'));
	}

	public function testCasChanged(): void {
		$cache = $this->getCache();
		$cache->set('foo', 'bar1');
		$this->assertFalse($cache->cas('foo', 'bar', 'asd'));
		$this->assertEquals('bar1', $cache->get('foo'));
	}
}
