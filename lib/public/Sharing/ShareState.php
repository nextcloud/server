<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCP\Sharing;

use OCP\AppFramework\Attribute\Consumable;

/**
 * @since 34.0.0
 */
#[Consumable(since: '34.0.0')]
enum ShareState: string {
	case Active = 'active';
	case Draft = 'draft';
	case Deleted = 'deleted';
}
