<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Files\Cache\Wrapper;

use Closure;
use OCP\Files\Cache\ICache;
use OCP\Files\Cache\ICacheEntry;

class CacheDirPermissionsMask extends CachePermissionsMask {
	/**
	 * @param Closure(string $path): bool $checkPath
	 */
	public function __construct(
		ICache $cache,
		int $mask,
		private readonly Closure $checkPath,
	) {
		parent::__construct($cache, $mask);
	}

	protected function formatCacheEntry($entry): ICacheEntry|false {
		$checkPath = $this->checkPath;
		if ($checkPath($entry['path'])) {
			return parent::formatCacheEntry($entry);
		}

		return $entry;
	}
}
