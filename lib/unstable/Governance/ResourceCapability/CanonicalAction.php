<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Governance\ResourceCapability;

use OCP\AppFramework\Attribute\Consumable;

/**
 * @experimental 36.0.0
 */
#[Consumable(since: '36.0.0')]
enum CanonicalAction: string {
	/**
	 * @experimental 36.0.0
	 */
	case Share = 'core.action.share';
	/**
	 * @experimental 36.0.0
	 */
	case Download = 'core.action.download';
}
