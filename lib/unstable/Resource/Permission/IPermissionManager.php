<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Resource\Permission;

use NCU\Resource\IResourceType;
use NCU\Resource\ResourceReference;
use OCP\Actor\Actor;
use OCP\AppFramework\Attribute\Consumable;

/**
 * Gives access to the registered permissions and resource types
 * and checks which permissions are restricted by other apps.
 *
 * Which access exists is up to the app owning the resource, it either passes the access
 * or is asked for it through {@see IResourceType::getAccess}.
 * That app also has to enforce the result.
 *
 * @experimental 36.0.0
 */
#[Consumable(since: '36.0.0')]
interface IPermissionManager {
	/**
	 * @return array<non-empty-string, IPermission> keyed by ID
	 * @experimental 36.0.0
	 */
	public function getPermissions(): array;

	/**
	 * @experimental 36.0.0
	 */
	public function getPermission(string $id): ?IPermission;

	/**
	 * @return array<non-empty-string, IResourceType> keyed by ID
	 * @experimental 36.0.0
	 */
	public function getResourceTypes(): array;

	/**
	 * @experimental 36.0.0
	 */
	public function getResourceType(string $id): ?IResourceType;

	/**
	 * Get the permissions of the actor on the resource that are not restricted.
	 *
	 * The result is what is left of the permissions granted by the access entries.
	 * If no entries are passed, the resource type is asked for the access of the actor.
	 * With an empty list of entries the actor has no access and no permission is allowed.
	 * If a restriction provider or the resource type fails, no permission is allowed.
	 *
	 * @param ?list<AccessEntry> $entries
	 * @throws \InvalidArgumentException if the resource type is not registered
	 * @experimental 36.0.0
	 */
	public function check(Actor $actor, ResourceReference $resource, ?array $entries = null): EffectivePermissions;

	/**
	 * Same as {@see self::check} for multiple resources at once, the resources can be of different types.
	 *
	 * @param list<ResourceAccess> $accesses
	 * @return array<non-empty-string, EffectivePermissions> keyed by {@see ResourceReference::key}
	 * @throws \InvalidArgumentException if a resource type is not registered or a resource is passed more than once
	 * @experimental 36.0.0
	 */
	public function checkAll(Actor $actor, array $accesses): array;

	/**
	 * Forget the results of previous checks and the access returned by the resource types.
	 *
	 * Both are remembered while the process runs. An {@see IRestrictionProvider} has to call this
	 * whenever its restrictions change and the app owning a resource whenever the access to it changes,
	 * so that later checks in the same process see the change.
	 *
	 * @experimental 36.0.0
	 */
	public function clearCache(): void;
}
