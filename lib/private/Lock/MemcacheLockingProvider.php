<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\Lock;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IMemcache;
use OCP\IMemcacheTTL;
use OCP\Lock\LockedException;

/**
 * Locking provider that stores locks in memcache.
 */
class MemcacheLockingProvider extends AbstractLockingProvider {
	private const EXCLUSIVE_LOCK_VALUE = 'exclusive';

	/** @var array<string, array{time: int, ttl: int}> */
	private array $oldTTLs = [];

	public function __construct(
		private IMemcache $memcache,
		private ITimeFactory $timeFactory,
		int $ttl = 3600,
	) {
		parent::__construct($ttl);
	}

	private function setTTL(string $path, ?int $ttl = null, mixed $compare = null): void {
		if ($ttl === null) {
			$ttl = $this->ttl;
		}

		if (!$this->memcache instanceof IMemcacheTTL) {
			return;
		}

		if ($compare !== null) {
			$this->memcache->compareSetTTL($path, $compare, $ttl);
			return;
		}

		$this->memcache->setTTL($path, $ttl);
	}

	/**
	 * @return int Actual TTL, or -1 when TTL support is unavailable or no TTL is returned
	 */
	private function getTTL(string $path): int {
		if (!$this->memcache instanceof IMemcacheTTL) {
			return -1;
		}

		$ttl = $this->memcache->getTTL($path);
		return $ttl === false ? -1 : $ttl;
	}

	#[\Override]
	public function isLocked(string $path, int $type): bool {
		$this->assertValidLockType($type);

		$lockValue = $this->memcache->get($path);

		return match ($type) {
			self::LOCK_SHARED => is_int($lockValue) && $lockValue > 0,
			self::LOCK_EXCLUSIVE => $lockValue === self::EXCLUSIVE_LOCK_VALUE,
		};
	}

	#[\Override]
	public function acquireLock(string $path, int $type, ?string $readablePath = null): void {
		$this->assertValidLockType($type);

		if ($type === self::LOCK_SHARED) {
			// Save the previous TTL for restoreTTL().
			$this->oldTTLs[$path] = [
				'ttl' => $this->getTTL($path),
				'time' => $this->timeFactory->getTime(),
			];
			if (!$this->memcache->inc($path)) {
				throw new LockedException($path, null, $this->getExistingLockForException($path), $readablePath);
			}
		} elseif ($type === self::LOCK_EXCLUSIVE) {
			// An exclusive lock has no TTL to restore.
			$this->memcache->add($path, 0);
			// The TTL is updated automatically when the compare-and-set succeeds.
			if (!$this->memcache->cas($path, 0, self::EXCLUSIVE_LOCK_VALUE)) {
				throw new LockedException($path, null, $this->getExistingLockForException($path), $readablePath);
			}
			unset($this->oldTTLs[$path]);
		}

		$this->setTTL($path);
		$this->markAcquire($path, $type);
	}

	#[\Override]
	public function releaseLock(string $path, int $type): void {
		$this->assertValidLockType($type);

		if ($type === self::LOCK_SHARED) {
			$ownSharedLockCount = $this->getOwnSharedLockCount($path);
			$newValue = 0;
			// If we do not hold the lock, do not try to release it.
			if ($ownSharedLockCount === 0) {
				return;
			}
			// If we own the only shared lock, remove it atomically.
			if ($ownSharedLockCount === 1) {
				$removed = $this->memcache->cad($path, 1);
				if (!$removed) {
					// Another owner holds a shared lock; decrement only our share.
					$newValue = $this->memcache->dec($path);
				}
			} else {
				// If we hold more than one shared lock, decrement only our count.
				$newValue = $this->memcache->dec($path);
			}

			if ($newValue > 0) {
				$this->restoreTTL($path);
			} else {
				unset($this->oldTTLs[$path]);
			}

			// If we (somehow) release more locks than exist, reset the lock.
			if ($newValue < 0) {
				$this->memcache->cad($path, $newValue);
			}
		} elseif ($type === self::LOCK_EXCLUSIVE) {
			$this->memcache->cad($path, self::EXCLUSIVE_LOCK_VALUE);
		}

		$this->markRelease($path, $type);
	}

	#[\Override]
	public function changeLock(string $path, int $targetType): void {
		$this->assertValidLockType($targetType);

		if ($targetType === self::LOCK_SHARED) {
			if (!$this->memcache->cas($path, self::EXCLUSIVE_LOCK_VALUE, 1)) {
				throw new LockedException($path, null, $this->getExistingLockForException($path));
			}
		} elseif ($targetType === self::LOCK_EXCLUSIVE) {
			// A shared lock can only be upgraded to exclusive when the shared lock has a single owner.
			if (!$this->memcache->cas($path, 1, self::EXCLUSIVE_LOCK_VALUE)) {
				$this->restoreTTL($path);
				throw new LockedException($path, null, $this->getExistingLockForException($path));
			}
			unset($this->oldTTLs[$path]);
		}

		$this->setTTL($path);
		$this->markChange($path, $targetType);
	}

	/**
	 * With shared locks, each acquisition resets the path's TTL.
	 *
	 * A side effect of this automatic TTL extension is that a shared lock not
	 * released for any reason may never expire if shared locks continue to be
	 * acquired for the path. With the default one-hour TTL, a client repeatedly
	 * trying to upload a file can therefore fail indefinitely because the lock
	 * never has an opportunity to expire.
	 *
	 * To help the lock expire in this case, restore the path's previous TTL, but
	 * only if no other concurrent owner, such as another request, acquired a
	 * shared lock after this one.
	 *
	 * This does not cover every concurrent shared-lock scenario, but mitigates
	 * common cases without making them worse.
	 */
	private function restoreTTL(string $path): void {
		if (!isset($this->oldTTLs[$path])) {
			return;
		}

		$saved = $this->oldTTLs[$path];
		$elapsed = $this->timeFactory->getTime() - $saved['time'];

		// Value to compare when updating the TTL in case another request changes the lock.
		$value = $this->memcache->get($path);

		$currentTtl = $this->getTTL($path);

		// Account for elapsed time since acquiring the shared lock.
		$remainingOldTtl = $saved['ttl'] - $elapsed;

		// Allow one second for rounding when detecting a concurrent acquisition.
		$expectedTtl = $this->ttl - $elapsed + 1;

		// Restore the TTL only if no other request has acquired the lock since.
		if ($currentTtl <= $expectedTtl) {
			// A negative remaining TTL expires the key.
			$this->setTTL($path, $remainingOldTtl, $value);
		}
	}

	private function getExistingLockForException(string $path): string {
		$existing = $this->memcache->get($path);

		if (!$existing) {
			return 'none';
		}

		if ($existing === self::EXCLUSIVE_LOCK_VALUE) {
			return $existing;
		}

		return $existing . ' shared locks';
	}
}
