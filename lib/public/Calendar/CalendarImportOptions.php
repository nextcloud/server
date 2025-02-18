<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Calendar;

/**
 * Calendar Import Options
 *
 * @since 32.0.0
 */
class CalendarImportOptions {

	private string $format = 'ical';
	private bool $supersede = false;
	private int $errors = 1;
	private int $validate = 1;

	/**
	 * Gets the import format
	 *
	 * @return string ical, jcal, xcal, etc, defaults to ical
	 */
	public function getFormat(): string {
		return $this->format;
	}

	/**
	 * Sets the import format
	 *
	 * @param string $format ical, jcal, xcal, etc, defaults to ical
	 */
	public function setFormat(string $format): void {
		$this->format = $format;
	}

	/**
	 * Gets whether to supersede existing objects
	 */
	public function getSupersede(): bool {
		return $this->supersede;
	}

	/**
	 * Sets whether to supersede existing objects
	 */
	public function setSupersede(bool $supersede): void {
		$this->supersede = $supersede;
	}

	/**
	 * Gets how to handle object errors
	 *
	 * @return int 0 - continue, 1 - fail
	 */
	public function getErrors(): int {
		return $this->errors;
	}

	/**
	 * Sets how to handle object errors
	 *
	 * @param int $errors 0 - continue, 1 - fail
	 */
	public function setErrors(int $errors): void {
		$this->errors = $errors;
	}

	/**
	 * Gets how to handle object validation
	 *
	 * @return int 0 - no validation, 1 - validate and skip on issue, 2 - validate and fail on issue
	 */
	public function getValidate(): int {
		return $this->validate;
	}

	/**
	 * Sets how to handle object validation
	 *
	 * @param int $validate 0 - no validation, 1 - validate and skip on issue, 2 - validate and fail on issue
	 */
	public function setValidate(int $validate): void {
		$this->validate = $validate;
	}

}
