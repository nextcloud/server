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
 * @since 34.0.5
 */
enum SystemReportDetailFormat {
	/** @since 34.0.5 */
	case SingleLine;
	/** @since 34.0.5 */
	case MultiLine;
	/** @since 34.0.5 */
	case Preformatted;
}
