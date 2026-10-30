<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\SystemReport;

/**
 * This interface needs to be implemented if you want to contribute a custom
 * section to the system report that administrators can generate for support
 * purposes. Register your implementation with
 * \OCP\AppFramework\Bootstrap\IRegistrationContext::registerSystemReportSection().
 *
 * @since 36.0.0
 */
interface ISystemReportSection {
	/**
	 * Unique, stable identifier of this section, e.g. "saml".
	 * Sections registered under the same id are merged.
	 *
	 * @since 36.0.0
	 */
	public function getId(): string;

	/**
	 * Translated title shown as heading for this section in the report.
	 *
	 * @since 36.0.0
	 */
	public function getTitle(): string;

	/**
	 * @return SystemReportDetail[]
	 * @since 36.0.0
	 */
	public function getDetails(): array;
}
