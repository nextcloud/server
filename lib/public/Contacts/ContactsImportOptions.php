<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Contacts;

use InvalidArgumentException;

/**
 * Contacts Import Options
 *
 * @since 35.0.0
 */
final class ContactsImportOptions {

	/**
	 * @since 35.0.0
	 */
	public const FORMATS = ['vcf', 'jcf', 'xcf'];

	/**
	 * @since 35.0.0
	 */
	public const VALIDATE_NONE = 0;
	/**
	 * @since 35.0.0
	 */
	public const VALIDATE_SKIP = 1;
	/**
	 * @since 35.0.0
	 */
	public const VALIDATE_FAIL = 2;
	/**
	 * @since 35.0.0
	 */
	public const VALIDATE_OPTIONS = [
		self::VALIDATE_NONE,
		self::VALIDATE_SKIP,
		self::VALIDATE_FAIL,
	];
	/**
	 * @since 35.0.0
	 */
	public const ERROR_CONTINUE = 0;
	/**
	 * @since 35.0.0
	 */
	public const ERROR_FAIL = 1;
	/**
	 * @since 35.0.0
	 */
	public const ERROR_OPTIONS = [
		self::ERROR_CONTINUE,
		self::ERROR_FAIL,
	];

	/** @var 'vcf'|'jcf'|'xcf' */
	private string $format = 'vcf';
	private bool $supersede = false;
	private int $errors = self::ERROR_FAIL;
	private int $validate = self::VALIDATE_SKIP;
	private bool $counts = false;

	/**
	 * Gets the import format
	 *
	 * @return 'vcf'|'jcf'|'xcf' (defaults to vcf)
	 * @since 35.0.0
	 */
	public function getFormat(): string {
		return $this->format;
	}

	/**
	 * Sets the import format
	 *
	 * @param 'vcf'|'jcf'|'xcf' $value
	 * @since 35.0.0
	 */
	public function setFormat(string $value): void {
		if (!in_array($value, self::FORMATS, true)) {
			throw new InvalidArgumentException('Format is not valid.');
		}
		$this->format = $value;
	}

	/**
	 * Gets whether to supersede existing objects
	 *
	 * @since 35.0.0
	 */
	public function getSupersede(): bool {
		return $this->supersede;
	}

	/**
	 * Sets whether to supersede existing objects
	 *
	 * @since 35.0.0
	 */
	public function setSupersede(bool $supersede): void {
		$this->supersede = $supersede;
	}

	/**
	 * Gets how to handle object errors
	 *
	 * @return int 0 - continue, 1 - fail
	 * @since 35.0.0
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
	 * @since 35.0.0
	 */
	public function setErrors(int $value): void {
		if (!in_array($value, self::ERROR_OPTIONS, true)) {
			throw new InvalidArgumentException('Invalid errors option specified');
		}
		$this->errors = $value;
	}

	/**
	 * Gets how to handle object validation
	 *
	 * @return int 0 - no validation, 1 - validate and skip on issue, 2 - validate and fail on issue
	 * @since 35.0.0
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
	 * @since 35.0.0
	 */
	public function setValidate(int $value): void {
		if (!in_array($value, self::VALIDATE_OPTIONS, true)) {
			throw new InvalidArgumentException('Invalid validation option specified');
		}
		$this->validate = $value;
	}

	/**
	 * Gets whether to include object counts as the first yielded value
	 *
	 * @since 35.0.0
	 */
	public function getCounts(): bool {
		return $this->counts;
	}

	/**
	 * Sets whether to include object counts as the first yielded value
	 *
	 * @since 35.0.0
	 */
	public function setCounts(bool $counts): void {
		$this->counts = $counts;
	}

}
