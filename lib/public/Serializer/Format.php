<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Serializer;

use OCP\AppFramework\Attribute\Consumable;

/**
 * The wire formats supported by {@see ISerializer}
 *
 * @since 36.0.0
 */
#[Consumable(since: '36.0.0')]
enum Format: string {
	/** @since 36.0.0 */
	case JSON = 'json';
	/** @since 36.0.0 */
	case XML = 'xml';
	/** @since 36.0.0 */
	case CSV = 'csv';
}
