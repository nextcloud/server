<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Permission;

use OCP\AppFramework\Attribute\Consumable;

/**
 * A permission as it applies to one resource type, with the overrides of its {@see PermissionBinding} applied.
 *
 * @experimental 36.0.0
 */
#[Consumable(since: '36.0.0')]
final readonly class ResolvedPermission {
	/**
	 * @param non-empty-string $resourceTypeId
	 * @param non-empty-string $displayName
	 * @param ?non-empty-string $hint
	 * @param list<non-empty-string> $requiredPermissionIds Required permissions that are also bound to the resource type
	 * @experimental 36.0.0
	 */
	public function __construct(
		public IPermission $permission,
		public string $resourceTypeId,
		public string $displayName,
		public ?string $hint,
		public string $defaultValue,
		public array $requiredPermissionIds,
	) {
	}
}
