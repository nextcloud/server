<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Resource\Permission;

use OCP\AppFramework\Attribute\Consumable;

/**
 * The permissions of an actor on a resource that are left after all restrictions.
 *
 * @psalm-immutable
 * @experimental 36.0.0
 */
#[Consumable(since: '36.0.0')]
final readonly class EffectivePermissions {
	/** @var array<non-empty-string, true> */
	private array $allowed;

	/**
	 * @param list<non-empty-string> $allowedPermissionIds
	 * @experimental 36.0.0
	 */
	public function __construct(array $allowedPermissionIds) {
		$this->allowed = array_fill_keys($allowedPermissionIds, true);
	}

	/**
	 * @param string|CorePermission $permission ID of the permission or the core permission
	 * @experimental 36.0.0
	 */
	public function isAllowed(string|CorePermission $permission): bool {
		$id = $permission instanceof CorePermission ? $permission->value : $permission;
		return isset($this->allowed[$id]);
	}

	/**
	 * @return list<non-empty-string>
	 * @experimental 36.0.0
	 */
	public function getAllowedPermissionIds(): array {
		return array_keys($this->allowed);
	}
}
