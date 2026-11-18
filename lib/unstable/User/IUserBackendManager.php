<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace NCU\User;

use Generator;

interface IUserBackendManager {
	/**
	 * Register a new backend
	 *
	 * @param class-string<IUserBackend> $class
	 * @return int Registered backend ID
	 * @since 36.0.0
	 */
	public function register(string $name, string $class): int;

	/**
	 * Remove a known backend
	 *
	 * @since 36.0.0
	 */
	public function remove(int $backendId): bool;

	/**
	 * List all available backends
	 *
	 * @return Generator<IUserBackend>
	 * @since 36.0.0
	 */
	public function list(): Generator;

	/**
	 * Get a backend from its ID
	 *
	 * @since 36.0.0
	 */
	public function get(int $backendId): IUserBackend;
}
