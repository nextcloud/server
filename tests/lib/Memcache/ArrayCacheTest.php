<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Memcache;

use OC\Memcache\ArrayCache;

/**
 * @group Memcache
 */
class ArrayCacheTest extends Cache {
	protected function setUp(): void {
		parent::setUp();
		$this->instance = new ArrayCache('');
	}
}
