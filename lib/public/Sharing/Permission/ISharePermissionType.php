<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCP\Sharing\Permission;

use OCP\AppFramework\Attribute\Implementable;
use OCP\L10N\IFactory;
use OCP\Sharing\Share;

/**
 * @psalm-import-type SharingPermission from Share
 * @since 35.0.0
 */
#[Implementable(since: '35.0.0')]
interface ISharePermissionType {
	/**
	 * Returns a user friendly display name for this permission.
	 *
	 * @return non-empty-string
	 * @since 35.0.0
	 */
	public function getDisplayName(IFactory $l10nFactory): string;

	/**
	 * Returns a user friendly hint for this permission.
	 *
	 * @return ?non-empty-string
	 * @since 35.0.0
	 */
	public function getHint(IFactory $l10nFactory): ?string;

	/**
	 * Returns the presets the permission belongs to.
	 *
	 * If one of the returned presets is selected by the user, this permission will be enabled.
	 *
	 * @return list<SharePermissionPreset>
	 * @since 35.0.0
	 */
	public function getPresets(): array;

	/**
	 * Whether this permission is enabled by default or not.
	 *
	 * @since 35.0.0
	 */
	public function getDefault(): bool;
}
