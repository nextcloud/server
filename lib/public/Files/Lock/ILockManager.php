<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Files\Lock;

use OCP\PreConditionNotMetException;

/**
 * Manage app integrations with the files_lock app and collaborative editors.
 *
 * This public API exposes locking operations to apps and allows a lock provider
 * from files_lock to be registered. The actual locking implementation remains
 * in the provider and the DAV plugin of the files_lock app.
 *
 * @since 24.0.0
 */
interface ILockManager extends ILockProvider {
	/**
	 * Register the lock provider implementation.
	 *
	 * @throws PreConditionNotMetException if a lock provider is already registered
	 * @since 24.0.0
	 * @deprecated 30.0.0 Use registerLazyLockProvider
	 */
	public function registerLockProvider(ILockProvider $lockProvider): void;

	/**
	 * Register a lock provider class for lazy resolution from the server container.
	 *
	 * @throws PreConditionNotMetException if a lock provider is already registered
	 * @since 30.0.0
	 */
	public function registerLazyLockProvider(string $lockProviderClass): void;

	/**
	 * Check whether a lock provider is currently available.
	 *
	 * @since 24.0.0
	 */
	public function isLockProviderAvailable(): bool;

	/**
	 * Run a callback within the scope of the given lock context.
	 *
	 * The callback is also executed if no lock provider is available.
	 *
	 * @throws PreConditionNotMetException if another lock scope is already active
	 * @since 24.0.0
	 */
	public function runInScope(LockContext $lock, callable $callback): void;

	/**
	 * Get the lock context currently active in this scope.
	 *
	 * Returns null if no lock scope is active.
	 *
	 * @since 24.0.0
	 */
	public function getLockInScope(): ?LockContext;
}
