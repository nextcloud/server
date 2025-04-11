<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
// use OCP namespace for all classes that are considered public.
// This means that they should be used by apps instead of the internal Nextcloud classes

namespace OCP;

use OCP\Session\Exceptions\SessionNotAvailableException;

/**
 * Interface ISession
 *
 * wrap PHP's internal session handling into the ISession interface
 * @since 6.0.0
 */
interface ISession {
	/**
	 * Set a value in the session
	 *
	 * @param string $key
	 * @param mixed $value
	 * @since 6.0.0
	 */
	public function set(string $key, $value);

	/**
	 * Get a value from the session
	 *
	 * @param string $key
	 * @return mixed should return null if $key does not exist
	 * @since 6.0.0
	 */
	public function get(string $key);

	/**
	 * Check if a named key exists in the session
	 *
	 * @param string $key
	 * @return bool
	 * @since 6.0.0
	 */
	public function exists(string $key): bool;

	/**
	 * Remove a $key/$value pair from the session
	 *
	 * @param string $key
	 * @since 6.0.0
	 */
	public function remove(string $key);

	/**
	 * Reset and recreate the session
	 * @since 6.0.0
	 */
	public function clear();

	/**
	 * Reopen a session for writing again
	 *
	 * @return bool true if the session was actually reopened, otherwise false
	 * @since 25.0.0
	 */
	public function reopen(): bool;

	/**
	 * Close the session and release the lock
	 * @since 7.0.0
	 */
	public function close();

	/**
	 * Wrapper around session_regenerate_id
	 *
	 * @param bool $deleteOldSession Whether to delete the old associated session file or not.
	 * @param bool $updateToken Wheater to update the associated auth token
	 * @return void
	 * @since 9.0.0, $updateToken added in 14.0.0
	 */
	public function regenerateId(bool $deleteOldSession = true, bool $updateToken = false);

	/**
	 * Wrapper around session_id
	 *
	 * @return string
	 * @throws SessionNotAvailableException
	 * @since 9.1.0
	 */
	public function getId(): string;
}
