<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\AppFramework\Utility;

/**
 * Common causes of a {@see \OCP\AppFramework\Attribute\PersistAcrossRequests}
 * service going stale, for use with {@see IPersistentServiceInvalidator}.
 *
 * This only covers causes shared by core services. An app is still free to
 * use a plain string group name for anything specific to itself.
 *
 * @since 36.0.0
 */
enum PersistentServiceGroup: string {
	/**
	 * An app was enabled or disabled
	 *
	 * @since 36.0.0
	 */
	case Apps = 'apps';

	/**
	 * A system-wide (config.php) or per-app config value was set or deleted
	 *
	 * @since 36.0.0
	 */
	case Config = 'config';
}
