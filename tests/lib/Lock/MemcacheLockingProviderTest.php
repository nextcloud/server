<?php
/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\Lock;

use OC\Memcache\ArrayCache;
use OCP\AppFramework\Utility\ITimeFactory;

class MemcacheLockingProviderTest extends LockingProvider {
	/**
	 * @var \OCP\IMemcache
	 */
	private $memcache;

	/**
	 * @return \OCP\Lock\ILockingProvider
	 */
	protected function getInstance() {
		$this->memcache = new ArrayCache();
		$timeProvider = \OC::$server->get(ITimeFactory::class);
		return new \OC\Lock\MemcacheLockingProvider($this->memcache, $timeProvider);
	}

	protected function tearDown(): void {
		$this->memcache->clear();
		parent::tearDown();
	}
}
