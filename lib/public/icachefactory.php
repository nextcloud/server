<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCP;

interface ICacheFactory{
	/**
	 * Get a memory cache instance
	 *
	 * All entries added trough the cache instance will be namespaced by $prefix to prevent collisions between apps
	 *
	 * @param string $prefix
	 * @return \OCP\ICache
	 */
	public function create($prefix = '');

	/**
	 * Check if any memory cache backend is available
	 *
	 * @return bool
	 */
	public function isAvailable();
}
