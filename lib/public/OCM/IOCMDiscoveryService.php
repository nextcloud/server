<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\OCM;

use OCP\OCM\Exceptions\OCMProviderException;

/**
 * Discover remote OCM services
 *
 * @since 28.0.0
 */
interface IOCMDiscoveryService {
	/**
	 * Discover remote OCM services
	 *
	 * @param string $remote address of the remote provider
	 * @param bool $skipCache ignore cache, refresh data
	 *
	 * @return IOCMProvider
	 * @throws OCMProviderException if no valid discovery data can be returned
	 * @since 28.0.0
	 */
	public function discover(string $remote, bool $skipCache = false): IOCMProvider;
}
