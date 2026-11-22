<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\SystemReport;

/**
 * How a \OCP\SystemReport\SystemReportDetail should be rendered.
 *
 * @since 36.0.0
 */
enum SystemReportDetailFormat {
	/** @since 36.0.0 */
	case SingleLine;
	/** @since 36.0.0 */
	case MultiLine;
	/** @since 36.0.0 */
	case Preformatted;
}
