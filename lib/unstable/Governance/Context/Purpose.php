<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace NCU\Governance\Context;

/**
 * Why labels are being read or changed. Changes the answer, not just the
 * permission: enforcement sees hidden/inherited labels, display does not.
 */
enum Purpose: string {
	/** Show labels in the UI: only what the principal may see */
	case Display = 'display';
	/** DLP decision (may this share/download happen?): full effective labels */
	case Enforcement = 'enforcement';
	/** Lifecycle/retention processing */
	case Retention = 'retention';
	/** eDiscovery / legal hold search */
	case Discovery = 'discovery';
	/** Apply, change or remove labels manually */
	case Management = 'management';
	/** Classifier or policy applying labels automatically */
	case AutoLabel = 'auto_label';

	public function changesLabels(): bool {
		return match ($this) {
			self::Management, self::AutoLabel => true,
			self::Display, self::Enforcement, self::Retention, self::Discovery => false,
		};
	}
}
