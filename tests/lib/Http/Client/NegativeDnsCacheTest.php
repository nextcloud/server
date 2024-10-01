<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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

		$this->negativeDnsCache->setNegativeCacheForDnsType('www.example.com', DNS_A, 3600);
	}

	public function testIsNegativeCached(): void {
		$this->cache
			->expects($this->once())
			->method('hasKey')
			->with('www.example.com-1')
			->willReturn(true);

		$this->assertTrue($this->negativeDnsCache->isNegativeCached('www.example.com', DNS_A));
	}
}
