<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\OCM;

use OCP\AppFramework\Attribute\Consumable;
use OCP\Constants;

/**
 * Permission conversion for Open Cloud Mesh.
 *
 * @since 35.0.3
 */
#[Consumable(since: '35.0.3')]
final class Permissions {

	/**
	 * Convert a Nextcloud permission bitmask to OCM permissions.
	 * Create and update both map to write; delete alone grants no OCM permission.
	 *
	 * @return list<string>
	 * @since 35.0.3
	 */
	public static function fromNextcloud(int $ncPermissions): array {
		$ocmPermissions = [];

		if ($ncPermissions & Constants::PERMISSION_SHARE) {
			$ocmPermissions[] = 'share';
		}

		if ($ncPermissions & Constants::PERMISSION_READ) {
			$ocmPermissions[] = 'read';
		}

		if (($ncPermissions & Constants::PERMISSION_CREATE)
			|| ($ncPermissions & Constants::PERMISSION_UPDATE)) {
			$ocmPermissions[] = 'write';
		}

		return $ocmPermissions;
	}
}
