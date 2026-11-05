<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Permission;

use OCP\AppFramework\Attribute\Consumable;

/**
 * One option of an {@see AEnumPermission}.
 *
 * @experimental 36.0.0
 */
#[Consumable(since: '36.0.0')]
final readonly class PermissionOption {
	/**
	 * @param non-empty-string $value Stable value that is persisted by consumers and must never change
	 * @param non-empty-string $displayName
	 * @param ?non-empty-string $hint
	 * @experimental 36.0.0
	 */
	public function __construct(
		public string $value,
		public string $displayName,
		public ?string $hint = null,
	) {
	}
}
