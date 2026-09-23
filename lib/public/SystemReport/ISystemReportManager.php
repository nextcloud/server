<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\SystemReport;

/**
 * @since 36.0.0
 */
interface ISystemReportManager {
	/**
	 * Resolve every registered \OCP\SystemReport\ISystemReportSection and
	 * return its contributed sections. A section whose resolution or
	 * getDetails() call throws is skipped and logged rather than aborting
	 * the whole report.
	 *
	 * @return ISystemReportSection[]
	 * @since 36.0.0
	 */
	public function getSections(): array;
}
