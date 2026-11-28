<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Governance;

use OCP\AppFramework\Attribute\Consumable;

/**
 * Whether an actor may assign or remove a label.
 *
 * New cases may be added in future versions, treat unknown ones as {@see self::No}.
 *
 * @experimental 36.0.0
 */
#[Consumable(since: '36.0.0')]
enum LabelModification: string {
	/**
	 * @experimental 36.0.0
	 */
	case No = 'no';

	/**
	 * @experimental 36.0.0
	 */
	case Yes = 'yes';

	/**
	 * Allowed, but a reason has to be passed which is recorded.
	 *
	 * @experimental 36.0.0
	 */
	case WithReason = 'with-reason';
}
