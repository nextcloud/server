<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Calendar;

/**
 * Calendar Export Options
 *
 * @since 32.0.0
 */
final class CalendarExportOptions {

	/** @var 'ical'|'jcal'|'xcal' */
	private string $format = 'ical';
	private ?string $rangeStart = null;
	private ?int $rangeCount = null;

	/**
	 * Gets the export format
	 *
	 * @return 'ical'|'jcal'|'xcal' (defaults to ical)
	 */
	public function getFormat(): string {
		return $this->format;
	}

	/**
	 * Sets the export format
	 *
	 * @param 'ical'|'jcal'|'xcal' $format
	 */
	public function setFormat(string $format): void {
		$this->format = $format;
	}

	/**
	 * Gets the start of the range to export
	 */
	public function getRangeStart(): ?string {
		return $this->rangeStart;
	}

	/**
	 * Sets the start of the range to export
	 */
	public function setRangeStart(?string $rangeStart): void {
		$this->rangeStart = $rangeStart;
	}

	/**
	 * Gets the number of objects to export
	 */
	public function getRangeCount(): ?int {
		return $this->rangeCount;
	}

	/**
	 * Sets the number of objects to export
	 */
	public function setRangeCount(?int $rangeCount): void {
		$this->rangeCount = $rangeCount;
	}
}
