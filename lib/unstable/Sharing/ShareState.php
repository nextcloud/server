<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Sharing;

use OCP\AppFramework\Attribute\Consumable;

/**
 * @experimental 35.0.0
 */
#[Consumable(since: '35.0.0')]
enum ShareState: string {
	/**
	 * @experimental 35.0.0
	 */
	case Active = 'active';
	/**
	 * @experimental 35.0.0
	 */
	case Draft = 'draft';
	/**
	 * @experimental 35.0.0
	 */
	case Deleted = 'deleted';
}
