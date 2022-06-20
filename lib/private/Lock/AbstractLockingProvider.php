<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
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

use OCP\Lock\ILockingProvider;

/**
 * Base locking provider that keeps track of locks acquired during the current request
 * to release any leftover locks at the end of the request
 */
abstract class AbstractLockingProvider implements ILockingProvider {
	/** how long until we clear stray locks in seconds */
	protected int $ttl;

	protected $acquiredLocks = [
		'shared' => [],
		'exclusive' => []
	];

	/** @inheritDoc */
	protected function hasAcquiredLock(string $path, int $type): bool {
		if ($type === self::LOCK_SHARED) {
			return isset($this->acquiredLocks['shared'][$path]) && $this->acquiredLocks['shared'][$path] > 0;
		} else {
			return isset($this->acquiredLocks['exclusive'][$path]) && $this->acquiredLocks['exclusive'][$path] === true;
		}
	}

	/** @inheritDoc */
	protected function markAcquire(string $path, int $targetType): void {
		if ($targetType === self::LOCK_SHARED) {
			if (!isset($this->acquiredLocks['shared'][$path])) {
				$this->acquiredLocks['shared'][$path] = 0;
			}
			$this->acquiredLocks['shared'][$path]++;
		} else {
			$this->acquiredLocks['exclusive'][$path] = true;
		}
	}

	/** @inheritDoc */
	protected function markRelease(string $path, int $type): void {
		if ($type === self::LOCK_SHARED) {
			if (isset($this->acquiredLocks['shared'][$path]) and $this->acquiredLocks['shared'][$path] > 0) {
				$this->acquiredLocks['shared'][$path]--;
				if ($this->acquiredLocks['shared'][$path] === 0) {
					unset($this->acquiredLocks['shared'][$path]);
				}
			}
		} elseif ($type === self::LOCK_EXCLUSIVE) {
			unset($this->acquiredLocks['exclusive'][$path]);
		}
	}

	/** @inheritDoc */
	protected function markChange(string $path, int $targetType): void {
		if ($targetType === self::LOCK_SHARED) {
			unset($this->acquiredLocks['exclusive'][$path]);
			if (!isset($this->acquiredLocks['shared'][$path])) {
				$this->acquiredLocks['shared'][$path] = 0;
			}
			$this->acquiredLocks['shared'][$path]++;
		} elseif ($targetType === self::LOCK_EXCLUSIVE) {
			$this->acquiredLocks['exclusive'][$path] = true;
			$this->acquiredLocks['shared'][$path]--;
		}
	}

	/** @inheritDoc */
	public function releaseAll(): void {
		foreach ($this->acquiredLocks['shared'] as $path => $count) {
			for ($i = 0; $i < $count; $i++) {
				$this->releaseLock($path, self::LOCK_SHARED);
			}
		}

		foreach ($this->acquiredLocks['exclusive'] as $path => $hasLock) {
			$this->releaseLock($path, self::LOCK_EXCLUSIVE);
		}
	}

	protected function getOwnSharedLockCount(string $path): int {
		return $this->acquiredLocks['shared'][$path] ?? 0;
	}
}
