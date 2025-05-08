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
final class CalendarImportOptions {

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
	 * @param 'ical'|'jcal'|'xcal' $format
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
	 *
	 * @template $errors of self::ERROR_*
	 */
	public function setErrors(int $errors): void {
		if (!in_array($errors, self::ERROR_OPTIONS, true)) {
			throw new \InvalidArgumentException("Invalid errors handling type <$errors> specified, only ERROR_CONTINUE or ERROR_FAIL allowed");
		}
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
	 *
	 * @template $validate of self::VALIDATE_*
	 */
	public function setValidate(int $validate): void {
		if (!in_array($validate, self::VALIDATE_OPTIONS, true)) {
			throw new \InvalidArgumentException("Invalid validation handling type <$validate> specified, only VALIDATE_NONE, VALIDATE_SKIP or VALIDATE_FAIL allowed");
		}
		$this->validate = $validate;
	}

}
