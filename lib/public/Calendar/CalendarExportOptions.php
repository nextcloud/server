<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Calendar;

/**
 * Calendar Export Options
 */
class CalendarExportOptions {

	private string $format = 'ical';
	private ?string $rangeStart = null;
	private ?int $rangeCount = null;

	/**
	 * Gets the export format
	 *
	 * @return string ical, jcal, xcal, etc, defaults to ical
	 */
	public function getFormat(): string {
		return $this->format;
	}

	/**
	 * Sets the export format
	 *
	 * @param string $format ical, jcal, xcal, etc, defaults to ical
	 */
	public function setFormat(string $format): void {
		$this->format = $format;
	}

	/**
	 * Gets the start of the range to export
	 *
	 * @return string|null
	 */
	public function getRangeStart(): ?string {
		return $this->rangeStart;
	}

	/**
	 * Sets the start of the range to export
	 *
	 * @param string|null $rangeStart
	 */
	public function setRangeStart(?string $rangeStart): void {
		$this->rangeStart = $rangeStart;
	}

	/**
	 * Gets the number of objects to export
	 *
	 * @return int|null
	 */
	public function getRangeCount(): ?int {
		return $this->rangeCount;
	}

	/**
	 * Sets the number of objects to export
	 *
	 * @param int|null $rangeCount
	 */
	public function setRangeCount(?int $rangeCount): void {
		$this->rangeCount = $rangeCount;
	}
}
