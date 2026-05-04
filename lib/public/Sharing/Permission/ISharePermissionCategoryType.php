<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCP\Sharing\Permission;

use OCP\AppFramework\Attribute\Implementable;

/**
 * @since 34.0.0
 */
#[Implementable(since: '34.0.0')]
interface ISharePermissionCategoryType {
	/**
	 * Returns a user friendly display name for this permission category.
	 *
	 * @return non-empty-string
	 */
	public function getDisplayName(): string;

	/**
	 * Whether permissions in this permission category are enabled by default or not.
	 *
	 * Each permission can still change its own default value regardless of the default value of its category.
	 */
	public function getDefault(): bool;
}
