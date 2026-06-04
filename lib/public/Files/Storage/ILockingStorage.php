<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\Files\Storage;

use OCP\Lock\ILockingProvider;

/**
 * Storage backends that require explicit locking
 *
 * Storage backends implementing this interface do not need to implement their own locking implementation but should use the provided lockingprovider instead
 * The implementation of the locking methods only need to map internal storage paths to "lock keys"
 *
 * @since 9.0.0
 */
interface ILockingStorage {
	/**
	 * @param string $path The path of the file to acquire the lock for
	 * @param int $type \OCP\Lock\ILockingProvider::LOCK_SHARED or \OCP\Lock\ILockingProvider::LOCK_EXCLUSIVE
	 * @throws \OCP\Lock\LockedException
	 * @since 9.0.0
	 */
	public function acquireLock(string $path, int $type, ILockingProvider $provider);

	/**
	 * @param string $path The path of the file to acquire the lock for
	 * @param int $type \OCP\Lock\ILockingProvider::LOCK_SHARED or \OCP\Lock\ILockingProvider::LOCK_EXCLUSIVE
	 * @throws \OCP\Lock\LockedException
	 * @since 9.0.0
	 */
	public function releaseLock(string $path, int $type, ILockingProvider $provider);

	/**
	 * @param string $path The path of the file to change the lock for
	 * @param int $type \OCP\Lock\ILockingProvider::LOCK_SHARED or \OCP\Lock\ILockingProvider::LOCK_EXCLUSIVE
	 * @throws \OCP\Lock\LockedException
	 * @since 9.0.0
	 */
	public function changeLock(string $path, int $type, ILockingProvider $provider);
}
