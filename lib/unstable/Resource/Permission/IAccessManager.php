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
 * Tells which access an actor was given to resources, before any restriction.
 *
 * Use {@see IPermissionManager::check} to get the permissions that are left after the restrictions.
 *
 * @experimental 36.0.0
 */
#[Consumable(since: '36.0.0')]
interface IAccessManager {
	/**
	 * Get the ways the actor has access to the resources, the resources can be of different types.
	 *
	 * Ask this instead of {@see IResourceType::getAccess}, the resource type is not the only source of access.
	 *
	 * @param list<ResourceReference> $resources
	 * @return array<non-empty-string, non-empty-list<AccessEntry>> keyed by {@see ResourceReference::key},
	 *                                                              resources the actor has no access to are left out
	 * @throws \InvalidArgumentException if a resource type is not registered
	 * @experimental 36.0.0
	 */
	public function getAccess(Actor $actor, array $resources): array;
}
