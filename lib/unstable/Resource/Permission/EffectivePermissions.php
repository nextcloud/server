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
	/**
	 * @param list<non-empty-string> $allowedPermissionIds
	 * @experimental 36.0.0
	 */
	public function __construct(
		private array $allowedPermissionIds,
	) {
	}

	/**
	 * @experimental 36.0.0
	 */
	public function isAllowed(string $permissionId): bool {
		return in_array($permissionId, $this->allowedPermissionIds, true);
	}

	/**
	 * @return list<non-empty-string>
	 * @experimental 36.0.0
	 */
	public function getAllowedPermissionIds(): array {
		return $this->allowedPermissionIds;
	}
}
