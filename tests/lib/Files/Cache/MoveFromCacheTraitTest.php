<?php
/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\Cache;

use OC\Files\Cache\Cache;
use OC\Files\Cache\MoveFromCacheTrait;
use OC\Files\Storage\Temporary;
use OCP\Files\Cache\ICacheEntry;

class FallBackCrossCacheMoveCache extends Cache {
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

		$this->storage = new Temporary([]);
		$this->storage2 = new Temporary([]);
		$this->cache = new FallBackCrossCacheMoveCache($this->storage);
		$this->cache2 = new FallBackCrossCacheMoveCache($this->storage2);

		$this->cache->insert('', ['size' => 0, 'mtime' => 0, 'mimetype' => ICacheEntry::DIRECTORY_MIMETYPE]);
		$this->cache2->insert('', ['size' => 0, 'mtime' => 0, 'mimetype' => ICacheEntry::DIRECTORY_MIMETYPE]);
	}
}
