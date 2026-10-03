<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCP\Lock;

/**
 * Contract for providers of shared and exclusive locks for server resources.
 *
 * This low-level interface provides the short-lived locks used for concurrency
 * control by Nextcloud storage implementations and, through them, by the Files
 * Node and View APIs.
 *
 * While primarily used by storage implementations, this interface is generic
 * and may also coordinate access to other server resources.
 *
 * This is an underlying primitive, not the high-level VFS API used for locking
 * filesystem paths during normal Files Node or View operations.
 *
 * This interface is distinct from the OCP\Files\Lock APIs, which provide
 * user, application, and collaborative-editor locks for files.
 *
 * @since 8.1.0
 */
interface ILockingProvider {
	/**
	 * A shared lock. Multiple shared locks may coexist, but they prevent an
	 * exclusive lock from being acquired.
	 *
	 * @since 8.1.0
	 */
	public const LOCK_SHARED = 1;

	/**
	 * An exclusive lock. It prevents shared and exclusive locks from being
	 * acquired.
	 *
	 * @since 8.1.0
	 */
	public const LOCK_EXCLUSIVE = 2;

	/**
	 * Check whether a lock of the requested type exists for a resource.
	 *
	 * This does not determine whether acquiring the requested type would
	 * succeed. For example, an existing shared lock prevents an exclusive
	 * acquisition, while this method returns false for LOCK_EXCLUSIVE.
	 *
	 * @param string $path Identifier of the resource to check.
	 * @param self::LOCK_SHARED|self::LOCK_EXCLUSIVE $type Lock type to check.
	 * @return bool True when a lock of the requested type exists.
	 * @since 8.1.0
	 */
	public function isLocked(string $path, int $type): bool;

	/**
	 * Acquire a shared or exclusive lock for a resource.
	 *
	 * A shared lock cannot be acquired while an exclusive lock exists. An
	 * exclusive lock cannot be acquired while any lock exists.
	 *
	 * @param string $path Identifier of the resource to lock.
	 * @param self::LOCK_SHARED|self::LOCK_EXCLUSIVE $type Lock type to acquire.
	 * @param ?string $readablePath Optional human-readable representation of
	 *        `$path` to include in error messages.
	 * @throws LockedException If the requested lock cannot be acquired.
	 * @since 8.1.0
	 */
	public function acquireLock(string $path, int $type, ?string $readablePath = null): void;

	/**
	 * Release one acquisition of the requested lock type for a resource.
	 *
	 * Repeated shared-lock acquisitions must be released separately.
	 *
	 * @param string $path Identifier of the resource to unlock.
	 * @param self::LOCK_SHARED|self::LOCK_EXCLUSIVE $type Lock type to release.
	 * @since 8.1.0
	 */
	public function releaseLock(string $path, int $type): void;

	/**
	 * Convert an existing lock to another type.
	 *
	 * Converting a shared lock to an exclusive lock succeeds only when the
	 * shared lock is the only shared lock. An exclusive lock can be converted
	 * to a shared lock.
	 *
	 * @param string $path Identifier of the resource whose lock to change.
	 * @param self::LOCK_SHARED|self::LOCK_EXCLUSIVE $targetType Lock type to
	 *        convert to.
	 * @throws LockedException If the existing lock cannot be converted.
	 * @since 8.1.0
	 */
	public function changeLock(string $path, int $targetType): void;

	/**
	 * Release all locks acquired and tracked by this provider instance.
	 *
	 * @since 8.1.0
	 */
	public function releaseAll(): void;
}
