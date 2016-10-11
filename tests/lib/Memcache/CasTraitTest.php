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

namespace Test\Memcache;

use Test\TestCase;

class CasTraitTest extends TestCase {
	/**
	 * @return \OC\Memcache\CasTrait
	 */
	private function getCache() {
		$sourceCache = new \OC\Memcache\ArrayCache();
		$mock = $this->getMockForTrait('\OC\Memcache\CasTrait');

		$mock->expects($this->any())
			->method('set')
			->will($this->returnCallback(function ($key, $value, $ttl) use ($sourceCache) {
				return $sourceCache->set($key, $value, $ttl);
			}));

		$mock->expects($this->any())
			->method('get')
			->will($this->returnCallback(function ($key) use ($sourceCache) {
				return $sourceCache->get($key);
			}));

		$mock->expects($this->any())
			->method('add')
			->will($this->returnCallback(function ($key, $value, $ttl) use ($sourceCache) {
				return $sourceCache->add($key, $value, $ttl);
			}));

		$mock->expects($this->any())
			->method('remove')
			->will($this->returnCallback(function ($key) use ($sourceCache) {
				return $sourceCache->remove($key);
			}));
		return $mock;
	}

	public function testCasNotChanged() {
		$cache = $this->getCache();
		$cache->set('foo', 'bar');
		$this->assertTrue($cache->cas('foo', 'bar', 'asd'));
		$this->assertEquals('asd', $cache->get('foo'));
	}

	public function testCasChanged() {
		$cache = $this->getCache();
		$cache->set('foo', 'bar1');
		$this->assertFalse($cache->cas('foo', 'bar', 'asd'));
		$this->assertEquals('bar1', $cache->get('foo'));
	}
}
