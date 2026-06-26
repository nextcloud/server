<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\Lock;

/**
 * This interface allows locking and unlocking filesystem paths
 *
 * This interface should be used directly and not implemented by an application.
 * The implementation is provided by the server.
 *
 * @since 8.1.0
 */
interface ILockingProvider {
	/**
	 * @since 8.1.0
	 */
	public const LOCK_SHARED = 1;
	/**
	 * @since 8.1.0
	 */
	public const LOCK_EXCLUSIVE = 2;

	/**
	 * @psalm-param self::LOCK_SHARED|self::LOCK_EXCLUSIVE $type
	 * @since 8.1.0
	 */
	public function isLocked(string $path, int $type): bool;

	/**
	 * @psalm-param self::LOCK_SHARED|self::LOCK_EXCLUSIVE $type
	 * @param ?string $readablePath A human-readable path to use in error messages, since 20.0.0
	 * @throws LockedException
	 * @since 8.1.0
	 */
	public function acquireLock(string $path, int $type, ?string $readablePath = null): void;

	/**
	 * @psalm-param self::LOCK_SHARED|self::LOCK_EXCLUSIVE $type
	 * @since 8.1.0
	 */
	public function releaseLock(string $path, int $type): void;

	/**
	 * Change the target type of an existing lock
	 *
	 * @psalm-param self::LOCK_SHARED|self::LOCK_EXCLUSIVE $targetType
	 * @throws LockedException
	 * @since 8.1.0
	 */
	public function changeLock(string $path, int $targetType): void;

	/**
	 * Release all lock acquired by this instance
	 * @since 8.1.0
	 */
	public function releaseAll(): void;
}
