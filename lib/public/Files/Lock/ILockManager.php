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
	 */
	public function registerLockProvider(ILockProvider $lockProvider): void;

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
