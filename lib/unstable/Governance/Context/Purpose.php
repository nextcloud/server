<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace NCU\Governance\Context;

use OCP\AppFramework\Attribute\Consumable;

/**
 * Why labels are being read or changed. Changes the answer, not just the
 * permission: enforcement sees hidden/inherited labels, display does not.
 *
 * todo: check if this is really needed for some use-cases and drop it if not
 *
 * @experimental 36.0.0
 */
#[Consumable(since: '36.0.0')]
enum Purpose: string {
	/**
	 * Show labels in the UI: only what the principal may see
	 *
	 * @experimental 36.0.0
	 */
	case Display = 'display';
	/**
	 * DLP decision (may this share/download happen?): full effective labels
	 *
	 * @experimental 36.0.0
	 */
	case Enforcement = 'enforcement';
	/**
	 * Lifecycle/retention processing
	 *
	 * @experimental 36.0.0
	 */
	case Retention = 'retention';
	/**
	 * Legal hold search
	 *
	 * @experimental 36.0.0
	 */
	case Discovery = 'discovery';
	/**
	 * Apply, change or remove labels manually
	 *
	 * @experimental 36.0.0
	 */
	case Management = 'management';
	/**
	 * Classifier or policy applying labels automatically
	 *
	 * @experimental 36.0.0
	 */
	case AutoLabel = 'auto_label';

	/**
	 * @experimental 36.0.0
	 */
	public function changesLabels(): bool {
		return match ($this) {
			self::Management, self::AutoLabel => true,
			self::Display, self::Enforcement, self::Retention, self::Discovery => false,
		};
	}
}
