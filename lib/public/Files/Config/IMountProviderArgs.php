<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Files\Config;

use OCP\Files\Cache\ICacheEntry;

class IMountProviderArgs {
	public function __construct(
		public ICachedMountInfo $mountInfo,
		public ICacheEntry $cacheEntry,
	) {
	}
}
