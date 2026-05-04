<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCP\Sharing\Permission;

use OCP\AppFramework\Attribute\Implementable;
use OCP\Sharing\Share;

/**
 * @psalm-import-type SharingPermission from Share
 * @since 34.0.0
 */
#[Implementable(since: '34.0.0')]
interface ISharePermissionType {
	/**
	 * Returns a user friendly display name for this permission.
	 *
	 * @return non-empty-string
	 */
	public function getDisplayName(): string;

	/**
	 * Returns the category the permission belongs to.
	 * If no category matches, it may return null.
	 *
	 * @return ?class-string<ISharePermissionCategoryType>
	 */
	public function getCategory(): ?string;

	/**
	 * Whether this permission is enabled by default or not.
	 *
	 * If null is returned and this permission has a category, the default from {@see ISharePermissionCategoryType::getDefault()} is used.
	 * If null is returned and this permission has no category, it will default to false.
	 */
	public function getDefault(): ?bool;
}
