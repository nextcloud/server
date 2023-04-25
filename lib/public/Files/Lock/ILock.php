<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2022 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Härtl <jus@bitgrid.net>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCP\Files\Lock;

/**
 * @since 24.0.0
 */
interface ILock {
	/**
	 * User owned manual lock
	 *
	 * This lock type is initiated by a user manually through the web UI or clients
	 * and will limit editing capabilities on the file to the lock owning user.
	 *
	 * @since 24.0.0
	 */
	public const TYPE_USER = 0;

	/**
	 * App owned lock
	 *
	 * This lock type is created by collaborative apps like Text or Office to avoid
	 * outside changes through WevDAV or other apps.
	 * @since 24.0.0
	 *
	 */
	public const TYPE_APP = 1;

	/**
	 * Token owned lock
	 *
	 * This lock type will bind the ownership to the provided lock token. Any request
	 * that aims to modify the file will be required to sent the token, the user
	 * itself is not able to write to files without the token. This will allow
	 * to limit the locking to an individual client.
	 *
	 * @since 24.0.0
	 */
	public const TYPE_TOKEN = 2;

	/**
	 * WebDAV Lock scope exclusive
	 *
	 * @since 24.0.0
	 */
	public const LOCK_EXCLUSIVE = 1;

	/**
	 * WebDAV Lock scope shared
	 *
	 * @since 24.0.0
	 */
	public const LOCK_SHARED = 2;

	/**
	 * Lock only the resource the lock is applied to
	 *
	 * @since 24.0.0
	 */
	public const LOCK_DEPTH_ZERO = 0;

	/**
	 * Lock app resources under the locked one with infinite depth
	 *
	 * @since 24.0.0
	 */
	public const LOCK_DEPTH_INFINITE = -1;

	/**
	 * Type of the lock
	 *
	 * @psalm-return ILock::TYPE_*
	 * @since 24.0.0
	 */
	public function getType(): int;

	/**
	 * Owner that holds the lock
	 *
	 * Depending on the lock type this is:
	 * - ILock::TYPE_USER: A user id
	 * - ILock::TYPE_APP: An app id
	 * - ILock::TYPE_TOKEN: A user id
	 *
	 * @since 24.0.0
	 */
	public function getOwner(): string;

	/**
	 * File id that the lock is holding
	 *
	 * @since 24.0.0
	 */
	public function getFileId(): int;

	/**
	 * Timeout of the lock in seconds starting from the created at time
	 *
	 * @since 24.0.0
	 */
	public function getTimeout(): int;

	/**
	 * Unix timestamp of the lock creation time
	 *
	 * @since 24.0.0
	 */
	public function getCreatedAt(): int;

	/**
	 * Token string as a unique identifier for the lock, usually a UUID
	 *
	 * @since 24.0.0
	 */
	public function getToken(): string;

	/**
	 * Lock depth to apply the lock to child resources
	 *
	 * @since 24.0.0
	 */
	public function getDepth(): int;

	/**
	 * WebDAV lock scope
	 *
	 * @since 24.0.0
	 * @psalm-return ILock::LOCK_EXCLUSIVE|ILock::LOCK_SHARED
	 */
	public function getScope(): int;

	/**
	 * String representation of the lock to identify it through logging
	 *
	 * @since 24.0.0
	 */
	public function __toString(): string;
}
