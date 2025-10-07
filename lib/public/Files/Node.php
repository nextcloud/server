<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCP\Files;

use OCP\AppFramework\Attribute\Consumable;
use OCP\Lock\ILockingProvider;
use OCP\Lock\LockedException;

/**
 * Interface Node
 *
 * Represents a generic node in a hierarchical structure. This can be either
 * a \OCP\Files\Folder or \OCP\Files\File.
 *
 * @since 6.0.0 - extends FileInfo was added in 8.0.0
 */
#[Consumable(since: '6.0.0')]
interface Node extends FileInfo {
	/**
	 * Move the file or folder to a new location.
	 *
	 * @param string $targetPath the absolute target path
	 * @throws NotFoundException
	 * @throws NotPermittedException if move not allowed or failed
	 * @throws LockedException
	 * @throws InvalidPathException
	 * @since 6.0.0
	 */
	public function move(string $targetPath): Node;

	/**
	 * Delete the file or folder
	 *
	 * @throws NotPermittedException
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 * @since 6.0.0
	 */
	public function delete(): void;

	/**
	 * Copy the file or folder to a new location
	 *
	 * @param string $targetPath the absolute target path
	 * @since 6.0.0
	 */
	public function copy(string $targetPath): Node;

	/**
	 * Change the modified date of the file or folder
	 * If $mtime is omitted the current time will be used
	 *
	 * @param int $mtime (optional) modified date as unix timestamp
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 * @throws NotPermittedException
	 * @return void
	 * @since 6.0.0
	 */
	public function touch(?int $mtime = null): void;

	/**
	 * Get metadata of the file or folder.
	 *
	 * The returned array contains the following values:
	 *  - mtime
	 *  - size
	 *
	 * @since 6.0.0
	 */
	public function stat(): array|false;

	/**
	 * Get the parent folder of the file or folder.
	 *
	 * @since 6.0.0
	 */
	public function getParent(): IRootFolder|Folder;

	/**
	 * Acquire a lock on this file or folder.
	 *
	 * A shared (read) lock will prevent any exclusive (write) locks from being created but any number of shared locks
	 * can be active at the same time.
	 * An exclusive lock will prevent any other lock from being created (both shared and exclusive).
	 *
	 * A locked exception will be thrown if any conflicting lock already exists
	 *
	 * Note that this uses mandatory locking, if you acquire an exclusive lock on a file it will block *all*
	 * other operations for that file, even within the same php process.
	 *
	 * Acquiring any lock on a file will also create a shared lock on all parent folders of that file.
	 *
	 * Note that in most cases you won't need to manually manage the locks for any files you're working with,
	 * any filesystem operation will automatically acquire the relevant locks for that operation.
	 *
	 * @param ILockingProvider::LOCK_SHARED|ILockingProvider::LOCK_EXCLUSIVE $type
	 * @throws LockedException
	 * @since 9.1.0
	 */
	public function lock(int $type): void;

	/**
	 * Check the type of an existing lock.
	 *
	 * A shared lock can be changed to an exclusive lock is there is exactly one shared lock on the file,
	 * an exclusive lock can always be changed to a shared lock since there can only be one exclusive lock in the first place.
	 *
	 * A locked exception will be thrown when these preconditions are not met.
	 * Note that this is also the case if no existing lock exists for the file.
	 *
	 * @param ILockingProvider::LOCK_SHARED|ILockingProvider::LOCK_EXCLUSIVE $targetType
	 * @throws LockedException
	 * @since 9.1.0
	 */
	public function changeLock(int $targetType): void;

	/**
	 * Release an existing lock.
	 *
	 * This will also free up the shared locks on any parent folder that were automatically acquired when locking the file.
	 *
	 * Note that this method will not give any sort of error when trying to free a lock that doesn't exist.
	 *
	 * @param ILockingProvider::LOCK_SHARED|ILockingProvider::LOCK_EXCLUSIVE $type
	 * @throws LockedException
	 * @since 9.1.0
	 */
	public function unlock(int $type): void;
}
