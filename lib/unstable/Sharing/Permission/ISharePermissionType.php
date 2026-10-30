<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Sharing\Permission;

use NCU\Sharing\Share;
use OCP\AppFramework\Attribute\Implementable;
use OCP\L10N\IFactory;

/**
 * @psalm-import-type SharingPermission from Share
 * @experimental 35.0.0
 */
#[Implementable(since: '35.0.0')]
interface ISharePermissionType {
	/**
	 * Returns a user friendly display name for this permission.
	 *
	 * @return non-empty-string
	 * @experimental 35.0.0
	 */
	public function getDisplayName(IFactory $l10nFactory): string;

	/**
	 * Returns a user friendly hint for this permission.
	 *
	 * @return ?non-empty-string
	 * @experimental 35.0.0
	 */
	public function getHint(IFactory $l10nFactory): ?string;

	/**
	 * Returns a priority used for sorting the permissions for the user interface.
	 * A higher value means the permission will be shown further up in the list of permissions.
	 *
	 * @return int<1, 100>
	 * @experimental 35.0.0
	 */
	public function getPriority(): int;

	/**
	 * Whether this permission is enabled by default or not.
	 *
	 * @experimental 35.0.0
	 */
	public function isEnabledByDefault(): bool;
}
