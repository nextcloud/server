<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2021, Lukas Reschke <lukas@statuscode.ch>
 *
 * @author Lukas Reschke <lukas@statuscode.ch>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace Test\Http\Client;

use OC\Http\Client\NegativeDnsCache;
use OCP\ICache;
use OCP\ICacheFactory;

class NegativeDnsCacheTest extends \Test\TestCase {
	/** @var ICache */
	private $cache;
	/** @var ICacheFactory */
	private $cacheFactory;
	/** @var NegativeDnsCache */
	private $negativeDnsCache;

	protected function setUp(): void {
		parent::setUp();

		$this->cache = $this->createMock(ICache::class);
		$this->cacheFactory = $this->createMock(ICacheFactory::class);
		$this->cacheFactory
			->method('createLocal')
			->with('NegativeDnsCache')
			->willReturn($this->cache);
		
		$this->negativeDnsCache = new NegativeDnsCache($this->cacheFactory);
	}

	public function testSetNegativeCacheForDnsType() : void {
		$this->cache
			->expects($this->once())
			->method('set')
			->with('www.example.com-1', 'true', 3600);

		$this->negativeDnsCache->setNegativeCacheForDnsType("www.example.com", DNS_A, 3600);
	}

	public function testIsNegativeCached() {
		$this->cache
			->expects($this->once())
			->method('hasKey')
			->with('www.example.com-1')
			->willReturn(true);

		$this->assertTrue($this->negativeDnsCache->isNegativeCached("www.example.com", DNS_A));
	}
}
