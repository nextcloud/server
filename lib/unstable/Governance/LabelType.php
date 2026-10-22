<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Governance;

use OCP\AppFramework\Attribute\Consumable;

/**
 * The three kinds of governance label. They differ in what they express, not
 * only in name:
 *
 * - Sensitivity: what may be done with a resource. Single-assignment.
 * - Retention: how long a resource must or should be kept, and what happens
 *   at the end of that period.
 * - Hold: a legal freeze. While one applies, changes are denied regardless of
 *   any other label, and it is only ever released manually.
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
