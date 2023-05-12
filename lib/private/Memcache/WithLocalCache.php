<?php
/**
 * @copyright Copyright (c) 2016 Robin Appelman <robin@icewind.nl>
 *
 * @author Robin Appelman <robin@icewind.nl>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OC\Memcache;

use OCP\Cache\CappedMemoryCache;
use OCP\ICache;

/**
 * Wrap a cache instance with an extra later of local, in-memory caching
 */
class WithLocalCache implements ICache {
	private ICache $inner;
	private CappedMemoryCache $cached;

	public function __construct(ICache $inner, int $localCapacity = 512) {
		$this->inner = $inner;
		$this->cached = new CappedMemoryCache($localCapacity);
	}

	public function get($key) {
		if (isset($this->cached[$key])) {
			return $this->cached[$key];
		} else {
			$value = $this->inner->get($key);
			if (!is_null($value)) {
				$this->cached[$key] = $value;
			}
			return $value;
		}
	}

	public function set($key, $value, $ttl = 0) {
		$this->cached[$key] = $value;
		return $this->inner->set($key, $value, $ttl);
	}

	public function hasKey($key) {
		return isset($this->cached[$key]) || $this->inner->hasKey($key);
	}

	public function remove($key) {
		unset($this->cached[$key]);
		return $this->inner->remove($key);
	}

	public function clear($prefix = '') {
		$this->cached->clear();
		return $this->inner->clear($prefix);
	}

	public static function isAvailable(): bool {
		return false;
	}
}
