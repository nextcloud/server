<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Files\Cache\Wrapper;

class CachePermissionsMask extends CacheWrapper {
	/**
	 * @var int
	 */
	protected $mask;

	/**
	 * @param \OCP\Files\Cache\ICache $cache
	 * @param int $mask
	 */
	public function __construct($cache, $mask) {
		parent::__construct($cache);
		$this->mask = $mask;
	}

	protected function formatCacheEntry($entry) {
		if (isset($entry['permissions'])) {
			$entry['scan_permissions'] = $entry['permissions'];
			$entry['permissions'] &= $this->mask;
		}
		return $entry;
	}
}
