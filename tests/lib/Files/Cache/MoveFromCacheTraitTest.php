<?php
/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\Cache;

use OC\Files\Cache\MoveFromCacheTrait;

class FallBackCrossCacheMoveCache extends \OC\Files\Cache\Cache {
	use MoveFromCacheTrait;
}

/**
 * Class MoveFromCacheTraitTest
 *
 * @group DB
 */
class MoveFromCacheTraitTest extends CacheTest {
	protected function setUp(): void {
		parent::setUp();

		$this->storage = new \OC\Files\Storage\Temporary([]);
		$this->storage2 = new \OC\Files\Storage\Temporary([]);
		$this->cache = new FallBackCrossCacheMoveCache($this->storage);
		$this->cache2 = new FallBackCrossCacheMoveCache($this->storage2);
	}
}
