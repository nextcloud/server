<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Sharing\Permission;

use OCP\AppFramework\Attribute\Consumable;

/**
 * @since 35.0.0
 */
#[Consumable(since: '35.0.0')]
enum SharePermissionPreset: string {
	/**
	 * @since 35.0.0
	 */
	case View = 'view';
	/**
	 * @since 35.0.0
	 */
	case Edit = 'edit';
}
