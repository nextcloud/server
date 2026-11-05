<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Permission;

use OCP\AppFramework\Attribute\Consumable;

/**
 * Registry of all permissions.
 *
 * Permissions are registered by class name in {@see \OCP\AppFramework\Bootstrap\IBootstrap::register()}
 * and instantiated through the DI container when they are first read.
 * Invalid registrations are logged and ignored.
 *
 * @experimental 36.0.0
 */
#[Consumable(since: '36.0.0')]
interface IPermissionManager {
	/**
	 * @param class-string<IPermission> $class
	 * @experimental 36.0.0
	 */
	public function registerPermission(string $class): void;

	/**
	 * Ordered by priority.
	 *
	 * @return array<non-empty-string, IPermission>
	 * @experimental 36.0.0
	 */
	public function getPermissions(): array;

	/**
	 * @experimental 36.0.0
	 */
	public function getPermission(string $id): ?IPermission;
}
