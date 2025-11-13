<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Connector;

trait PermissionsTrait {
	/**
	 * Removes mount information from the permission string if isPublic is true.
	 */
	private function getPermissionString(bool $isPublic, string $permissions):
	string {
		return $isPublic ? str_replace(['S', 'M'], '', $permissions) : $permissions;
	}
}
