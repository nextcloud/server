<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Http\Client;

use OC\Http\Client\NegativeDnsCache;

class NegativeDnsCacheTest extends \Test\TestCase {
	private NegativeDnsCache $negativeDnsCache;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->negativeDnsCache = $this->createInstanceWithMocks(NegativeDnsCache::class);
	}

	public function testSetNegativeCacheForDnsType() : void {
		$this->getCacheAutoMock('NegativeDnsCache')
			->expects($this->once())
			->method('set')
			->with('www.example.com-1', 'true', 3600);

		$this->negativeDnsCache->setNegativeCacheForDnsType('www.example.com', DNS_A, 3600);
	}

	public function testIsNegativeCached(): void {
		$this->getCacheAutoMock('NegativeDnsCache')
			->expects($this->once())
			->method('hasKey')
			->with('www.example.com-1')
			->willReturn(true);

		$this->assertTrue($this->negativeDnsCache->isNegativeCached('www.example.com', DNS_A));
	}
}
