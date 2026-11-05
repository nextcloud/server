<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Permission;

use OCP\AppFramework\Attribute\Consumable;

/**
 * Registry of all permissions and the resource types they apply to.
 *
 * Permissions and resource types are registered by class name in {@see \OCP\AppFramework\Bootstrap\IBootstrap::register()}
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
	 * @param class-string<IPermissionResourceType> $class
	 * @experimental 36.0.0
	 */
	public function registerResourceType(string $class): void;

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

	/**
	 * Ordered by ID.
	 *
	 * @return array<non-empty-string, IPermissionResourceType>
	 * @experimental 36.0.0
	 */
	public function getResourceTypes(): array;

	/**
	 * @experimental 36.0.0
	 */
	public function getResourceType(string $id): ?IPermissionResourceType;

	/**
	 * All permissions supported by a resource type, ordered by priority.
	 *
	 * @return list<ResolvedPermission>
	 * @experimental 36.0.0
	 */
	public function getPermissionsForResourceType(string $resourceTypeId): array;

	/**
	 * A permission for every resource type supporting it, ordered by resource type ID.
	 *
	 * @return list<ResolvedPermission>
	 * @experimental 36.0.0
	 */
	public function getResourceTypesForPermission(string $permissionId): array;
}
