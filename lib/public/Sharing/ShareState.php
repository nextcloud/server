<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCP\Sharing;

use OCP\AppFramework\Attribute\Consumable;

/**
 * @since 35.0.0
 */
#[Consumable(since: '35.0.0')]
enum ShareState: string {
	/**
	 * @since 35.0.0
	 */
	case Active = 'active';
	/**
	 * @since 35.0.0
	 */
	case Draft = 'draft';
	/**
	 * @since 35.0.0
	 */
	case Deleted = 'deleted';
}
