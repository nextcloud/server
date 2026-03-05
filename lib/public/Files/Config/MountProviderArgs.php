<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Files\Config;

use OCP\Files\Cache\ICacheEntry;

/**
 * Data-class containing information related to a mount and its root.
 *
 * @since 33.0.0
 */
class MountProviderArgs {
	public function __construct(
		public readonly ICachedMountInfo $mountInfo,
		public readonly ICacheEntry $cacheEntry,
	) {
	}
}
