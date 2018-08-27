<?php
/**
 * @copyright Copyright (c) 2018 Robin Appelman <robin@icewind.nl>
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

namespace OCA\DAV\Tests\Paginate;

use OCA\DAV\Paginate\PaginateCache;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Security\ISecureRandom;
use Test\TestCase;

/**
 * @group DB
 */
class PaginateCacheTest extends TestCase {
	/** @var PaginateCache */
	private $cache;

	/** @var ITimeFactory|\PHPUnit_Framework_MockObject_MockObject */
	private $timeFactory;

	/** @var ISecureRandom|\PHPUnit_Framework_MockObject_MockObject */
	private $random;

	const SAMPLE_DATA = [
		['foo'],
		['bar'],
		['asd']
	];
	
	const URL = 'http://example.com';

	private $now = 1000;

	protected function setUp() {
		parent::setUp();

		$count = 0;

		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->timeFactory->expects($this->any())
			->method('getTime')
			->willReturnCallback(function() {
				return $this->now;
			});
		$this->random = $this->createMock(ISecureRandom::class);
		$this->random->expects($this->any())
			->method('generate')
			->willReturnCallback(function () use (&$count) {
				$count++;
				return 'random_value_' . $count;
			});
		$this->cache = new PaginateCache(
			\OC::$server->getDatabaseConnection(),
			$this->random,
			$this->timeFactory
		);
	}

	protected function tearDown() {
		$this->cache->clear();

		return parent::tearDown();
	}

	public function testBasic() {
		list($token, $count) = $this->cache->store(self::URL, new \ArrayIterator(self::SAMPLE_DATA));
		$this->assertEquals(count(self::SAMPLE_DATA), $count);

		$this->assertEquals(self::SAMPLE_DATA, $this->cache->get(self::URL, $token, 0, 5));

		$this->assertEquals(array_slice(self::SAMPLE_DATA, 1, 1), $this->cache->get(self::URL, $token, 1, 1));
	}

	public function testWrongUrl() {
		list($token) = $this->cache->store(self::URL, new \ArrayIterator(self::SAMPLE_DATA));

		$this->assertCount(0, $this->cache->get('http://other.url', $token, 0, 5));
	}

	public function testWrongToken() {
		$this->cache->store(self::URL, new \ArrayIterator(self::SAMPLE_DATA));

		$this->assertCount(0, $this->cache->get(self::URL, 'wrong_token', 0, 5));
	}

	public function testCleanup() {
		$this->now = 1000;
		list($token1) = $this->cache->store(self::URL, new \ArrayIterator(self::SAMPLE_DATA));

		$this->now = 2000;
		list($token2) = $this->cache->store(self::URL, new \ArrayIterator(self::SAMPLE_DATA));

		$this->assertCount(count(self::SAMPLE_DATA), $this->cache->get(self::URL, $token1, 0, 5));
		$this->assertCount(count(self::SAMPLE_DATA), $this->cache->get(self::URL, $token2, 0, 5));

		$this->now = 1500 + PaginateCache::TTL;

		$this->cache->cleanup();

		$this->assertCount(0, $this->cache->get(self::URL, $token1, 0, 5));
		$this->assertCount(count(self::SAMPLE_DATA), $this->cache->get(self::URL, $token2, 0, 5));
	}
}