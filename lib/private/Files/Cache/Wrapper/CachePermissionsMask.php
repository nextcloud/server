<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Files\Cache\Wrapper;

use OCP\Files\Cache\ICache;

class CachePermissionsMask extends CacheWrapper {
	/**
	 * @param ICache $cache
	 * @param int $mask
	 */
	public function __construct(
		$cache,
		protected $mask,
	) {
		parent::__construct($cache);
	}

	protected function formatCacheEntry($entry) {
		if (isset($entry['permissions'])) {
			$entry['scan_permissions'] ??= $entry['permissions'];
			$entry['permissions'] &= $this->mask;
		}
		return $entry;
	}
}
