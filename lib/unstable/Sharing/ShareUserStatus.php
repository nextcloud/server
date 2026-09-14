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
enum ShareUserStatus: string {
	/**
	 * @experimental 35.0.0
	 */
	case Pending = 'pending';
	/**
	 * @experimental 35.0.0
	 */
	case Accepted = 'accepted';
	/**
	 * @experimental 35.0.0
	 */
	case Rejected = 'rejected';
}
