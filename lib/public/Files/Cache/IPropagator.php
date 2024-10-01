<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\Files\Cache;

/**
 * Propagate etags and mtimes within the storage
 *
 * @since 9.0.0
 */
interface IPropagator {
	/**
	 * Mark the beginning of a propagation batch
	 *
	 * Note that not all cache setups support propagation in which case this will be a noop
	 *
	 * Batching for cache setups that do support it has to be explicit since the cache state is not fully consistent
	 * before the batch is committed.
	 *
	 * @since 9.1.0
	 */
	public function beginBatch();

	/**
	 * Commit the active propagation batch
	 *
	 * @since 9.1.0
	 */
	public function commitBatch();

	/**
	 * @param string $internalPath
	 * @param int $time
	 * @param int $sizeDifference
	 * @since 9.0.0
	 */
	public function propagateChange($internalPath, $time, $sizeDifference = 0);
}
