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
 * @since 33.0.10
 */
enum SystemReportDetailFormat {
	/** @since 33.0.10 */
	case SingleLine;
	/** @since 33.0.10 */
	case MultiLine;
	/** @since 33.0.10 */
	case Preformatted;
}
