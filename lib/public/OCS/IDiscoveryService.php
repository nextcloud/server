<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\OCS;

/**
 * Interface IDiscoveryService
 *
 * Allows you to discover OCS end-points on a remote server
 *
 * @since 12.0.0
 */
interface IDiscoveryService {
	/**
	 * Discover OCS end-points
	 *
	 * If no valid discovery data is found the defaults are returned
	 *
	 * @since 12.0.0
	 *
	 * @param string $remote
	 * @param string $service the service you want to discover
	 * @param bool $skipCache We won't check if the data is in the cache. This is useful if a background job is updating the status - Added in 14.0.0
	 * @return array
	 */
	public function discover(string $remote, string $service, bool $skipCache = false): array;
}
