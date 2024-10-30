<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Jaakko Salo <jaakkos@gmail.com>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OC\Lock;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IMemcache;
use OCP\IMemcacheTTL;
use OCP\Lock\LockedException;

class MemcacheLockingProvider extends AbstractLockingProvider {
	/** @var array<string, array{time: int, ttl: int}> */
	private array $oldTTLs = [];

	public function __construct(
		private IMemcache $memcache,
		private ITimeFactory $timeFactory,
		int $ttl = 3600,
	) {
		parent::__construct($ttl);
	}

	private function setTTL(string $path, int $ttl = null, mixed $compare = null): void {
		if (is_null($ttl)) {
			$ttl = $this->ttl;
		}
		if ($this->memcache instanceof IMemcacheTTL) {
			if ($compare !== null) {
				$this->memcache->compareSetTTL($path, $compare, $ttl);
			} else {
				$this->memcache->setTTL($path, $ttl);
			}
		}
	}

	private function getTTL(string $path): int {
		if ($this->memcache instanceof IMemcacheTTL) {
			$ttl = $this->memcache->getTTL($path);
			return $ttl === false ? -1 : $ttl;
		} else {
			return -1;
		}
	}

	public function isLocked(string $path, int $type): bool {
		$lockValue = $this->memcache->get($path);
		if ($type === self::LOCK_SHARED) {
			return is_int($lockValue) && $lockValue > 0;
		} elseif ($type === self::LOCK_EXCLUSIVE) {
			return $lockValue === 'exclusive';
		} else {
			return false;
		}
	}

	public function acquireLock(string $path, int $type, ?string $readablePath = null): void {
		if ($type === self::LOCK_SHARED) {
			// save the old TTL to for `restoreTTL`
			$this->oldTTLs[$path] = [
				"ttl" => $this->getTTL($path),
				"time" => $this->timeFactory->getTime()
			];
			if (!$this->memcache->inc($path)) {
				throw new LockedException($path, null, $this->getExistingLockForException($path), $readablePath);
			}
		} else {
			// when getting exclusive locks, we know there are no old TTLs to restore
			$this->memcache->add($path, 0);
			// ttl is updated automatically when the `set` succeeds
			if (!$this->memcache->cas($path, 0, 'exclusive')) {
				throw new LockedException($path, null, $this->getExistingLockForException($path), $readablePath);
			}
			unset($this->oldTTLs[$path]);
		}
		$this->setTTL($path);
		$this->markAcquire($path, $type);
	}

	public function releaseLock(string $path, int $type): void {
		if ($type === self::LOCK_SHARED) {
			$ownSharedLockCount = $this->getOwnSharedLockCount($path);
			$newValue = 0;
			if ($ownSharedLockCount === 0) { // if we are not holding the lock, don't try to release it
				return;
			}
			if ($ownSharedLockCount === 1) {
				$removed = $this->memcache->cad($path, 1); // if we're the only one having a shared lock we can remove it in one go
				if (!$removed) { //someone else also has a shared lock, decrease only
					$newValue = $this->memcache->dec($path);
				}
			} else {
				// if we own more than one lock ourselves just decrease
				$newValue = $this->memcache->dec($path);
			}

			if ($newValue > 0) {
				$this->restoreTTL($path);
			} else {
				unset($this->oldTTLs[$path]);
			}

			// if we somehow release more locks then exists, reset the lock
			if ($newValue < 0) {
				$this->memcache->cad($path, $newValue);
			}
		} elseif ($type === self::LOCK_EXCLUSIVE) {
			$this->memcache->cad($path, 'exclusive');
		}
		$this->markRelease($path, $type);
	}

	public function changeLock(string $path, int $targetType): void {
		if ($targetType === self::LOCK_SHARED) {
			if (!$this->memcache->cas($path, 'exclusive', 1)) {
				throw new LockedException($path, null, $this->getExistingLockForException($path));
			}
		} elseif ($targetType === self::LOCK_EXCLUSIVE) {
			// we can only change a shared lock to an exclusive if there's only a single owner of the shared lock
			if (!$this->memcache->cas($path, 1, 'exclusive')) {
				$this->restoreTTL($path);
				throw new LockedException($path, null, $this->getExistingLockForException($path));
			}
			unset($this->oldTTLs[$path]);
		}
		$this->setTTL($path);
		$this->markChange($path, $targetType);
	}

	/**
	 * With shared locks, each time the lock is acquired, the ttl for the path is reset.
	 *
	 * Due to this "ttl extension" when a shared lock isn't freed correctly for any reason
	 * the lock won't expire until no shared locks are required for the path for 1h.
	 * This can lead to a client repeatedly trying to upload a file, and failing forever
	 * because the lock never gets the opportunity to expire.
	 *
	 * To help the lock expire in this case, we lower the TTL back to what it was before we
	 * took the shared lock *only* if nobody else got a shared lock after we did.
	 *
	 * This doesn't handle all cases where multiple requests are acquiring shared locks
	 * but it should handle some of the more common ones and not hurt things further
	 */
	private function restoreTTL(string $path): void {
		if (isset($this->oldTTLs[$path])) {
			$saved = $this->oldTTLs[$path];
			$elapsed = $this->timeFactory->getTime() - $saved['time'];

			// old value to compare to when setting ttl in case someone else changes the lock in the middle of this function
			$value = $this->memcache->get($path);

			$currentTtl = $this->getTTL($path);

			// what the old ttl would be given the time elapsed since we acquired the lock
			// note that if this gets negative the key will be expired directly when we set the ttl
			$remainingOldTtl = $saved['ttl'] - $elapsed;
			// what the currently ttl would be if nobody else acquired a lock since we did (+1 to cover rounding errors)
			$expectedTtl = $this->ttl - $elapsed + 1;

			// check if another request has acquired a lock (and didn't release it yet)
			if ($currentTtl <= $expectedTtl) {
				$this->setTTL($path, $remainingOldTtl, $value);
			}
		}
	}

	private function getExistingLockForException(string $path): string {
		$existing = $this->memcache->get($path);
		if (!$existing) {
			return 'none';
		} elseif ($existing === 'exclusive') {
			return $existing;
		} else {
			return $existing . ' shared locks';
		}
	}
}
