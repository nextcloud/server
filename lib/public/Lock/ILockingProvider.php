<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Morris Jobke <hey@morrisjobke.de>
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
