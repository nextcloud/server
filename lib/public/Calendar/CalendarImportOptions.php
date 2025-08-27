<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Calendar;

use InvalidArgumentException;

/**
 * Calendar Import Options
 *
 * @since 32.0.0
 */
final class CalendarImportOptions {

	public const FORMATS = ['ical', 'jcal', 'xcal'];

	public const VALIDATE_NONE = 0;
	public const VALIDATE_SKIP = 1;
	public const VALIDATE_FAIL = 2;
	public const VALIDATE_OPTIONS = [
		self::VALIDATE_NONE,
		self::VALIDATE_SKIP,
		self::VALIDATE_FAIL,
	];
	public const ERROR_CONTINUE = 0;
	public const ERROR_FAIL = 1;
	public const ERROR_OPTIONS = [
		self::ERROR_CONTINUE,
		self::ERROR_FAIL,
	];

	/** @var 'ical'|'jcal'|'xcal' */
	private string $format = 'ical';
	private bool $supersede = false;
	private int $errors = self::ERROR_FAIL;
	private int $validate = self::VALIDATE_SKIP;

	/**
	 * Gets the import format
	 *
	 * @return 'ical'|'jcal'|'xcal' (defaults to ical)
	 */
	public function getFormat(): string {
		return $this->format;
	}

	/**
	 * Sets the import format
	 *
	 * @param 'ical'|'jcal'|'xcal' $value
	 */
	public function setFormat(string $value): void {
		if (!in_array($value, self::FORMATS, true)) {
			throw new InvalidArgumentException('Format is not valid.');
		}
		$this->format = $value;
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
	 * @param int $value 0 - continue, 1 - fail
	 *
	 * @template $value of self::ERROR_*
	 */
	public function setErrors(int $value): void {
		if (!in_array($value, CalendarImportOptions::ERROR_OPTIONS, true)) {
			throw new InvalidArgumentException('Invalid errors option specified');
		}
		$this->errors = $value;
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
	 * @param int $value 0 - no validation, 1 - validate and skip on issue, 2 - validate and fail on issue
	 *
	 * @template $value of self::VALIDATE_*
	 */
	public function setValidate(int $value): void {
		if (!in_array($value, CalendarImportOptions::VALIDATE_OPTIONS, true)) {
			throw new InvalidArgumentException('Invalid validation option specified');
		}
		$this->validate = $value;
	}

}
