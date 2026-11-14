<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Governance;

use OCP\AppFramework\Attribute\Consumable;

/**
 * The kinds of labels, they differ in what they express:
 *
 * - Sensitivity: what may be done with a resource. A resource has at most one.
 * - Retention: how long a resource is kept and what happens afterwards.
 * - Hold: a legal freeze, changes are denied while one is assigned.
 *
 * Every resource type supports sensitivity labels, the other kinds only apply to some of them.
 *
 * @experimental 36.0.0
 */
#[Consumable(since: '36.0.0')]
enum LabelType: string {
	/**
	 * @experimental 36.0.0
	 */
	case Sensitivity = 'sensitivity';

	/**
	 * @experimental 36.0.0
	 */
	case Retention = 'retention';

	/**
	 * @experimental 36.0.0
	 */
	case Hold = 'hold';
}
