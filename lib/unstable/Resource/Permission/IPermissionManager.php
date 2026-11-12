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
 * The check does not decide which access exists, this is up to the app owning the resource.
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
	 * With access entries the result is what is left of the permissions granted by them.
	 * Without access entries the result is what is left of all permissions that apply to
	 * the resource type, the caller has to intersect it with the access it grants itself.
	 *
	 * @param list<AccessEntry> $entries
	 * @throws \InvalidArgumentException if the resource type is not registered
	 * @experimental 36.0.0
	 */
	public function check(Actor $actor, ResourceReference $resource, array $entries = []): EffectivePermissions;

	/**
	 * Same as {@see self::check} for multiple resources at once, the resources can be of different types.
	 *
	 * @param list<ResourceAccess> $accesses
	 * @return array<non-empty-string, EffectivePermissions> keyed by {@see ResourceReference::key}
	 * @throws \InvalidArgumentException if a resource type is not registered or a resource is passed more than once
	 * @experimental 36.0.0
	 */
	public function checkAll(Actor $actor, array $accesses): array;
}
