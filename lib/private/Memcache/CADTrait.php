<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Memcache;

/**
 * CAD/NCAD implementations for cache backends that lack their own.
 */
trait CADTrait {
	abstract public function get($key);

	abstract public function remove($key);

	abstract public function add($key, $value, $ttl = 0);

	/**
	 * Compare-and-delete.
  	 *
	 * If $key's current value is equal to $expectedValue, delete $key.
  	 * Note may return false for reasons other than not meeting condition.
	 *
	 * Implements CAD using simple locking (only requiring backend add/remove support).
	 *
	 * @param string $key
	 * @param mixed $expectedValue
	 * @return bool True if key was deleted; False if no deletion occurred
	 */
	public function cad($key, $expectedValue) {
		if (!$this->acquireLock($key)) {
			return false;
		}
		$currentValue = $this->get($key);
		// Check condition		
		if ($currentValue === $expectedValue) {
			// Matches condition
			$this->remove($key);
			$this->releaseLock($key);
			return true;
		} else {
			// Fails condition
			$this->releaseLock($key);
			// TODO: consider throwing if release fails
			return false;
		}
	}

	/**
 	 * Nonequal-compare-and-delete.
   	 *
	 * If $key's current value is not equal to $expectedValue, delete $key.
	 * Note may return false for reasons other than not meeting condition.
	 *
	 * Implements NCAD using simple locking (only requiring backend add/remove support).
  	 *
	 * @param string $key
	 * @param mixed $expectedValue
	 * @return bool True if key was deleted; False if no deletion occurred
	 */
	public function ncad(string $key, mixed $expectedValue): bool {
		if (!$this->acquireLock($key)) {
			return false;
		}
		$currentValue = $this->get($key);
		// Check condition
		if ($currentValue !== null && $currentValue !== $expectedValue) {
			// Matches condition
			$this->remove($key);
			$this->releaseLock($key);
			return true;
		} else {
			// Fails condition
			$this->releaseLock($key);
			// TODO: consider throwing if release fails
			return false;
		}
	}

	//
	// Utilities
	//

	private function acquireLock(string $key): bool {
		// TODO: an actual TTL would be clearer
		return $this->add($key . '_lock', true);
	}
	
	private function releaseLock(string $key): bool {
		return $this->remove($key . '_lock');
	}
}
