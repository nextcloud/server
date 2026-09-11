<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\SystemReport;

/**
 * A single piece of information contributed to a system report section.
 *
 * @since 36.0.0
 */
final readonly class SystemReportDetail {
	/**
	 * @param string $title Translated label of this detail
	 * @param string $content Value of this detail. Must not contain secrets such as
	 *                        passwords, private keys, or tokens - system reports are
	 *                        commonly shared with third parties for support purposes.
	 * @param SystemReportDetailFormat $format How to render $content
	 * @since 36.0.0
	 */
	public function __construct(
		private string $title,
		private string $content,
		private SystemReportDetailFormat $format = SystemReportDetailFormat::MultiLine,
	) {
	}

	/** @since 36.0.0 */
	public function getTitle(): string {
		return $this->title;
	}

	/** @since 36.0.0 */
	public function getContent(): string {
		return $this->content;
	}

	/** @since 36.0.0 */
	public function getFormat(): SystemReportDetailFormat {
		return $this->format;
	}
}
