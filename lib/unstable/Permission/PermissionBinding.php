<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Permission;

use OCP\AppFramework\Attribute\Consumable;

/**
 * Declares that a resource type supports a permission and how it is presented for this resource type.
 *
 * Overrides only change the presentation, the meaning of the permission stays the same.
 * A null value falls back to the value of the permission.
 *
 * @experimental 36.0.0
 */
#[Consumable(since: '36.0.0')]
final readonly class PermissionBinding {
	/**
	 * @param non-empty-string $permissionId
	 * @param ?non-empty-string $displayName
	 * @param ?non-empty-string $hint
	 * @experimental 36.0.0
	 */
	public function __construct(
		public string $permissionId,
		public ?string $displayName = null,
		public ?string $hint = null,
		public ?string $defaultValue = null,
	) {
	}
}
