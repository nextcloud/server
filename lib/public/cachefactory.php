<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCP;

interface CacheFactory{
	/**
	 * @param string $prefix
	 * @return $return \OCP\ICache
	 */
	public function create($prefix = '');
}
