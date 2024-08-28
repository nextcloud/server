<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Files\Lock;

use OCP\PreConditionNotMetException;

/**
 * Manage app integrations with files_lock with collaborative editors
 *
 * The OCP parts are mainly for exposing the ability to lock/unlock for apps and
 * to give the files_lock app a way to register and then be triggered by the apps
 * while the actual locking implementation is kept in the LockProvider and DAV
 * plugin from files_lock app.
 *
 * @since 24.0.0
 */
interface ILockManager extends ILockProvider {
	/**
	 * @throws PreConditionNotMetException if there is already a lock provider registered
	 * @since 24.0.0
	 * @deprecated 30.0.0 Use registerLazyLockProvider
	 */
	public function registerLockProvider(ILockProvider $lockProvider): void;

	/**
	 * @param string $lockProviderClass
	 * @return void
	 * @since 30.0.0
	 */
	public function registerLazyLockProvider(string $lockProviderClass): void;

	/**
	 * @return bool
	 * @since 24.0.0
	 */
	public function isLockProviderAvailable(): bool;

	/**
	 * Run within the scope of a given lock condition
	 *
	 * The callback will also be executed if no lock provider is present
	 *
	 * @since 24.0.0
	 */
	public function runInScope(LockContext $lock, callable $callback): void;

	/**
	 * @throws NoLockProviderException if there is no lock provider available
	 * @since 24.0.0
	 */
	public function getLockInScope(): ?LockContext;
}
