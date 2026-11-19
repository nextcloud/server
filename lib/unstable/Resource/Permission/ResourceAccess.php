<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Resource\Permission;

use NCU\Resource\ResourceReference;
use OCP\AppFramework\Attribute\Consumable;

/**
 * A resource together with the ways the actor has access to it.
 *
 * The access is either given as access entries (see {@see self::entries}), as aggregated permissions
 * (see {@see self::aggregated}) or not at all, then the resource type is asked for the access entries.
 *
 * @psalm-immutable
 * @experimental 36.0.0
 */
#[Consumable(since: '36.0.0')]
final readonly class ResourceAccess {
	/**
	 * @param ?list<AccessEntry> $entries
	 * @param ?list<non-empty-string> $aggregatedPermissionIds
	 */
	private function __construct(
		public ResourceReference $resource,
		public ?array $entries,
		public ?array $aggregatedPermissionIds,
	) {
	}

	/**
	 * The ways the actor has access to the resource, one entry per recipient.
	 *
	 * @param ?list<AccessEntry> $entries Null to ask the resource type for the access of the actor,
	 *                                    an empty list means the actor has no access
	 * @experimental 36.0.0
	 */
	public static function entries(ResourceReference $resource, ?array $entries = null): self {
		return new self($resource, $entries, null);
	}

	/**
	 * The permissions of the actor merged from all the ways they have access, e.g. permissions that are already cached.
	 *
	 * The recipients the permissions were granted to are not known. If a restriction applies only to
	 * the access through one recipient, the resource type is asked for the access entries.
	 * The result never contains more than these permissions.
	 *
	 * @param list<non-empty-string> $permissionIds An empty list means the actor has no access
	 * @experimental 36.0.0
	 */
	public static function aggregated(ResourceReference $resource, array $permissionIds): self {
		return new self($resource, null, $permissionIds);
	}
}
