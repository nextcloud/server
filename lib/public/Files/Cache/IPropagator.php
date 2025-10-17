<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCP\Files\Cache;

use OCP\AppFramework\Attribute\Consumable;

/**
 * Propagate ETags and mtimes within the storage.
 *
 * @since 9.0.0
 */
#[Consumable(since: '9.0.0')]
interface IPropagator {
	/**
	 * Mark the beginning of a propagation batch.
	 *
	 * Note that not all cache setups support propagation in which case this will be a noop
	 *
	 * Batching for cache setups that do support it has to be explicit since the cache state is not fully consistent
	 * before the batch is committed.
	 *
	 * @since 9.1.0
	 */
	public function beginBatch(): void;

	/**
	 * Commit the active propagation batch.
	 *
	 * @since 9.1.0
	 */
	public function commitBatch(): void;

	/**
	 * @param string $internalPath
	 * @param int $time
	 * @param int $sizeDifference The number of bytes the file has grown.
	 * @since 9.0.0
	 */
	public function propagateChange(string $internalPath, int $time, int $sizeDifference = 0): void;
}
