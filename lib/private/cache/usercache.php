<?php
/**
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Scrutinizer Auto-Fixer <auto-fixer@scrutinizer-ci.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Thomas Tanghus <thomas@tanghus.net>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OC\Cache;

/**
 * This interface defines method for accessing the file based user cache.
 */
class UserCache implements \OCP\ICache {

	/**
	 * @var \OC\Cache\File $userCache
	 */
	protected $userCache;

	public function __construct() {
		$this->userCache = new File();
	}

	/**
	 * Get a value from the user cache
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function get($key) {
		return $this->userCache->get($key);
	}

	/**
	 * Set a value in the user cache
	 *
	 * @param string $key
	 * @param string $value
	 * @param int $ttl Time To Live in seconds. Defaults to 60*60*24
	 * @return bool
	 */
	public function set($key, $value, $ttl = 0) {
		if (empty($key)) {
			return false;
		}
		return $this->userCache->set($key, $value, $ttl);
	}

	/**
	 * Check if a value is set in the user cache
	 *
	 * @param string $key
	 * @return bool
	 */
	public function hasKey($key) {
		return $this->userCache->hasKey($key);
	}

	/**
	 * Remove an item from the user cache
	 *
	 * @param string $key
	 * @return bool
	 */
	public function remove($key) {
		return $this->userCache->remove($key);
	}

	/**
	 * clear the user cache of all entries starting with a prefix
	 * @param string $prefix (optional)
	 * @return bool
	 */
	public function clear($prefix = '') {
		return $this->userCache->clear($prefix);
	}
}
