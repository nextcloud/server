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
 * @since 35.0.2
 */
enum SystemReportDetailFormat {
	/** @since 35.0.2 */
	case SingleLine;
	/** @since 35.0.2 */
	case MultiLine;
	/** @since 35.0.2 */
	case Preformatted;
}
