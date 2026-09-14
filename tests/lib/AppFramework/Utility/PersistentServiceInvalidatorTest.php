<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\AppFramework\Utility;

use OC\AppFramework\Utility\PersistentServiceInvalidator;
use OC\Memcache\ArrayCache;
use OCP\AppFramework\Utility\PersistentServiceGroup;
use OCP\ICacheFactory;

class PersistentServiceInvalidatorTest extends \Test\TestCase {
	private ArrayCache $cache;
	private PersistentServiceInvalidator $invalidator;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->cache = new ArrayCache();
		$cacheFactory = $this->createMock(ICacheFactory::class);
		$cacheFactory->method('createDistributed')
			->willReturn($this->cache);

		$this->invalidator = new PersistentServiceInvalidator($cacheFactory);
	}

	public function testGenerationStartsAtZero(): void {
		$this->assertSame(0, $this->invalidator->getGeneration('apps'));
	}

	public function testInvalidateBumpsTheGeneration(): void {
		$this->invalidator->invalidate('apps');
		$this->assertSame(1, $this->invalidator->getGeneration('apps'));

		$this->invalidator->invalidate('apps');
		$this->assertSame(2, $this->invalidator->getGeneration('apps'));
	}

	public function testGroupsAreIndependent(): void {
		$this->invalidator->invalidate('apps');

		$this->assertSame(1, $this->invalidator->getGeneration('apps'));
		$this->assertSame(0, $this->invalidator->getGeneration('custom-group'));
	}

	public function testEnumGroupIsEquivalentToItsStringValue(): void {
		$this->invalidator->invalidate(PersistentServiceGroup::Apps);

		$this->assertSame(1, $this->invalidator->getGeneration('apps'));
		$this->assertSame(1, $this->invalidator->getGeneration(PersistentServiceGroup::Apps));
	}
}
