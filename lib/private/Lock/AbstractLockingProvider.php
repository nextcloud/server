<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\Lock;

use OCP\Lock\ILockingProvider;

/**
 * Base locking provider that keeps track of locks acquired during the current request
 * to release any leftover locks at the end of the request
 */
abstract class AbstractLockingProvider implements ILockingProvider {
	/**
	 * @var array{
	 *     shared: array<string, int>,
	 *     exclusive: array<string, true>,
	 * }
	 */
	protected array $acquiredLocks = [
		'shared' => [],
		'exclusive' => [],
	];

	/**
	 * @param int $ttl How long until stray locks are cleared, in seconds.
	 */
	public function __construct(
		protected int $ttl,
	) {
	}

	protected function assertValidLockType(int $type): void {
		if ($type !== self::LOCK_SHARED && $type !== self::LOCK_EXCLUSIVE) {
			throw new \InvalidArgumentException('Invalid lock type: ' . $type);
		}
	}

	protected function hasAcquiredLock(string $path, int $type): bool {
		if ($type === self::LOCK_SHARED) {
			return isset($this->acquiredLocks['shared'][$path])
				&& $this->acquiredLocks['shared'][$path] > 0;
		}

		return $type === self::LOCK_EXCLUSIVE
			&& isset($this->acquiredLocks['exclusive'][$path]);
	}

	protected function markAcquire(string $path, int $targetType): void {
		if ($targetType === self::LOCK_EXCLUSIVE) {
			$this->acquiredLocks['exclusive'][$path] = true;
			return;
		}

		if ($targetType === self::LOCK_SHARED) {
			$this->acquiredLocks['shared'][$path] ??= 0;
			$this->acquiredLocks['shared'][$path]++;
		}
	}

	protected function markRelease(string $path, int $type): void {
		if ($type === self::LOCK_EXCLUSIVE) {
			unset($this->acquiredLocks['exclusive'][$path]);
			return;
		}

		if ($type === self::LOCK_SHARED) {
			if (!isset($this->acquiredLocks['shared'][$path])) {
				return;
			}

			$this->acquiredLocks['shared'][$path]--;
			if ($this->acquiredLocks['shared'][$path] === 0) {
				unset($this->acquiredLocks['shared'][$path]);
			}
		}
	}

	protected function markChange(string $path, int $targetType): void {
		if ($targetType === self::LOCK_EXCLUSIVE) {
			$this->acquiredLocks['exclusive'][$path] = true;
			unset($this->acquiredLocks['shared'][$path]);
			return;
		}

		if ($targetType === self::LOCK_SHARED) {
			unset($this->acquiredLocks['exclusive'][$path]);
			$this->acquiredLocks['shared'][$path] ??= 0;
			$this->acquiredLocks['shared'][$path]++;
		}
	}

	#[\Override]
	public function releaseAll(): void {
		foreach ($this->acquiredLocks['shared'] as $path => $count) {
			for ($i = 0; $i < $count; $i++) {
				$this->releaseLock($path, self::LOCK_SHARED);
			}
		}

		foreach ($this->acquiredLocks['exclusive'] as $path => $_) {
			$this->releaseLock($path, self::LOCK_EXCLUSIVE);
		}
	}

	protected function getOwnSharedLockCount(string $path): int {
		return $this->acquiredLocks['shared'][$path] ?? 0;
	}
}
